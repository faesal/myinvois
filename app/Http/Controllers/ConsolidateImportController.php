<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\eInvoisModel; // Ensure this model exists in your App\Models folder

class ConsolidateImportController extends Controller
{
    /**
     * Display the import interface
     */
 public function index(Request $request)
    {
        // 1. Determine the layout based on the user's role
        $isDeveloper = (auth()->check() && auth()->user()->role === 'developer');
        $layout = $isDeveloper ? 'layouts.developerLayout' : 'layouts.app';

        $connections = [];
        $selectedConnection = $request->query('connection_integrate');

        // 2. Fetch Connections ONLY if Developer
        if ($isDeveloper) {
            $connections = DB::table('customer')
                ->select('connection_integrate', 'registration_name')
                ->where('id_developer', auth()->id())
                ->where('customer_type', 'SUPPLIER') // Ensures only the main account (not buyers) is fetched
                ->where('is_deleted', 0)
                ->whereNotNull('connection_integrate')
                ->groupBy('connection_integrate', 'registration_name')
                ->get();
        }

        // 3. Base Query (Your original logic)
        $query = DB::table('consolidate_invoice as ci')
            ->select('ci.*')
            ->addSelect(DB::raw('(SELECT COUNT(*) FROM invoice_item WHERE id_consolidate_invoice = ci.id_invoice) as is_processed'))
            ->where('ci.invoice_status', 'consolidated')
            ->where('ci.is_import', 1);

        // 4. Apply Filters
        if ($isDeveloper && $selectedConnection) {
            $query->where('ci.connection_integrate', $selectedConnection);
        } elseif (!$isDeveloper) {
            // Optional fallback: enforce session connection for normal users to prevent data leakage
            $query->where('ci.connection_integrate', session('connection_integrate'));
        }

        $consolidations = $query->orderBy('ci.created_at', 'desc')->paginate(10);

        // 5. Pass variables to the view
        return view('consolidate.index', compact('layout', 'consolidations', 'isDeveloper', 'connections', 'selectedConnection'));
    }

