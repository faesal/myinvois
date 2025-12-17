<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Make sure to import DB facade

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function main()
    {
        $user = auth()->user();
    
        // Jika admin, tak perlu filter
        if ($user->role == 'admin') {
    
            $totalCustomers = DB::table('customer')->whereNull('deleted')->count();
    
            $totalInvoices = DB::table('invoice')->count();
    
            $totalRevenue = DB::table('invoice')->select(DB::raw('SUM(CAST(price AS DECIMAL(15,2))) as total'))->value('total');
    
            $totalTax = DB::table('invoice')->select(DB::raw('SUM(CAST(tax_amount AS DECIMAL(15,2))) as total'))->value('total');
    
            $invoiceStatus = DB::table('invoice')
                ->select('submission_status', DB::raw('COUNT(*) as total'))
                ->groupBy('submission_status')
                ->get();
    
            $recentInvoices = DB::table('invoice')
                ->leftJoin('customer', 'invoice.id_customer', '=', 'customer.id_customer')
                ->select('invoice.*', 'customer.registration_name')
                ->orderBy('invoice.issue_date', 'desc')
                ->limit(5)
                ->get();
    
        } else {
            $connection = session('connection_integrate');
    
            $totalCustomers = DB::table('customer')
            ->where('connection_integrate', $connection)
            ->where('customer_type','CUSTOMER')
            ->whereNull('deleted')
            ->count();

            $totalInvoices = DB::table('invoice')
                ->where('connection_integrate', $connection)
                ->count();
    
            $totalRevenue = DB::table('invoice')
                ->where('connection_integrate', $connection)
                ->select(DB::raw('SUM(CAST(price AS DECIMAL(15,2))) as total'))
                ->value('total');
    
            $totalTax = DB::table('invoice')
                ->where('connection_integrate', $connection)
                ->select(DB::raw('SUM(CAST(tax_amount AS DECIMAL(15,2))) as total'))
                ->value('total');
    
            $invoiceStatus = DB::table('invoice')
                ->where('connection_integrate', $connection)
                ->select('submission_status', DB::raw('COUNT(*) as total'))
                ->groupBy('submission_status')
                ->get();
    
            $recentInvoices = DB::table('invoice')
                ->leftJoin('customer', 'invoice.id_customer', '=', 'customer.id_customer')
                ->where('invoice.connection_integrate', $connection)
                ->select('invoice.*', 'customer.registration_name')
                ->orderBy('invoice.issue_date', 'desc')
                ->limit(5)
                ->get();
        }
    
        return view('dashboard.main', [
            'totalCustomers' => $totalCustomers,
            'totalInvoices' => $totalInvoices,
            'totalRevenue' => $totalRevenue,
            'totalTax' => $totalTax,
            'invoiceStatus' => $invoiceStatus,
            'recentInvoices' => $recentInvoices,
        ]);
    }
    
}
?>