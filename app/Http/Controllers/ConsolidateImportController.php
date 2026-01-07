<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ConsolidateImportController extends Controller
{
    /**
     * Display the import interface
     */
    public function index()
    {
        // Fetch existing consolidated batches to show in the table
        $consolidations = DB::table('consolidate_invoice')
            ->where('invoice_status', 'consolidated')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('consolidate.index', compact('consolidations'));
    }

    /**
     * Handle the CSV Batch Import (No Library Required)
     * Matches route: Route::post('/import', [..., 'importBatch'])
     */
    public function importBatch(Request $request)
    {
        // 1. Validate file is CSV
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $selected_connection = session('connection_integrate');
        $id_developer = session('id_developer');

        if (!$selected_connection) {
            return redirect()->back()->with('error', 'No active connection found. Please select a connection first.');
        }

        // 2. Open the file directly
        $file = $request->file('file');
        
        // Open file for reading
        if (($handle = fopen($file->getRealPath(), 'r')) === false) {
            return redirect()->back()->with('error', 'Could not open the file.');
        }

        // 3. Create Parent Record (The Batch Header)
        $batchUniqueId = (string) Str::uuid();
        $currentDate = Carbon::now();
        
        $invoiceId = DB::table('consolidate_invoice')->insertGetId([
            'unique_id' => $batchUniqueId,
            'invoice_no' => 'CONSO-' . $currentDate->format('YmdHis'),
            'connection_integrate' => $selected_connection,
            'id_developer' => $id_developer,
            'id_customer' => 6, // Default customer ID based on your snippet
            'invoice_status' => 'consolidated',
            'is_import' => 1,
            'created_at' => $currentDate,
            'updated_at' => $currentDate,
        ]);

        $totalItems = 0;
        $totalAmount = 0;
        $totalTax = 0;
        $totalTaxable = 0;
        $itemIds = [];
        $rowIndex = 0;

        // 4. Loop through CSV rows
        while (($row = fgetcsv($handle, 1000, ",")) !== false) {
            $rowIndex++;

            // Skip Header Row (Row 1)
            if ($rowIndex == 1) continue; 
            
            // Skip empty rows
            if (empty($row[0])) continue;

            // Map Columns 
            // CSV Format: Issue Date, Qty, Line Amount, Description, Unit Price, Discount, Taxable Amount, Tax Amount
            $issueDate = $row[0] ?? $currentDate->format('Y-m-d');
            $qty = $row[1] ?? 0;
            $lineAmount = (float)($row[2] ?? 0);
            $desc = $row[3] ?? 'Imported Item';
            $priceAmt = (float)($row[4] ?? 0);
            $priceDisc = (float)($row[5] ?? 0);
            $priceExt = (float)($row[6] ?? 0);
            $taxAmount = (float)($row[7] ?? 0);

            $itemId = DB::table('consolidate_invoice_item')->insertGetId([
                'unique_id' => $batchUniqueId,
                'id_consolidate_invoice' => $invoiceId,
                'connection_integrate' => $selected_connection,
                'id_developer' => $id_developer,
                'id_customer' => 6,
                'line_id' => $totalItems + 1,
                'item_id_integrate' => $totalItems + 1,
                'is_import' => 1,
                'item_clasification_value' => '004', // Default Classification
                
                'issue_date' => $issueDate,
                'invoiced_quantity' => $qty,
                'line_extension_amount' => $lineAmount,
                'item_description' => $desc,
                'price_amount' => $priceAmt,
                'price_discount' => $priceDisc,
                'price_extension_amount' => $priceExt,
                'tax' => $taxAmount,
                'created_at' => $currentDate,
            ]);

            $totalItems++;
            $totalAmount += $lineAmount;
            $totalTax += $taxAmount;
            $totalTaxable += $priceExt;
            $itemIds[] = $itemId;
        }

        fclose($handle);

        // 5. Update Parent Totals
        DB::table('consolidate_invoice')
            ->where('id_invoice', $invoiceId)
            ->update([
                'consolidate_total_item' => $totalItems,
                'consolidate_complete_total' => $totalAmount,
                'consolidate_list_sale_item_id' => implode(',', $itemIds),
                'consolidate_total_amount_before' => $totalAmount,
                'consolidate_total_amount_after' => $totalAmount,
                'price' => $totalAmount,
                'tax_amount' => $totalTax,
                'taxable_amount' => $totalTaxable,
                'updated_at' => now()
            ]);

        return redirect()->back()->with('success', "Batch imported successfully! ($totalItems items processed)");
    }

    /**
     * Download CSV Template
     */
    public function downloadTemplate()
    {
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=consolidate_template.csv",
        ];

        // Column Headers
        $columns = ['Issue Date', 'Qty', 'Line Amount', 'Description', 'Unit Price', 'Discount', 'Taxable Amount', 'Tax Amount'];

        // Example Data Row
        $example = [date('Y-m-d'), '1', '100.00', 'Service Fee', '100.00', '0', '100.00', '0'];

        $callback = function() use($columns, $example) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, $example); // Add example row
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Delete the batch and all its items
     */
    public function destroy($id)
    {
        // 1. Delete the children items first
        DB::table('consolidate_invoice_item')->where('id_consolidate_invoice', $id)->delete();
        
        // 2. Delete the parent batch
        DB::table('consolidate_invoice')->where('id_invoice', $id)->delete();

        return redirect()->back()->with('success', 'Batch and items deleted successfully.');
    }

    /**
     * Update the batch data (Simple Edit)
     */
   public function update(Request $request, $id)
{
    // Validate that the date is in the correct format
    $request->validate([
        'created_at' => 'required|date_format:Y-m-d',
        'amount' => 'required|numeric',
    ]);

    // Update the parent table
    DB::table('consolidate_invoice')->where('id_invoice', $id)->update([
        'invoice_no' => $request->invoice_no,
        'unique_id'  => $request->unique_id,
        'consolidate_complete_total' => $request->amount,
        'price' => $request->amount, // Usually price matches total
        'created_at' => $request->created_at . ' ' . date('H:i:s'), // Keep current time, change date
        'updated_at' => now(),
    ]);

    // Also update the unique_id in the items table so they stay linked!
    DB::table('consolidate_invoice_item')
        ->where('id_consolidate_invoice', $id)
        ->update(['unique_id' => $request->unique_id]);

    return redirect()->back()->with('success', 'Batch #' . $request->invoice_no . ' updated successfully.');
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
        $qty = $request->qty;
        $unitPrice = $request->price;
        $lineAmount = $qty * $unitPrice;

        // 1. Update the Item row using the correct Primary Key
        DB::table('consolidate_invoice_item')
            ->where('id_invoice_item', $id)
            ->update([
                'item_description'      => $request->description,
                'invoiced_quantity'     => $qty,
                'price_amount'          => $unitPrice,
                'line_extension_amount' => $lineAmount,
                'price_extension_amount' => $lineAmount,
                'updated_at'            => now()
            ]);

        // 2. Find the Parent Invoice ID
        $item = DB::table('consolidate_invoice_item')->where('id_invoice_item', $id)->first();
        $parentId = $item->id_consolidate_invoice;

        // 3. Recalculate Totals for the entire Invoice
        $subtotal = DB::table('consolidate_invoice_item')
            ->where('id_consolidate_invoice', $parentId)
            ->sum('line_extension_amount');

        $tax = $subtotal * 0.10; // 10% Tax
        $grandTotal = $subtotal + $tax;

        // 4. Update the Parent table (consolidate_invoice)
        DB::table('consolidate_invoice')
            ->where('id_invoice', $parentId)
            ->update([
                'consolidate_total_amount_before' => $subtotal,
                'tax_amount'                      => $tax,
                'consolidate_complete_total'      => $grandTotal,
                'price'                           => $grandTotal,
                'updated_at'                      => now()
            ]);

        return response()->json([
            'success' => true,
            'new_subtotal' => number_format($subtotal, 2),
            'new_tax'      => number_format($tax, 2),
            'new_total'    => number_format($grandTotal, 2)
        ]);

    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}
