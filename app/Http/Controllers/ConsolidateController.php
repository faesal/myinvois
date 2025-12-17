<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use App\Mail\DailyConsolidateSummary;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

use App\Models\eInvoisModel;

class ConsolidateController extends Controller
{


public function monthlyConsolidateToInvoice()
{
    $start = now()->startOfMonth()->format('Y-m-d');
    $end = now()->endOfMonth()->format('Y-m-d');

    $connections = DB::table('consolidate_invoice')
        ->select('connection_integrate')
        ->whereNotNull('secret_key1')
        ->whereBetween('issue_date', [$start, $end])
        ->distinct()
        ->pluck('connection_integrate');

    foreach ($connections as $conn) {
        // Check if connection exists in customer table and has secret_key1
        $customer = DB::table('customer')
            ->where('connection_integrate', $conn)
            ->whereNotNull('secret_key1')
            ->first();

        if (!$customer) continue; // Skip if not authorized

        // Set session for this connection
        Session::put('connection_integrate', $conn);

        // Get consolidate records
        $consolidates = DB::table('consolidate_invoice')
            ->where('connection_integrate', $conn)
            ->whereBetween('issue_date', [$start, $end])
            ->get();

        $items = DB::table('consolidate_invoice_item')
            ->join('consolidate_invoice', 'consolidate_invoice.id_invoice', '=', 'consolidate_invoice_item.id_consolidate_invoice')
            ->where('consolidate_invoice.connection_integrate', $conn)
            ->whereNot('submition_status','=','submitted')
            ->whereBetween('consolidate_invoice.issue_date', [$start, $end])
            ->select('consolidate_invoice_item.*')
            ->get();

        if ($consolidates->isEmpty() || $items->isEmpty()) continue;

        // Split into chunks of 25
        $chunks = $items->chunk(25);
        $chunkIndex = 1;

        foreach ($chunks as $chunk) {
            $totalBefore = $chunk->sum('price_extension_amount') - $chunk->sum('price_discount');
            $totalAfter = $chunk->sum('line_extension_amount');
            $uniqueId = Str::uuid();
            $invoiceNo = 'CONSOLIDATE-' . strtoupper($conn) . '-' . now()->format('Ym') . '-V' . $chunkIndex++;

            $invoiceId = DB::table('invoice')->insertGetId([
                'unique_id' => $uniqueId,
                'sale_id_integrate' => null,
                'connection_integrate' => $conn,
                'invoice_status' => 'monthly',
                'invoice_no' => $invoiceNo,
                'invoice_type_code' => '01',
                'issue_date' => $end,
                'price' => $totalAfter,
                'taxable_amount' => $totalBefore,
                'tax_amount' => $totalAfter - $totalBefore,
                'tax_percent' => '6',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($chunk as $index => $item) {
                DB::table('invoice_item')->insert([
                    'unique_id' => $uniqueId,
                    'issue_date' => $item->issue_date,
                    'connection_integrate' => $item->connection_integrate,
                    'sale_id_integrate' => $item->sale_id_integrate,
                    'id_consolidate_invoice' => $item->id_consolidate_invoice,
                    'line_id' => $index + 1,
                    'invoiced_quantity' => $item->invoiced_quantity,
                    'line_extension_amount' => $item->line_extension_amount,
                    'item_description' => $item->item_description,
                    'price_amount' => $item->price_amount,
                    'price_discount' => $item->price_discount,
                    'price_extension_amount' => $item->price_extension_amount,
                    'item_clasification_value' => $item->item_clasification_value,
                    'created_at' => now(),
                    'submition_status'=>'submitted'
                ]);
            }

            $invoice = new eInvoisModel;
            $invoice->submit($invoiceId);

            // Trigger e-Invoice submission here if needed
            // dispatch(new SubmitInvoiceJob($invoiceId)); // optional queue job
        }
    }

    return response()->json(['status' => 'Monthly invoices created and submitted where applicable']);
}


    public function compare(Request $request)
    {
        $date = $request->input('date') ?? Carbon::yesterday()->format('Y-m-d');
        $connections = explode(',', env('INTEGRATE_POS_CONNECTIONS'));
        $results = [];
    
        foreach ($connections as $connection) {
            $config = [
                'driver' => 'mysql',
                'host' => env('DB_' . strtoupper($connection) . '_HOST'),
                'database' => env('DB_' . strtoupper($connection) . '_DATABASE'),
                'username' => env('DB_' . strtoupper($connection) . '_USERNAME'),
                'password' => env('DB_' . strtoupper($connection) . '_PASSWORD'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
            ];
    
            Config::set("database.connections.$connection", $config);
    
            try {
                $salesItems = DB::connection($connection)->table('phppos_sales_items')
                    ->join('phppos_sales', 'phppos_sales.sale_id', '=', 'phppos_sales_items.sale_id')
                    ->join('phppos_items', 'phppos_items.item_id', '=', 'phppos_sales_items.item_id')
                    ->whereDate('phppos_sales.sale_time', $date)
                    ->select(
                        'phppos_sales_items.sale_id',
                        'phppos_sales.sale_time',
                        'phppos_items.name as item_name',
                        'phppos_sales_items.quantity_purchased',
                        'phppos_sales_items.subtotal'
                    )
                    ->where('phppos_sales.deleted', 0)
                    ->get();
    
                DB::setDefaultConnection('mysql');
    
                $consolidatedItems = DB::table('consolidate_invoice_item')
                    ->join('consolidate_invoice', 'consolidate_invoice.id_invoice', '=', 'consolidate_invoice_item.id_consolidate_invoice')
                    ->where('consolidate_invoice.connection_integrate', $connection)
                    ->whereDate('consolidate_invoice.issue_date', $date)
                    ->select(
                        'consolidate_invoice_item.sale_id_integrate as sale_id',
                        'consolidate_invoice_item.issue_date',
                        'consolidate_invoice_item.item_description as item_name',
                        'consolidate_invoice_item.invoiced_quantity as quantity_purchased',
                        'consolidate_invoice_item.line_extension_amount as subtotal'
                    )
                    ->get();
    
                $salesKeyed = $salesItems->map(fn($row) => [
                    'sale_id' => $row->sale_id,
                    'item_name' => $row->item_name,
                    'quantity_purchased' => $row->quantity_purchased,
                    'subtotal' => $row->subtotal,
                ]);
    
                $consolidatedKeyed = $consolidatedItems->map(fn($row) => [
                    'sale_id' => $row->sale_id,
                    'item_name' => $row->item_name,
                    'quantity_purchased' => $row->quantity_purchased,
                    'subtotal' => $row->subtotal,
                ]);
    
                $diffSales = $salesKeyed->filter(fn($item) => !$consolidatedKeyed->contains($item));
                $diffConsolidated = $consolidatedKeyed->filter(fn($item) => !$salesKeyed->contains($item));
    
                $results[$connection] = [
                    'sales_diff' => $diffSales,
                    'consolidated_diff' => $diffConsolidated,
                ];
            } catch (\Throwable $e) {
                Log::error("[Compare] Failed on connection $connection: " . $e->getMessage());
                $results[$connection] = [
                    'sales_diff' => collect(),
                    'consolidated_diff' => collect(),
                    'error' => $e->getMessage()
                ];
            }
        }
    
        return view('consolidate.compare', compact('results', 'date'));
    }

    public function pullFromConnections(Request $request)
    {

    abort_unless($request->token === env('CRON_ACCESS_TOKEN'), 403);
    $connectionKeys = explode(',', env('INTEGRATE_POS_CONNECTIONS'));
    $date = \Carbon\Carbon::yesterday()->format('Y-m-d');
    //$date = '2025-06-17';
    
    foreach ($connectionKeys as $key) {
        $config = [
            'host' => env('DB_' . strtoupper($key) . '_HOST'),
            'database' => env('DB_' . strtoupper($key) . '_DATABASE'),
            'username' => env('DB_' . strtoupper($key) . '_USERNAME'),
            'password' => env('DB_' . strtoupper($key) . '_PASSWORD'),
        ];

        try {
            Config::set("database.connections.$key", array_merge(
                config('database.connections.mysql'), $config
            ));

            DB::setDefaultConnection($key);

            $sales = DB::connection($key)->table('phppos_sales')
                ->whereDate('sale_time', $date)
                ->where('deleted', 0)
                ->get();
            $totalBefore = $sales->sum('subtotal');


            $totalItems = DB::connection($key)
            ->table('phppos_sales_items')
            ->join('phppos_sales', 'phppos_sales_items.sale_id', '=', 'phppos_sales.sale_id')
            ->whereDate('phppos_sales.sale_time', $date)
            ->where('deleted', 0)
            ->count();

            
              
            foreach ($sales as $sale) {
                $items = DB::connection($key)->table('phppos_sales_items')
                    ->join('phppos_items', 'phppos_sales_items.item_id', '=', 'phppos_items.item_id')
                    ->join('phppos_sales', 'phppos_sales_items.sale_id', '=', 'phppos_sales.sale_id')
                    ->where('phppos_sales.sale_id', $sale->sale_id)
                    ->whereDate('phppos_sales.sale_time', $date)
                    ->select('phppos_sales_items.*','phppos_items.*')
                    ->get();

           

                $this->pushToMainInvoice($sale, $key, $items, $totalBefore,$totalItems);
            }
        } catch (\Throwable $e) {
            Log::error("[Consolidate] Failed to pull from $key: " . $e->getMessage());
            Mail::raw("[ERROR] Consolidation failed for $key: {$e->getMessage()}", function ($message) use ($key) {
                $message->to('faesal09@gmail.com')
                        ->subject("[ERROR] Consolidation Failed: $key");
            });
        }
    }

    $this->sendDailySummary($date);
    $this->detectTallyMismatch($date);
    }

    public function pushToMainInvoice($sale, $connection, $items, $totalBefore,$totalItems)
    {
        try {
            DB::setDefaultConnection('mysql');
    
            $saleDate = Carbon::parse($sale->sale_time)->format('Y-m-d');
            $uniqueId = Str::uuid();
    
            $saleId = $sale->sale_id;
    
            // Check if invoice exists
            $existingInvoice = DB::table('consolidate_invoice')
                ->where('connection_integrate', $connection)
                ->whereDate('issue_date', $saleDate)
                ->first();
    
            if ($existingInvoice) {
                $invoiceId = $existingInvoice->id_invoice;
                $uniqueId = $existingInvoice->unique_id;
    
                // Merge & update sale_id list
                $existingSaleIds = explode(',', $existingInvoice->consolidate_list_sale_item_id);
                if (!in_array($saleId, $existingSaleIds)) {
                    $existingSaleIds[] = $saleId;
                }
    
                DB::table('consolidate_invoice')->where('id_invoice', $invoiceId)->update([
                    'consolidate_total_item' => $totalItems,
                    'consolidate_total_amount_before' => $totalBefore,
                    'consolidate_list_sale_item_id' => implode(',', $existingSaleIds),
                    'updated_at' => now(),
                ]);


                foreach ($items as $index => $item) {
                    //20*10-176
                    if($item->discount_percent!='')
                    $discount_amount=($item->item_unit_price*$item->quantity_purchased)-$item->subtotal;
                    else
                    $discount_amount=0;

                    DB::table('consolidate_invoice_item')->insert([
                        'connection_integrate' => $connection,
                        'sale_id_integrate' => $sale->sale_id,
                        'issue_date' => $sale->sale_time,
                        'unique_id' => $uniqueId,
                        'id_consolidate_invoice' => $invoiceId,
                        'line_id' => $index + 1,
                        'invoiced_quantity' => $item->quantity_purchased,
                        'line_extension_amount' => $item->subtotal,
                        'item_description' => $item->name,
                        'price_amount' => $item->item_unit_price,
                        'price_discount' => $discount_amount,
                        'price_extension_amount' => $item->item_unit_price*$item->quantity_purchased,
                        'item_clasification_value' => '004',
                    ]);
                
                }
            } else {
                $invoiceId = DB::table('consolidate_invoice')->insertGetId([
                    'unique_id' => $uniqueId,
                    'consolidate_date' => $saleDate,
                    'payment_note_term'=> "Cash",
                    'consolidate_total_item' => count($items),
                    'consolidate_list_sale_item_id' => $saleId, // new sale
                    'consolidate_complete_status' => 'completed',
                    'consolidate_total_amount_before' => $totalBefore,
                    'connection_integrate' => $connection,
                    'invoice_status' => 'consolidated',
                    
                    'issue_date' => $sale->sale_time,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);


                foreach ($items as $index => $item) {
               
                     //20*10-176
                     if($item->discount_percent!='')
                     $discount_amount=($item->item_unit_price*$item->quantity_purchased)-$item->subtotal;
                     else
                     $discount_amount=0;

                    DB::table('consolidate_invoice_item')->insert([


                        'connection_integrate' => $connection,
                        'sale_id_integrate' => $sale->sale_id,
                        'issue_date' => $sale->sale_time,
                        'unique_id' => $uniqueId,
                        'id_consolidate_invoice' => $invoiceId,
                        'line_id' => $index + 1,
                        'invoiced_quantity' => $item->quantity_purchased,
                        'line_extension_amount' => $item->subtotal,
                        'item_description' => $item->name,
                        'price_amount' => $item->item_unit_price,
                        'price_discount' => $discount_amount,
                        'price_extension_amount' => $item->item_unit_price*$item->quantity_purchased,
                        'item_clasification_value' => '004',
                    ]);
                
            }
    

            }
    
            
            // Update total_after
            $totalAfter = DB::table('consolidate_invoice_item')
                ->where('id_consolidate_invoice', $invoiceId)
                ->sum('line_extension_amount');
    
            DB::table('consolidate_invoice')
                ->where('id_invoice', $invoiceId)
                ->update(['consolidate_total_amount_after' => $totalAfter]);

            // Kira semula jumlah item berdasarkan connection dan issue_date
            $totalItems = DB::table('consolidate_invoice_item')
            ->join('consolidate_invoice', 'consolidate_invoice_item.id_consolidate_invoice', '=', 'consolidate_invoice.id_invoice')
            ->where('consolidate_invoice.connection_integrate', $connection)
            ->whereDate('consolidate_invoice.issue_date', $saleDate)
            ->count();

            DB::table('consolidate_invoice')
            ->where('id_invoice', $invoiceId)
            ->update([
                'consolidate_complete_total' => $totalItems,
            ]);
    
            $totalTax = DB::table('consolidate_invoice_item')
            ->where('id_consolidate_invoice', $invoiceId)
            ->sum('item_tax_amount');

            DB::table('consolidate_invoice')
            ->where('id_invoice', $invoiceId)
            ->update([
                'consolidate_total_tax' => $totalTax,
            ]);

        } catch (\Throwable $e) {
            Log::error("[Consolidate] Failed to insert/update sale_id {$sale->sale_id} on connection {$connection}: " . $e->getMessage());
        }
    }
    

    public function sendDailySummary($date)
    {
        DB::setDefaultConnection('mysql');
        $summary = DB::table('consolidate_invoice')
            ->select(
                'connection_integrate',
                'consolidate_total_item',
                'consolidate_total_amount_before',
                'consolidate_total_amount_after',
                DB::raw('DATE(issue_date) as tarikh')
            )
            ->whereDate('issue_date', $date)
            
            ->get();

        if ($summary->isNotEmpty()) {
            $subject = '📊 Consolidate Summary for ' . $date;

            Mail::to('faesal09@gmail.com')->send(new DailyConsolidateSummary($summary, $subject));          
        }
    }

public function detectTallyMismatch($date)
{
    DB::setDefaultConnection('mysql');
    $mismatch = DB::table('consolidate_invoice')
        ->select(
            'issue_date',
            'consolidate_complete_total',
            'connection_integrate',
            'consolidate_total_item',
            'consolidate_total_amount_before',
            'consolidate_total_amount_after',
            DB::raw('DATE(issue_date) as tarikh')
        )
        ->whereDate('issue_date', $date)
        ->where(function ($query) {
            $query->whereColumn('consolidate_total_item', '!=', 'consolidate_complete_total')
                ->orWhereColumn('consolidate_total_amount_before', '!=', 'consolidate_total_amount_after');
        })
        ->get();

    if ($mismatch->isNotEmpty()) {
        $html = "<h2>⚠️ NOT TALLY DATA DETECTED (" . now()->format('Y-m-d H:i:s') . ")</h2>";
        $html .= "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                    <thead style='background-color: #f2f2f2;'>
                        <tr>
                            <th>Connection</th>
                            <th>Date</th>
                            <th>Total Item</th>
                            <th>Complete Total</th>
                            <th>Amount Before (RM)</th>
                            <th>Amount After (RM)</th>
                        </tr>
                    </thead>
                    <tbody>";

        foreach ($mismatch as $row) {
            $html .= "<tr>
                        <td>{$row->connection_integrate}</td>
                        <td>" . \Carbon\Carbon::parse($row->issue_date)->format('d/m/Y') . "</td>
                        <td>{$row->consolidate_total_item}</td>
                        <td>{$row->consolidate_complete_total}</td>
                        <td>" . number_format($row->consolidate_total_amount_before, 2) . "</td>
                        <td>" . number_format($row->consolidate_total_amount_after, 2) . "</td>
                      </tr>";
        }

        $html .= "</tbody></table>";

        // ✅ FIX HERE
        Mail::html($html, function ($message) {
            $message->to('faesal09@gmail.com')
                    ->subject('[WARNING] NOT TALLY - Consolidate Invoice Detected');
        });
    }
}


}
