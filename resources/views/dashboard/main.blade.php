@extends('layouts.app')

@section('content')
{{-- Tailwind CDN for Dashboard Styling --}}
<script src="https://cdn.tailwindcss.com"></script>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    
    .dashboard-wrapper {
        font-family: 'Inter', sans-serif;
    }

    /** * CRITICAL FIX: Forces sidebar submenus to stay visible.
     * Overrides Tailwind's reset which hides Bootstrap accordions.
     */
    .nav-item-submenu.nav-item-open > .nav-group-sub {
        display: block !important;
    }
    
    /* Custom scrollbar for mobile table */
    .table-container::-webkit-scrollbar {
        height: 4px;
    }
    .table-container::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .table-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
</style>

<div class="dashboard-wrapper min-h-screen py-4 px-3 sm:px-6 lg:px-8 bg-gray-50/50">
    <div class="max-w-7xl mx-auto">
        
        {{-- Header Section --}}
        <div class="mb-6 md:mb-8 text-center md:text-left">
            <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 leading-tight">Analytics Dashboard</h1>
            <p class="mt-1 text-sm md:text-base text-gray-600">Overview of your customer and invoice data.</p>
        </div>

        {{-- Stats Cards Grid (Responsive: 1 col mobile, 2 col tablet, 4 col desktop) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            {{-- Card 1: Customers --}}
            <div class="bg-emerald-600 text-white rounded-xl shadow-sm p-5 flex flex-col justify-between hover:shadow-md transition-shadow">
                <div>
                    <h3 class="text-3xl font-bold mb-1">{{ number_format($totalCustomers) }}</h3>
                    <span class="text-xs font-semibold uppercase tracking-wider opacity-90">Total Customers</span>
                </div>
                <div class="text-[10px] opacity-75 mt-2">Registered in system</div>
            </div>

            {{-- Card 2: Invoices --}}
            <div class="bg-rose-600 text-white rounded-xl shadow-sm p-5 flex flex-col justify-between hover:shadow-md transition-shadow">
                <div>
                    <h3 class="text-3xl font-bold mb-1">{{ number_format($totalInvoices) }}</h3>
                    <span class="text-xs font-semibold uppercase tracking-wider opacity-90">Total Invoices</span>
                </div>
                <div class="text-[10px] opacity-75 mt-2">Generated to date</div>
            </div>

            {{-- Card 3: Revenue --}}
            <div class="bg-indigo-600 text-white rounded-xl shadow-sm p-5 flex flex-col justify-between hover:shadow-md transition-shadow">
                <div>
                    <h3 class="text-2xl font-bold mb-1">RM {{ number_format($totalRevenue, 2) }}</h3>
                    <span class="text-xs font-semibold uppercase tracking-wider opacity-90">Total Revenue</span>
                </div>
                <div class="text-[10px] opacity-75 mt-2">Across all invoices</div>
            </div>

            {{-- Card 4: Tax --}}
            <div class="bg-orange-600 text-white rounded-xl shadow-sm p-5 flex flex-col justify-between hover:shadow-md transition-shadow">
                <div>
                    <h3 class="text-2xl font-bold mb-1">RM {{ number_format($totalTax, 2) }}</h3>
                    <span class="text-xs font-semibold uppercase tracking-wider opacity-90">Total Tax</span>
                </div>
                <div class="text-[10px] opacity-75 mt-2">Collected</div>
            </div>
        </div>

        {{-- Recent Invoices Section --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            {{-- Card Header --}}
            <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <h2 class="text-lg font-bold text-gray-800">Recent Invoices</h2>
                
                {{-- Status Pills --}}
                <div class="flex flex-wrap gap-2">
                    @forelse ($invoiceStatus as $status)
                        @php
                            $isSubmitted = strtolower($status->submission_status) == 'submitted';
                            $statusText = $isSubmitted ? 'Submitted' : 'Failed';
                            $bgClass = $isSubmitted ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200';
                        @endphp
                        <div class="flex items-center border px-3 py-1 rounded-full text-[11px] font-bold {{ $bgClass }}">
                            {{ $statusText }}: <span class="ml-1.5 text-gray-900">{{ $status->total }}</span>
                        </div>
                    @empty
                        <div class="text-gray-400 italic text-xs">No status data</div>
                    @endforelse
                </div>
            </div>

            {{-- Mobile Responsive Table Container --}}
            <div class="table-container overflow-x-auto w-full">
                <table class="min-w-full divide-y divide-gray-200 text-sm text-left text-gray-700 whitespace-nowrap">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-bold">
                        <tr>
                            <th scope="col" class="px-4 py-3 md:px-6 tracking-wider">Invoice No.</th>
                            <th scope="col" class="px-4 py-3 md:px-6 tracking-wider">Customer</th>
                            <th scope="col" class="px-4 py-3 md:px-6 tracking-wider">Date</th>
                            <th scope="col" class="px-4 py-3 md:px-6 text-right tracking-wider">Amount</th>
                            <th scope="col" class="px-4 py-3 md:px-6 text-center tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($recentInvoices as $invoice)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3.5 md:px-6 font-semibold text-gray-900">
                                    {{ $invoice->invoice_no }}
                                </td>
                                <td class="px-4 py-3.5 md:px-6 text-gray-600 max-w-[180px] truncate" title="{{ $invoice->registration_name }}">
                                    {{ $invoice->registration_name ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3.5 md:px-6 text-gray-500 text-xs md:text-sm">
                                    {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3.5 md:px-6 text-right font-bold text-gray-900">
                                    RM {{ number_format((float) str_replace(['$', ','], '', $invoice->price), 2) }}
                                </td>
                                <td class="px-4 py-3.5 md:px-6 text-center">
                                    @if(strtolower($invoice->submission_status) == 'submitted')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-800">
                                            <span class="w-1.5 h-1.5 mr-1.5 bg-green-500 rounded-full"></span>
                                            Submitted
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800">
                                            <span class="w-1.5 h-1.5 mr-1.5 bg-red-500 rounded-full"></span>
                                            Failed
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="ph-note-blank text-3xl mb-2 opacity-50"></i>
                                        <span class="italic text-sm">No recent invoices found.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Mobile Swipe Hint --}}
            <div class="md:hidden py-3 text-center border-t border-gray-50 bg-gray-50/50">
                <span class="text-[10px] text-gray-400 uppercase tracking-widest font-medium flex items-center justify-center gap-2">
                    <i class="ph-arrows-left-right"></i> Swipe table to see more
                </span>
            </div>
        </div>
    </div>
</div>
@endsection