public function addItem($invoice_id)
{
    // Create a blank row in the database first to get an ID
    $newItemId = DB::table('consolidate_invoice_item')->insertGetId([
        'id_consolidate_invoice' => $invoice_id,
        'item_description' => '',
        'invoiced_quantity' => 0,
        'price_amount' => 0,
        'line_extension_amount' => 0,
        'is_import' => 1,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    return response()->json([
        'success' => true,
        'id' => $newItemId
    ]);
}
public function deleteItem($id)
{
    try {
        // 1. Find the item to get the Parent ID (id_consolidate_invoice) before it's gone
        $item = DB::table('consolidate_invoice_item')->where('id_invoice_item', $id)->first();
        
        if (!$item) {
            return response()->json(['success' => false, 'error' => 'Item not found.']);
        }

        $parentId = $item->id_consolidate_invoice;

        // 2. Delete the specific item
        DB::table('consolidate_invoice_item')->where('id_invoice_item', $id)->delete();

        // 3. AUTO-CALCULATE: Sum up all remaining items for this parent
        $subtotal = DB::table('consolidate_invoice_item')
            ->where('id_consolidate_invoice', $parentId)
            ->sum('line_extension_amount') ?: 0;

        $tax = $subtotal * 0.10; // 10% Tax calculation
        $grandTotal = $subtotal + $tax;

        // 4. Update the Parent Invoice table so the database is always correct
        DB::table('consolidate_invoice')
            ->where('id_invoice', $parentId)
            ->update([
                'consolidate_total_amount_before' => $subtotal,
                'tax_amount'                      => $tax,
                'consolidate_complete_total'      => $grandTotal,
                'price'                           => $grandTotal,
                'updated_at'                      => now()
            ]);

        // 5. Send the new numbers back to the webpage
        return response()->json([
            'success' => true,
            'new_subtotal' => number_format($subtotal, 2),
            'new_tax'      => number_format($tax, 2),
            'new_total'    => number_format($grandTotal, 2)
        ]);

    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}
}

