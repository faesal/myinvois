<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\eInvoisModel;


class DebitNoteController extends Controller
{

    public function test_debit_note(){
       
        $invoice = new eInvoisModel;
       
        $originalInvoice = DB::table('invoice')->where('id_invoice', 22)->first();
        session(['invoice_unique_id' => $originalInvoice->unique_id]);
        session(['previous_uuid' => $originalInvoice->previous_uuid]);
        session(['previous_invoice_no' => $originalInvoice->previous_invoice_no]);
        session(['invoice_type_code' => $originalInvoice->invoice_type_code]);
        
        print_r( $originalInvoice);
        //session(['consolidate_status' => $originalInvoice->]);
        $result = $invoice->submit(22);
       
        session(['invoice_unique_id' => '']);
        session(['previous_uuid' => '']);
        session(['previous_invoice_no' => '']);
        session(['invoice_type_code' => '']);
        

        print_r($result);
    }

    public function listing(Request $request)
    {
        $search = $request->input('search');
    
        $query = DB::table('invoice')
            ->join('customer as c', 'invoice.id_customer', '=', 'c.id_customer')
            ->join('customer as s', 'invoice.id_supplier', '=', 's.id_customer') // alias 's' for supplier
            ->select(
                'invoice.*',
                'c.registration_name as customer_name',
                'c.email as customer_email',
                's.registration_name as supplier_name',
                's.tin_no as supplier_tin'
            )
            ->where('invoice.invoice_type_code', '03'); // Credit Note only
    
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice.invoice_no', 'like', "%$search%")
                  ->orWhere('c.registration_name', 'like', "%$search%")
                  ->orWhere('s.registration_name', 'like', "%$search%");
            });
        }
    
        $creditNotes = $query->orderBy('invoice.created_at', 'desc')->paginate(50);
    
        $total = DB::table('invoice')->where('invoice_type_code', '03')->count();
        $submitted = DB::table('invoice')
            ->where('invoice_type_code', '03')
            ->where('submission_status', 'submitted')
            ->count();
        $noteType='debit';
        return view('credit_debit_notes.listing', compact('creditNotes', 'total', 'submitted', 'search', 'noteType'));
    }
    

    public function create()
    {
        $invoices = DB::table('invoice')->where('invoice_type_code', '01')->get();
        $noteType='debit';
        return view('credit_debit_notes.create', compact('invoices','noteType'));
    }

    public function fetchInvoiceItems($id_invoice)
    {
        $invoice = DB::table('invoice')->where('id_invoice', $id_invoice)->first();
        $items = DB::table('invoice_item')->where('id_invoice', $id_invoice)->get();
    
        $customer = DB::table('customer')->where('id_customer', $invoice->id_customer)->first();
        $supplier = DB::table('customer')->where('id_customer', $invoice->id_supplier)->first();
    
        return response()->json([
            'invoice' => $invoice,
            'items' => $items,
            'customer' => $customer,
            'supplier' => $supplier,
        ]);
    }

    public function store(Request $request)
    {
        $originalInvoiceId = $request->input('original_invoice_id');
        $totalCreditNote = $request->input('total_credit_note');
   
        $items = $request->input('items', []);
        $unique_id=Str::uuid();
        // Create credit note invoice
        //echo DB::table('invoice')->where('id_invoice', $originalInvoiceId)->value('previous_uuid');
        $creditNoteId = DB::table('invoice')->insertGetId([
            'unique_id' => $unique_id,
            'invoice_no' => 'DEBIT_NOTE-' . now()->format('YmdHis'),
            'invoice_type_code' => '03', // Credit Note
            'issue_date' => now(),
            'price' => $totalCreditNote,
            'id_customer' => DB::table('invoice')->where('id_invoice', $originalInvoiceId)->value('id_customer'),
            'id_supplier' => DB::table('invoice')->where('id_invoice', $originalInvoiceId)->value('id_supplier'),
            'invoice_status' => DB::table('invoice')->where('id_invoice', $originalInvoiceId)->value('invoice_status'),
            'previous_id_invoice' => $originalInvoiceId,
            'previous_invoice_no' => DB::table('invoice')->where('id_invoice', $originalInvoiceId)->value('invoice_no'),
            'previous_uuid' => DB::table('invoice')->where('id_invoice', $originalInvoiceId)->value('uuid'),
            'payment_note_term'=>'CASH',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    
        print_r($items);
        // Insert selected items into credit note items
        foreach ($items as $item) {
            if (isset($item['id_invoice_item']) && isset($item['price'])) {
    
            DB::table('invoice_item')->insert([
                'id_invoice' => $creditNoteId,
                'unique_id' => $unique_id,
                'previous_id_invoice' => $originalInvoiceId,
                'previous_amount' => DB::table('invoice_item')->where('id_invoice_item', @$item['id_invoice_item'])->value('line_extension_amount'),
                'previous_id_invoice_item'=>@$item['id_invoice_item'],
                'line_id' => @$item['id_invoice_item'],
                'invoiced_quantity' => @$item['qty'],
                'price_amount' => @$item['price'],
                'tax' => @$item['tax'],
                'price_discount' => @$item['discount'],
                'line_extension_amount' => (@$item['qty'] * @$item['price']) - @$item['discount'],
                'price_extension_amount' => (@$item['qty'] * @$item['price']) - @$item['discount'],
                'item_description' => $item['description'],
                'item_clasification_value'=>$item['item_clasification_value'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        }

        // Kira jumlah semula dari item
        $recalculatedTotal = DB::table('invoice_item')
        ->where('id_invoice', $creditNoteId)
        ->sum('line_extension_amount');

        $tax = DB::table('invoice_item')
        ->where('id_invoice', $creditNoteId)
        ->sum('tax');

    
        // Kemas kini invoice
        DB::table('invoice')
        ->where('id_invoice', $creditNoteId)
        ->update([
            'price' => $recalculatedTotal,
            'taxable_amount' => $recalculatedTotal,
            'tax_amount' =>  $tax,
            'updated_at' => now(),
        ]);



        $invoice = new eInvoisModel;
       
        $originalInvoice = DB::table('invoice')->where('id_invoice', $creditNoteId)->first();
        session(['invoice_unique_id' => $originalInvoice->unique_id]);
        session(['previous_uuid' => $originalInvoice->previous_uuid]);
        session(['previous_invoice_no' => $originalInvoice->previous_invoice_no]);
        session(['invoice_type_code' => $originalInvoice->invoice_type_code]);
        
  
        //session(['consolidate_status' => $originalInvoice->]);
        $result = $invoice->submit($creditNoteId);
        session(['invoice_unique_id' =>'']);
        session(['previous_uuid' => '']);
        session(['previous_invoice_no' => '']);
        session(['invoice_type_code' => '']);
        
    
        return response()->json(['message' => 'Credit Note submitted successfully.']);
    }
}