    /**
     * Handle the CSV Batch Import (UPDATED)
     * - Maps 8-column structure including company_name lookup
     * - Catches Developer connection dropdown input
     */
    public function importBatch(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $isDeveloper = (auth()->check() && auth()->user()->role === 'developer');
        
        // Use dropdown for Developer, fallback to Session for Normal User
        $selected_connection = $isDeveloper ? $request->input('connection_integrate') : session('connection_integrate');
        $id_developer = $isDeveloper ? auth()->id() : session('id_developer');

        if (!$selected_connection) {
            return redirect()->back()->with('error', 'No active connection found. Please select a LHDN account.');
        }

        $file = $request->file('file');
        if (($handle = fopen($file->getRealPath(), 'r')) === false) {
            return redirect()->back()->with('error', 'Could not open the file.');
        }

        $batchUniqueId = (string) Str::uuid();
        $currentDate = Carbon::now();
        $invoiceNoToSave = 'CONSO-' . $currentDate->format('YmdHis');

        // 1. Create Header (Initialized)
        $invoiceId = DB::table('consolidate_invoice')->insertGetId([
            'unique_id' => $batchUniqueId,
            'invoice_no' => $invoiceNoToSave,
            'connection_integrate' => $selected_connection,
            'id_developer' => $id_developer,
            'id_customer' => 6, // Default, will update if company_name matches
            'invoice_status' => 'consolidated',
            'is_import' => 1,
            'tax_category_id' => '01', 
            'tax_scheme_id' => 'OTH',   
            'created_at' => $currentDate,
            'updated_at' => $currentDate,
            'price' => 0,
            'tax_amount' => 0,
            'taxable_amount' => 0,
            'consolidate_complete_total' => 0,
            'consolidate_total_amount_before' => 0,
            'consolidate_total_item' => 0,
        ]);

        // Accumulators
        $totalItems = 0; 
        $totalGross = 0; // Accumulates Gross (Before Disc)
        $totalNet = 0;   // Accumulates Net (After Disc)
        $totalTax = 0;
        $totalGrand = 0;

        $itemIds = []; 
        $rowIndex = 0;
        $customInvoiceNoFound = false;
        $headerCustomerUpdated = false;

        while (($row = fgetcsv($handle, 1000, ",")) !== false) {
            $rowIndex++;
            if ($rowIndex == 1 || empty($row[0])) continue; 

            // CSV Mapping based on standard 8-column format
            // 0: invoice_no, 1: company_name, 2: issue_date, 3: description, 4: qty, 5: unit_price, 6: discount_amount, 7: tax_rate
            $csvInvoiceNo = trim($row[0] ?? '');
            $companyName  = trim($row[1] ?? '');
            $csvIssueDate = trim($row[2] ?? '');
            $desc         = $row[3] ?? 'Imported Item';
            $qty          = (float)($row[4] ?? 0);
            $unitPrice    = (float)($row[5] ?? 0);
            $discAmt      = (float)($row[6] ?? 0); 
            $taxRate      = (float)($row[7] ?? 0); 

            // Handle Invoice No Override
            if (!empty($csvInvoiceNo) && !$customInvoiceNoFound) {
                $invoiceNoToSave = $csvInvoiceNo;
                $customInvoiceNoFound = true; 
                DB::table('consolidate_invoice')->where('id_invoice', $invoiceId)->update(['invoice_no' => $invoiceNoToSave]);
            }

            // Lookup Customer ID by Company Name (Mapping)
            $customerId = 6; // Default fallback
            if (!empty($companyName)) {
                $party = DB::table('customer')
                    ->where('connection_integrate', $selected_connection)
                    ->where('registration_name', $companyName)
                    ->where('is_deleted', 0)
                    ->first();
                
                if ($party) {
                    $customerId = $party->id_customer;
                    // Update header customer ID on first match
                    if (!$headerCustomerUpdated) {
                        DB::table('consolidate_invoice')->where('id_invoice', $invoiceId)->update(['id_customer' => $customerId]);
                        $headerCustomerUpdated = true;
                    }
                }
            }

            // --- CALCULATION LOGIC ---
            // 1. Gross Amount (Total Before Discount)
            $grossAmount = $qty * $unitPrice;

            // 2. Net Amount (Total After Discount)
            $netAmount = $grossAmount - $discAmt;
            if ($netAmount < 0) $netAmount = 0; 

            // 3. Tax Amount (Calculated on Net)
            $taxAmount = round($netAmount * ($taxRate / 100), 2);

            // 4. Line Total (Net + Tax)
            $lineTotal = $netAmount + $taxAmount;
            // -------------------------

            try {
                $issueDate = !empty($csvIssueDate) ? Carbon::parse($csvIssueDate)->format('Y-m-d') : $currentDate->format('Y-m-d');
            } catch (\Exception $e) {
                $issueDate = $currentDate->format('Y-m-d'); 
            }

            // 2. Create Items
            $itemId = DB::table('consolidate_invoice_item')->insertGetId([
                'unique_id' => (string) Str::uuid(),
                'id_consolidate_invoice' => $invoiceId,
                'connection_integrate' => $selected_connection,
                'id_developer' => $id_developer,
                'id_customer' => $customerId, // Mapped from CSV
                'line_id' => $totalItems + 1,
                'is_import' => 1,
                'issue_date' => $issueDate,
                'invoiced_quantity' => $qty,
                'price_amount' => $unitPrice,
                'price_discount' => $discAmt, 
                'tax' => $taxAmount, 
                
                // --- MAPPING ---
                'line_extension_amount' => $grossAmount,  // Gross
                'price_extension_amount' => $netAmount,   // Net
                
                'item_description' => $desc,
                'item_clasification_value' => '004',
                'created_at' => $currentDate,
            ]);

            $totalItems++;
            $totalGross += $grossAmount;
            $totalNet += $netAmount;
            $totalTax += $taxAmount;
            $totalGrand += $lineTotal; 
            $itemIds[] = $itemId;
        }
        fclose($handle);

        // Update Header Totals
        DB::table('consolidate_invoice')->where('id_invoice', $invoiceId)->update([
            'consolidate_total_item' => $totalItems,
            'consolidate_list_sale_item_id' => implode(',', $itemIds),
            
            // FIXED MAPPINGS:
            'price' => round($totalGrand, 2),                            // Payable Grand Total
            'taxable_amount' => round($totalNet, 2),                     // Net (After Discount)
            'consolidate_total_amount_before' => round($totalNet, 2),    // Subtotal Excl. Tax
            'tax_amount' => round($totalTax, 2),                         // Total Tax
            'consolidate_complete_total' => round($totalGrand, 2),       // Grand Total
            
            'updated_at' => now()
        ]);

        return redirect()->back()->with('success', "Batch imported successfully!");
    }

