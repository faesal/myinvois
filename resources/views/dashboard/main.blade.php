@extends('layouts.app')

@section('content')
<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f3f4f6;
    }
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    ::-webkit-scrollbar-track {
        background: #e0e0e0;
        border-radius: 10px;
    }
    ::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>

<div class="min-h-screen flex flex-col items-center py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl w-full">
        <!-- Dashboard Header -->
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-extrabold text-gray-900 leading-tight">Analytics Dashboard</h1>
            <p class="mt-2 text-lg text-gray-600">Overview of your customer and invoice data.</p>
        </div>

        <!-- Stats Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Customers -->
            <div class="card bg-emerald-600 text-white rounded-xl shadow-lg overflow-hidden">
                <div class="card-body p-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-4xl font-bold">{{ number_format($totalCustomers) }}</h3>
                    </div>
                    <div>
                        <span class="text-lg font-medium">Total Customers</span>
                        <div class="text-sm opacity-75">Registered in the system</div>
                    </div>
                </div>
            </div>

            <!-- Total Invoices -->
            <div class="card bg-rose-600 text-white rounded-xl shadow-lg overflow-hidden">
                <div class="card-body p-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-4xl font-bold">{{ number_format($totalInvoices) }}</h3>
                    </div>
                    <div>
                        <span class="text-lg font-medium">Total Invoices</span>
                        <div class="text-sm opacity-75">Generated to date</div>
                    </div>
                </div>
            </div>

            <!-- Total Revenue -->
            <div class="card bg-indigo-600 text-white rounded-xl shadow-lg overflow-hidden">
                <div class="card-body p-6">
                    <div class="flex items-center justify-between mb-2">
                        <strong class="text-2xl">RM {{ number_format($totalRevenue, 2) }}</strong>
                    </div>
                    <div>
                        <span class="text-lg font-medium">Total Revenue</span>
                        <div class="text-sm opacity-75">Across all invoices</div>
                    </div>
                </div>
            </div>

            <!-- Total Tax -->
            <div class="card bg-orange-600 text-white rounded-xl shadow-lg overflow-hidden">
                <div class="card-body p-6">
                    <div class="flex items-center justify-between mb-2">
                        <h5 class="text-2xl font-bold">RM {{ number_format($totalTax, 2) }}</h5>
                    </div>
                    <div>
                        <span class="text-lg font-medium">Total Tax</span>
                        <div class="text-sm opacity-75">Collected</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Invoices Section -->
        <div class="bg-white rounded-xl shadow-lg p-7 border border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Recent Invoices</h2>

       <!-- Status Summary Badges -->
        <div class="flex flex-wrap gap-2 mb-5">
            @forelse ($invoiceStatus as $status)
                @php
                    $status_invoice = ucfirst($status->submission_status) ?: 'Failed';
                    $color = $status_invoice == 'Submitted' ? 'green' : 'red';
                @endphp
                <div class="flex items-center justify-between bg-{{ $color }}-100 text-{{ $color }}-800 px-4 py-3 rounded-md shadow-sm w-full sm:w-auto">
                    <span class="text-sm font-semibold">{{ $status_invoice }}</span>
                    <span class="ml-4 text-sm font-bold text-gray-700">({{ $status->total }})</span>
                </div>
            @empty
                <div class="text-gray-500">No invoice status summary available.</div>
            @endforelse
        </div>

            <!-- Table -->
            <table class="table-auto w-full text-sm text-left text-gray-700">
                <thead class="bg-gray-100 text-xs uppercase text-gray-600">
                    <tr>
                        <th class="px-4 py-2">Invoice No.</th>
                        <th class="px-4 py-2">Customer</th>
                        <th class="px-4 py-2">Date</th>
                        <th class="px-4 py-2">Amount</th>
                        <th class="px-4 py-2">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($recentInvoices as $invoice)
                        <tr>
                            <td class="px-4 py-2 font-medium truncate">{{ $invoice->invoice_no }}</td>
                            <td class="px-4 py-2 truncate">{{ $invoice->registration_name ?? 'N/A' }}</td>
                            <td class="px-4 py-2">{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d-m-Y') }}</td>
                            <td class="px-4 py-2">RM {{ number_format((float) str_replace('$', '', $invoice->price), 2) }}</td>
                            <td class="px-4 py-2">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $invoice->submission_status == 'submitted' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $invoice->submission_status == 'submitted' ? 'Submitted' : 'Failed' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-2 text-center text-gray-500">No recent invoices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
