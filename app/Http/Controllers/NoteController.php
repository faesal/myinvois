<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\eInvoisModel;

class NoteController extends Controller
{
    protected $noteCodes = [
        'credit' => '02',
        'debit' => '03',
        'refund' => '04',
    ];

    protected function getNoteTypeInfo()
    {
        $noteTypeSlug = request()->route('note_type'); // e.g. credit_note
        $noteType = str_replace('_note', '', $noteTypeSlug); // credit

        return [
            'type' => $noteType,
            'slug' => $noteTypeSlug,
            'code' => $this->noteCodes[$noteType] ?? '02',
        ];
    }

    public function listing(Request $request)
{
    $search = $request->input('search');
    $info = $this->getNoteTypeInfo();

    $query = DB::table('invoice')
        ->join('customer as c', 'invoice.id_customer', '=', 'c.id_customer')
        ->join('customer as s', 'invoice.id_supplier', '=', 's.id_customer')
        ->select(
            'invoice.*',
            'c.registration_name as customer_name',
            'c.email as customer_email',
            's.registration_name as supplier_name',
            's.tin_no as supplier_tin'
        )
        ->where('invoice.invoice_type_code', $info['code']);

    // Filter ikut peranan pengguna
    if (auth()->user()->role !== 'admin') {
        $query->where('invoice.connection_integrate', session('connection_integrate'));
    }

    // Carian
    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('invoice.invoice_no', 'like', "%$search%")
                ->orWhere('c.registration_name', 'like', "%$search%")
                ->orWhere('s.registration_name', 'like', "%$search%");
        });
    }

    // Dapatkan nota yang ditapis
    $notes = $query->orderBy('invoice.created_at', 'desc')->paginate(50);

    // Jumlah keseluruhan & dihantar
    $totalQuery = DB::table('invoice')->where('invoice_type_code', $info['code']);
    $submittedQuery = DB::table('invoice')
        ->where('invoice_type_code', $info['code'])
        ->where('submission_status', 'submitted');

    // Filter semula untuk bukan admin
    if (auth()->user()->role !== 'admin') {
        $totalQuery->where('connection_integrate', session('connection_integrate'));
        $submittedQuery->where('connection_integrate', session('connection_integrate'));
    }

    $total = $totalQuery->count();
    $submitted = $submittedQuery->count();

    return view('credit_debit_notes.listing', [
        'notes' => $notes,
        'total' => $total,
        'submitted' => $submitted,
        'search' => $search,
        'noteType' => $info['type'],
        'noteTypeSlug' => $info['slug']
    ]);
}


public function create(Request $request)
{
    $info = $this->getNoteTypeInfo();

    $query = DB::table('invoice')->where('invoice_type_code', '01');

    // Filter by role
    if (auth()->user()->role !== 'admin') {
        $query->where('connection_integrate', session('connection_integrate'));
    }

    $invoices = $query->get();

    return view('credit_debit_notes.create', [
        'invoices' => $invoices,
        'noteType' => $info['type'],
        'noteTypeSlug' => $info['slug']
    ]);
}


    public function fetchInvoiceItems($note_type, $id_invoice)
    {
        // $note_type akan terima 'credit_note', 'debit_note', atau 'refund_note'
        // $id_invoice adalah ID sebenar yang dihantar

        $invoice = DB::table('invoice')->where('id_invoice', $id_invoice)->first();

        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found.'], 404);
        }

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
        $info = $this->getNoteTypeInfo();

        $originalInvoiceId = $request->input('original_invoice_id');
        $totalAmount = $request->input('total_credit_note');
        $items = $request->input('items', []);
        $unique_id = Str::uuid();

        $original = DB::table('invoice')->where('id_invoice', $originalInvoiceId)->first();

        $newInvoiceId = DB::table('invoice')->insertGetId([
            'unique_id' => $unique_id,
            'connection_integrate' => session('connection_integrate'),
            'invoice_no' => strtoupper($info['type']) . '_NOTE-' . now()->format('YmdHis'),
            'invoice_type_code' => $info['code'],
            'issue_date' => now(),
            'price' => $totalAmount,
            'id_customer' => $original->id_customer,
            'id_supplier' => $original->id_supplier,
            'invoice_status' => $original->invoice_status,
            'previous_id_invoice' => $originalInvoiceId,
            'previous_invoice_no' => $original->invoice_no,
            'previous_uuid' => $original->uuid,
            'payment_note_term' => 'CASH',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($items as $item) {
            if (isset($item['id_invoice_item']) && isset($item['price'])) {
                DB::table('invoice_item')->insert([
                    'id_invoice' => $newInvoiceId,
                    'unique_id' => $unique_id,
                    'connection_integrate' => session('connection_integrate'),
                    'previous_id_invoice' => $originalInvoiceId,
                    'previous_id_invoice_item' => $item['id_invoice_item'],
                    'previous_amount' => DB::table('invoice_item')->where('id_invoice_item', $item['id_invoice_item'])->value('line_extension_amount'),
                    'line_id' => $item['id_invoice_item'],
                    'invoiced_quantity' => $item['qty'],
                    'price_amount' => $item['price'],
                    'tax' => $item['tax'],
                    'price_discount' => $item['discount'],
                    'line_extension_amount' => ($item['qty'] * $item['price']) - $item['discount'],
                    'price_extension_amount' => ($item['qty'] * $item['price']) - $item['discount'],
                    'item_description' => $item['description'],
                    'item_clasification_value' => $item['item_clasification_value'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        $recalculatedTotal = DB::table('invoice_item')->where('id_invoice', $newInvoiceId)->sum('line_extension_amount');
        $tax = DB::table('invoice_item')->where('id_invoice', $newInvoiceId)->sum('tax');

        DB::table('invoice')->where('id_invoice', $newInvoiceId)->update([
            'price' => $recalculatedTotal,
            'taxable_amount' => $recalculatedTotal,
            'tax_amount' => $tax,
            'updated_at' => now(),
        ]);

        $model = new eInvoisModel;
        session([
            'invoice_unique_id' => $unique_id,
            'previous_uuid' => $original->uuid,
            'previous_invoice_no' => $original->invoice_no,
            'invoice_type_code' => $info['code']
        ]);

        $model->submit($newInvoiceId);

        session()->forget(['invoice_unique_id', 'previous_uuid', 'previous_invoice_no', 'invoice_type_code']);

        return response()->json(['message' => ucfirst($info['type']) . ' Note submitted successfully.']);
    }
}
