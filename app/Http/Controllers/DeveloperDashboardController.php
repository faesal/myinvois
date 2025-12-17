<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeveloperDashboardController extends Controller
{
    public function index()
    {
        $developerId = auth()->id();

        // -----------------------------
        // KPI COUNTS
        // -----------------------------
        $totalClients = DB::table('customer')
            ->where('id_developer', $developerId)
            ->where('customer_type', 'SUPPLIER')
            ->whereNull('deleted')
            ->count();

        $activeIntegrations = DB::table('customer')
            ->where('id_developer', $developerId)
            ->where('is_activation', 1)
            ->where('customer_type', 'SUPPLIER')
            ->whereNull('deleted')
            ->count();

        // TOTAL API CALLS (from consolidate_invoice)
        $totalApiCalls = DB::table('consolidate_invoice')
            ->where('id_developer', $developerId)
            ->count();

        // -----------------------------
        // 30-DAY INVOICE BAR CHART DATA
        // -----------------------------
        $fromDate = now()->subDays(29)->startOfDay();

        $rawRows = DB::table('invoice')
            ->join('customer', 'customer.id_customer', '=', 'invoice.id_customer')
            ->select(
                DB::raw('DATE(invoice.created_at) as day'),
                DB::raw('COUNT(*) as total')
            )
            ->where('customer.id_developer', $developerId)
            
            ->where('invoice.created_at', '>=', $fromDate)
            ->groupBy('day')
            ->orderBy('day', 'ASC')
            ->get();

        // Map for quick lookup: 'YYYY-MM-DD' => total
        $dayMap = $rawRows->pluck('total', 'day');

        $invoiceLabels = [];
        $invoiceCounts = [];

        // Build continuous 30-day window (including today)
        for ($i = 0; $i < 30; $i++) {
            $date = now()->subDays(29 - $i)->toDateString();
            $invoiceLabels[] = $date;
            $invoiceCounts[] = (int) ($dayMap[$date] ?? 0);
        }

        // -----------------------------
        // CLIENT SUMMARY TABLE
        // -----------------------------
        $clients = DB::table('customer')
            ->leftJoin('invoice', 'invoice.id_customer', '=', 'customer.id_customer')
            ->leftJoin('consolidate_invoice', 'consolidate_invoice.id_customer', '=', 'customer.id_customer')
            ->select(
                'customer.*',
                DB::raw('COUNT(invoice.id_invoice) AS invoice_count'),
                DB::raw('MAX(invoice.updated_at) AS last_sync')
            )
            ->where('customer.id_developer', $developerId)
            ->where('customer_type', 'SUPPLIER')
            ->whereNull('customer.deleted')
            ->groupBy('customer.id_customer')
            ->get();


        foreach ($clients as $c) {
            // Count LHDN keys
            $keyCount = 0;
            if (!empty($c->secret_key1)) $keyCount++;
            if (!empty($c->secret_key2)) $keyCount++;
            if (!empty($c->secret_key3)) $keyCount++;
            $c->keyCount = $keyCount;

        // Subscription expiry calculation
        if ($c->end_subscribe) {

            $today = Carbon::today();
            $end   = Carbon::parse($c->end_subscribe)->startOfDay();

            if ($end->isPast()) {
                // Subscription already expired
                $diff = $end->diffInDays($today);
                $c->expires_in = "Expired {$diff} day" . ($diff > 1 ? 's' : '') . " ago";
            } elseif ($end->isToday()) {
                // Ends today
                $c->expires_in = "Ends today";
            } else {
                // Subscription still active
                $months = $today->diffInMonths($end);
                $days   = $today->copy()->addMonths($months)->diffInDays($end);

                if ($months > 0 && $days > 0) {
                    $c->expires_in = "{$months} month" . ($months > 1 ? 's' : '') . " {$days} day" . ($days > 1 ? 's' : '');
                } elseif ($months > 0) {
                    $c->expires_in = "{$months} month" . ($months > 1 ? 's' : '');
                } else {
                    $c->expires_in = "{$days} day" . ($days > 1 ? 's' : '');
                }
            }

        } else {
            $c->expires_in = "Unknown";
        }

        }
        // -----------------------------
        // PREPARE EXPORT DATA (for DataTables Excel)
        // -----------------------------
        $exportClients = [];

        foreach ($clients as $c) {
            $exportClients[] = [
                'registration_name' => $c->registration_name,
                'tin_no'            => $c->tin_no,
                'unique_id'         => $c->unique_id,
                'keys_count'        => $c->keyCount,
                'start_subscribe'   => $c->start_subscribe,
                'end_subscribe'     => $c->end_subscribe,
                'expires_in'        => $c->expires_in,
            ];
        }


        return view('developer.dashboard', [
            'totalClients'   => $totalClients,
            'activeIntegrations' => $activeIntegrations,
            'apiCallsToday' => $totalApiCalls,

            'invoiceLabels'  => json_encode($invoiceLabels),
            'invoiceCounts'  => json_encode($invoiceCounts),

            'clients'        => $clients,
            'exportClients' => $exportClients,
        ]);

    }
}
