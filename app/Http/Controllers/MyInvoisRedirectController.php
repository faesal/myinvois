<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MyInvoisRedirectController extends Controller
{
    public function process($unique_id)
    {

        // 1. Get consolidated invoice header
        $con = DB::table('consolidate_invoice')
            ->where('unique_id', $unique_id)
            ->first();

        if (!$con) {
            return abort(404, "No consolidated invoice found with unique_id: {$unique_id}");
        }

        // 2. Get supplier from connection_integrate
        $supplier = DB::table('connection_integrate')
            ->where('code', $con->connection_integrate)
            ->first();

        $id_supplier = DB::table('customer')
            ->where('connection_integrate', $supplier->code)
            ->value('id_customer');

        if (!$id_supplier) {
            return response()->json(['error' => 'Supplier not found for POS'], 404);
        }

        session(['id_supplier' => $id_supplier]);
        session(['connection_integrate' => $con->connection_integrate]);


        // 3. Insert INTO invoice table
        $invoiceId = DB::table('invoice')->insertGetId([
            'unique_id'             => $con->unique_id,
            'sale_id_integrate'     => $con->sale_id_integrate,
            'connection_integrate'  => $con->connection_integrate,
            'id_customer'           => $con->id_customer,
            'id_supplier'           => $id_supplier,
            'id_developer'          => $supplier->id_developer,    
            'invoice_status'        => $con->invoice_status,
            'invoice_no'            => $con->invoice_no,
            'invoice_type_code'     => '01',
            'issue_date'            => $con->issue_date,
            
            'price'                 => $con->consolidate_total_amount_after,
            
            'taxable_amount'        => $con->taxable_amount?: "0.00",
            'tax_amount'            => $con->tax_amount?: "0.00",
            'tax_category_id'       => $con->tax_category_id ,
            'tax_exemption_reason'  => $con->tax_exemption_reason,
            'tax_scheme_id'         => 'OTH',
            'tax_percent'           => $con->tax_percent?: "0.00",

            'payment_note_term'     => $con->payment_note_term,
            'payment_financial_account' => $con->payment_financial_account,
            'payment_method'        => $con->payment_method,
            

            'uuid'                  => $con->uuid,
            'submission_uuid'       => $con->submission_uuid,

            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        // 4. Load associated items from consolidate_invoice_item
        $items = DB::table('consolidate_invoice_item')
            ->where('unique_id', $unique_id)
            ->get();

        // 5. Insert all invoice items
        foreach ($items as $item) {
            DB::table('invoice_item')->insert([
                'unique_id'              => $unique_id,
                'id_invoice'             => $invoiceId,
                'sale_id_integrate'      => $item->sale_id_integrate,
                'connection_integrate'   => $item->connection_integrate,
                'issue_date'             => $item->issue_date,
                'id_customer'            => $item->id_customer,
                'id_developer'          =>  $supplier->id_developer, 
                'line_id'                => $item->sorting_id ?? $item->line_id,
                'invoiced_quantity'      => $item->invoiced_quantity,
                'line_extension_amount'  => $item->line_extension_amount,
                'item_description'       => $item->item_description,
                'price_amount'           => $item->price_amount,
                'price_discount'         => $item->price_discount,
                'price_extension_amount' => $item->price_extension_amount,
                'item_clasification_value' => '036',
                'tax'                    => '0.00',
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
        }

        // 6. Redirect to existing customer creation handler
        return redirect()->to("/createcustomer/{$unique_id}");
    }
}