    /**
     * Download Template
     * - Removed 'Total Price'
     * - Added 'Invoice No' & 'Company Name'
     */
    public function downloadTemplate()
    {
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=consolidate_template.csv",
        ];

        $columns = [
            'invoice_no',      // maps to consolidate_invoice.invoice_no
            'company_name',    // maps to customer.registration_name
            'issue_date', 
            'description',     // maps to consolidate_invoice_item.item_description
            'qty', 
            'unit_price', 
            'discount_amount', 
            'tax_rate'
        ];

        $example = [
            'INV-CON-001',
            'Waja Global Services',
            date('Y-m-d'), 
            'Vehicle Shipping Fee', 
            '1', 
            '1500.00', 
            '0.00', 
            '0'
        ];

        return response()->stream(function() use ($columns, $example) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, $example);
            fclose($file);
        }, 200, $headers);
    }

    /**
     * SUBMIT SELECTED BATCHES TO LISTING INVOICES
     */
    public function consolidateSubmitSelected(Request $request)
    {
        $selectedIds = $request->input('ids', []);
        if (empty($selectedIds)) {
            return response()->json(['success' => false, 'message' => 'No batches selected.'], 400);
        }

        $alreadySubmitted = DB::table('invoice_item')->whereIn('id_consolidate_invoice', $selectedIds)->exists();
        if ($alreadySubmitted) {
            return response()->json(['success' => false, 'message' => 'Batches already submitted.'], 400);
        }

        $invoiceBaseNo = 'CONSOLIDATE-' . now()->format('Ymd-His');
        $version = 1;

        foreach ($selectedIds as $batchId) {
            $items = DB::table('consolidate_invoice_item')->where('id_consolidate_invoice', $batchId)->get();
            if ($items->isEmpty()) continue;

            $targetConnection = $items->first()->connection_integrate;
            $supplier = DB::table('customer')->where('connection_integrate', $targetConnection)->where('customer_type', 'SUPPLIER')->first();
            if (!$supplier) continue;

            $chunks = collect($items)->chunk(25);
            foreach ($chunks as $chunk) {
                $uniqueId = Str::uuid();
                $invoiceNo = $invoiceBaseNo . '-V' . $version;

                // --- HEADER CALCULATION UPDATE ---
                // We use 'price_extension_amount' (Net) instead of 'line_extension_amount' (Gross)
                // so that the discount is properly reflected in the total invoice amount.
                $invoiceId = DB::table('invoice')->insertGetId([
                    'unique_id' => $uniqueId,
                    'connection_integrate' => $supplier->connection_integrate,
                    'invoice_status' => 'manual',
                    'id_customer' => 6,
                    'id_supplier' => $supplier->id_customer,
                    'invoice_no' => $invoiceNo,
                    'invoice_type_code' => '01',
                    'issue_date' => now(),
                    'tax_category_id' => '01',
                    'tax_scheme_id' => 'OTH',
                    
                    // FIXED: Taxable amount is the Net Amount (price_extension_amount)
                    // Price (Payable) is Net Amount + Tax
                    'price' => round($chunk->sum('price_extension_amount') + $chunk->sum('tax'), 2),
                    'taxable_amount' => round($chunk->sum('price_extension_amount'), 2), 
                    'tax_amount' => round($chunk->sum('tax'), 2),
                    
                    'is_import' => 1,
                    'created_at' => now(),
                ]);

                foreach ($chunk as $index => $item) {
                    DB::table('invoice_item')->insert([
                        'unique_id' => $uniqueId,
                        'id_consolidate_invoice' => $item->id_consolidate_invoice,
                        'id_developer' => $item->id_developer,
                        'issue_date' => $item->issue_date,
                        'connection_integrate' => $item->connection_integrate,
                        'line_id' => $index + 1,
                        'id_invoice' => $invoiceId,
                        'invoiced_quantity' => $item->invoiced_quantity,
                        'line_extension_amount' => round($item->line_extension_amount, 2), // Gross
                        'item_description' => $item->item_description,
                        'price_amount' => $item->price_amount,
                        'price_discount' => $item->price_discount,
                        'price_extension_amount' => $item->price_extension_amount,       // Net
                        'tax' => round($item->tax, 2),
                        'item_clasification_value' => '004', 
                        'is_import' => 1,
                        'created_at' => now()
                    ]);
                }

                DB::table('consolidate_invoice_item')
                    ->whereIn('id_invoice_item', $chunk->pluck('id_invoice_item'))
                    ->update(['submission_status' => 'submitted', 'updated_at' => now()]);

                $version++;
            }
        }
        return response()->json(['success' => true]);
    }
    
    public function view($id)
    {
        $invoice = DB::table('consolidate_invoice')->where('id_invoice', $id)->first();
        $items = DB::table('consolidate_invoice_item')->where('id_consolidate_invoice', $id)->get();
        return view('consolidate.view', compact('invoice', 'items'));
    }

    public function updateItem(Request $request, $id)
    {
        try {
            $qty = (float) $request->qty;
            $unitPrice = (float) $request->price;
            $discount = (float) $request->discount;
            $taxRate = (float) $request->tax_rate;

            // 1. Calculate Gross (Before Discount)
            $grossAmount = $qty * $unitPrice;
            
            // 2. Calculate Net (After Discount)
            $netAmount = $grossAmount - $discount;
            if ($netAmount < 0) $netAmount = 0;

            // 3. Tax is calculated on Net
            $taxRM = round($netAmount * ($taxRate / 100), 2);

            DB::table('consolidate_invoice_item')->where('id_invoice_item', $id)->update([
                'item_description' => $request->description,
                'invoiced_quantity' => $qty,
                'price_amount' => $unitPrice,
                'price_discount' => $discount,
                'tax' => $taxRM, 
                
                // --- THE FIX: CORRECTED MAPPING ---
                'line_extension_amount' => $grossAmount,  // Gross amount
                'price_extension_amount' => $netAmount,   // Net amount (Discount applied)
                // ----------------------------------
                
                'updated_at' => now()
            ]);

            $item = DB::table('consolidate_invoice_item')->where('id_invoice_item', $id)->first();
            return $this->recalculateParent($item->id_consolidate_invoice);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function exportCSV(Request $request)
    {
        $ids = explode(',', $request->ids);
        $batches = DB::table('consolidate_invoice')->whereIn('id_invoice', $ids)->get();

        $fileName = 'Export_' . date('Ymd_His') . '.csv';
        
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($batches) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Invoice ID', 'Sale ID', 'Items', 'Amount (RM)', 'Date']);

            foreach ($batches as $batch) {
                fputcsv($file, [
                    $batch->invoice_no,
                    $batch->unique_id,
                    $batch->consolidate_total_item,
                    number_format($batch->consolidate_complete_total, 2),
                    $batch->created_at
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPDF(Request $request)
    {
        $ids = explode(',', $request->ids);
        $batches = DB::table('consolidate_invoice')->whereIn('id_invoice', $ids)->get();

        if (class_exists('\PDF')) {
            $pdf = \PDF::loadView('consolidate.pdf_template', compact('batches'));
            return $pdf->download('LHDN_Consolidate_Report.pdf');
        }

        return redirect()->back()->with('error', 'PDF Library not found. Please install DomPDF.');
    }

    private function recalculateParent($parentId)
    {
        $items = DB::table('consolidate_invoice_item')->where('id_consolidate_invoice', $parentId)->get();
        $totalBeforeTax = 0; 
        $totalTax = 0; 
        $grandTotal = 0;

        foreach($items as $i) {
            // FIXED: Use 'price_extension_amount' (Net) instead of 'line_extension_amount' (Gross)
            $totalBeforeTax += (float)$i->price_extension_amount; 
            $totalTax += (float)$i->tax; 
            
            // Grand total is now Net + Tax
            $grandTotal += ((float)$i->price_extension_amount + (float)$i->tax);
        }

        DB::table('consolidate_invoice')->where('id_invoice', $parentId)->update([
            'consolidate_total_amount_before' => $totalBeforeTax,
            'tax_amount' => $totalTax,
            'consolidate_complete_total' => $grandTotal,
            'price' => $grandTotal,
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'new_subtotal' => number_format($totalBeforeTax, 2),
            'new_tax' => number_format($totalTax, 2),
            'new_total' => number_format($grandTotal, 2)
        ]);
    }

    public function addItem($invoice_id)
    {
        try {
            $parent = DB::table('consolidate_invoice')->where('id_invoice', $invoice_id)->first();

            if (!$parent) {
                return response()->json(['success' => false, 'message' => 'Parent invoice not found.'], 404);
            }

            $maxLine = DB::table('consolidate_invoice_item')
                ->where('id_consolidate_invoice', $invoice_id)
                ->max('line_id');
            $nextLineId = $maxLine ? $maxLine + 1 : 1;

            $newItemId = DB::table('consolidate_invoice_item')->insertGetId([
                'unique_id' => (string) Str::uuid(),
                'id_consolidate_invoice' => $invoice_id,
                'connection_integrate' => $parent->connection_integrate,
                'id_developer' => $parent->id_developer,
                'id_customer' => $parent->id_customer,
                'line_id' => $nextLineId,
                'item_description' => '',
                'invoiced_quantity' => 0,
                'price_amount' => 0.00,
                'price_discount' => 0.00,
                'tax' => 0.00,
                'line_extension_amount' => 0.00,
                'price_extension_amount' => 0.00,
                'is_import' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json(['success' => true, 'id' => $newItemId]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteItem($id)
    {
        $item = DB::table('consolidate_invoice_item')->where('id_invoice_item', $id)->first();
        if ($item) {
            $parentId = $item->id_consolidate_invoice;
            DB::table('consolidate_invoice_item')->where('id_invoice_item', $id)->delete();
            return $this->recalculateParent($parentId);
        }
        return response()->json(['success' => false]);
    }

    public function destroy($id)
    {
        DB::table('consolidate_invoice_item')->where('id_consolidate_invoice', $id)->delete();
        DB::table('consolidate_invoice')->where('id_invoice', $id)->delete();
        return redirect()->back()->with('success', 'Batch deleted successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate(['created_at' => 'required|date_format:Y-m-d', 'amount' => 'required|numeric']);
        DB::table('consolidate_invoice')->where('id_invoice', $id)->update([
            'invoice_no' => $request->invoice_no,
            'unique_id'  => $request->unique_id,
            'consolidate_complete_total' => $request->amount,
            'price' => $request->amount,
            'created_at' => $request->created_at . ' ' . date('H:i:s'),
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Updated successfully.');
    }
}