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
    public function index()
    {
        // We join or use a subquery to see if the ID exists in the final invoice_item table
        $consolidations = DB::table('consolidate_invoice as ci')
            ->select('ci.*')
            ->addSelect(DB::raw('(SELECT COUNT(*) FROM invoice_item WHERE id_consolidate_invoice = ci.id_invoice) as is_processed'))
            ->where('ci.invoice_status', 'consolidated')
            ->where('ci.is_import', 1)
            ->orderBy('ci.created_at', 'desc')
            ->paginate(10);

        return view('consolidate.index', compact('consolidations'));
    }

    /**
     * Handle the CSV Batch Import (UPDATED)
     * - Checks column 7 for "Invoice No"
     * - Overwrites the auto-generated number if found
     */
public function importBatch(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:csv,txt|max:10240',
    ]);

    $selected_connection = session('connection_integrate');
    $id_developer = session('id_developer');

    if (!$selected_connection) {
        return redirect()->back()->with('error', 'No active connection found.');
    }

    $file = $request->file('file');
    if (($handle = fopen($file->getRealPath(), 'r')) === false) {
        return redirect()->back()->with('error', 'Could not open the file.');
    }

    $batchUniqueId = (string) Str::uuid();
    $currentDate = Carbon::now();
    $invoiceNoToSave = 'CONSO-' . $currentDate->format('YmdHis');

    // 1. Create Header
    $invoiceId = DB::table('consolidate_invoice')->insertGetId([
        'unique_id' => $batchUniqueId,
        'invoice_no' => $invoiceNoToSave,
        'connection_integrate' => $selected_connection,
        'id_developer' => $id_developer,
        'id_customer' => 6,
        'invoice_status' => 'consolidated',
        'is_import' => 1,
        'tax_category_id' => '01', 
        'tax_scheme_id' => 'OTH',   
        'created_at' => $currentDate,
        'updated_at' => $currentDate,
    ]);

    $totalItems = 0; $totalGrand = 0; $totalTaxRM = 0; $totalLineExt = 0;
    $itemIds = []; $rowIndex = 0;
    $customInvoiceNoFound = false;

    while (($row = fgetcsv($handle, 1000, ",")) !== false) {
        $rowIndex++;
        if ($rowIndex == 1 || empty($row[0])) continue; 

        $qty = (float)($row[1] ?? 0);
        $unitPrice = (float)($row[2] ?? 0);
        $desc = $row[3] ?? 'Imported Item';
        $discAmt = (float)($row[4] ?? 0);
        $taxRate = (float)($row[5] ?? 0);
        $csvInvoiceNo = isset($row[6]) ? trim($row[6]) : ''; 

        if (!empty($csvInvoiceNo) && !$customInvoiceNoFound) {
            $invoiceNoToSave = $csvInvoiceNo;
            $customInvoiceNoFound = true; 
            DB::table('consolidate_invoice')->where('id_invoice', $invoiceId)->update(['invoice_no' => $invoiceNoToSave]);
        }

        // --- CALCULATION LOGIC ---
        $priceExt = $qty * $unitPrice; 
        $lineExt = $priceExt - $discAmt; 
        if ($lineExt < 0) $lineExt = 0;

        // FIXED ROUNDING: Prevents long decimals in DB
        $taxRM = round($lineExt * ($taxRate / 100), 2);
        $itemTotal = $lineExt + $taxRM;

        // --- DATE PARSING FIX ---
        // Converts M/D/YYYY or other formats to MySQL Y-m-d
        try {
            $issueDate = !empty($row[0]) ? Carbon::parse($row[0])->format('Y-m-d') : $currentDate->format('Y-m-d');
        } catch (\Exception $e) {
            $issueDate = $currentDate->format('Y-m-d'); // Fallback to today if date is garbled
        }

        // 2. Create Items
        $itemId = DB::table('consolidate_invoice_item')->insertGetId([
            'unique_id' => (string) Str::uuid(),
            'id_consolidate_invoice' => $invoiceId,
            'connection_integrate' => $selected_connection,
            'id_developer' => $id_developer,
            'id_customer' => 6,
            'line_id' => $totalItems + 1,
            'is_import' => 1,
            'issue_date' => $issueDate,
            'invoiced_quantity' => $qty,
            'price_amount' => $unitPrice,
            'price_discount' => $discAmt,
            'tax' => $taxRM, 
            'price_extension_amount' => $priceExt,
            'line_extension_amount' => $lineExt,
            'item_description' => $desc,
            'item_clasification_value' => '004',
            'created_at' => $currentDate,
        ]);

        $totalItems++;
        $totalLineExt += $lineExt;
        $totalTaxRM += $taxRM;
        $totalGrand += $itemTotal;
        $itemIds[] = $itemId;
    }
    fclose($handle);

    // Update Header Totals with rounding
    DB::table('consolidate_invoice')->where('id_invoice', $invoiceId)->update([
        'consolidate_total_item' => $totalItems,
        'consolidate_complete_total' => round($totalGrand, 2),
        'consolidate_list_sale_item_id' => implode(',', $itemIds),
        'consolidate_total_amount_before' => round($totalLineExt, 2),
        'price' => round($totalGrand, 2),
        'taxable_amount' => round($totalLineExt, 2),
        'tax_amount' => round($totalTaxRM, 2),
        'updated_at' => now()
    ]);

    return redirect()->back()->with('success', "Batch imported successfully!");
}
     /**
     * Download Template (UPDATED)
     * - Removed 'Total Price'
     * - Added 'Invoice No (Optional)'
     */
    public function downloadTemplate()
    {
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=lhdn_template.csv",
        ];

        $columns = [
            'Issue Date', 
            'Qty', 
            'Unit Price', 
            'Description', 
            'Discount Amount', 
            'Tax Rate (%)', 
            'Invoice No (Optional)' 
        ];

        $example = [
            date('Y-m-d'), 
            '1', 
            '100.00', 
            'Service Item', 
            '10.00', 
            '6', 
            'INV-2024-001' 
        ];

        $callback = function() use($columns, $example) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, $example);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * SUBMIT SELECTED BATCHES TO LISTING INVOICES
     * (Conversion logic only - No API call)
     */
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
                'price' => round($chunk->sum('line_extension_amount') + $chunk->sum('tax'), 2),
                'taxable_amount' => round($chunk->sum('line_extension_amount'), 2), // FIXED: Tax should be based on Net (Line Extension)
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
                    'line_extension_amount' => round($item->line_extension_amount, 2),
                    'item_description' => $item->item_description,
                    'price_amount' => $item->price_amount,
                    'price_discount' => $item->price_discount,
                    'price_extension_amount' => $item->price_extension_amount,
                    'tax' => round($item->tax, 2),
                    'item_clasification_value' => '004', 
                    'is_import' => 1,
                    'created_at' => now()
                ]);
            }

            DB::table('consolidate_invoice_item')
                ->whereIn('id_invoice_item', $chunk->pluck('id_invoice_item'))
                ->update(['submition_status' => 'submitted', 'updated_at' => now()]);

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

        $priceExt = $qty * $unitPrice;
        $lineExt = $priceExt - $discount;
        if ($lineExt < 0) $lineExt = 0;

        // ROUNDING HERE FIXES THE 8.8634 PROBLEM
        $taxRM = round($lineExt * ($taxRate / 100), 2);

        DB::table('consolidate_invoice_item')->where('id_invoice_item', $id)->update([
            'item_description' => $request->description,
            'invoiced_quantity' => $qty,
            'price_amount' => $unitPrice,
            'price_discount' => $discount,
            'tax' => $taxRM, 
            'price_extension_amount' => $priceExt,
            'line_extension_amount' => $lineExt,
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
        $totalBeforeTax += (float)$i->line_extension_amount; // Sum of Net amounts
        $totalTax += (float)$i->tax; 
        $grandTotal += ((float)$i->line_extension_amount + (float)$i->tax);
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