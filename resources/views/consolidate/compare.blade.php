@extends('layouts.app')

@section('content')
    <h2>📊 Comparison Difference ({{ $date }})</h2>


    <form method="POST" class="mb-4">
        @csrf
        <div class="row g-2 align-items-end">
            <div class="col-auto">
                <label for="date" class="form-label">Select Date:</label>
                <input type="date" id="date" name="date" class="form-control" value="{{ $date }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </div>
    </form>
    @foreach($results as $connection => $data)
        <h4>🔌 Connection: {{ strtoupper($connection) }}</h4>

        @if(isset($data['error']))
            <p style="color: red;">❌ Error: {{ $data['error'] }}</p>
            @continue
        @endif

        <div class="row">
            <div class="col-md-6">
                <h5>❗ In POS Sales But Missing/Inconsistent in Consolidated</h5>
                <table class="table table-sm table-bordered table-warning">
                    <thead>
                        <tr>
                            <th>Sale ID</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['sales_diff'] as $row)
                            <tr>
                                <td>{{ $row['sale_id'] }}</td>
                                <td>{{ $row['item_name'] }}</td>
                                <td>{{ $row['quantity_purchased'] }}</td>
                                <td>{{ number_format($row['subtotal'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">✅ No Differences</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="col-md-6">
                <h5>❗ In Consolidated But Missing/Inconsistent in POS</h5>
                <table class="table table-sm table-bordered table-danger">
                    <thead>
                        <tr>
                            <th>Sale ID</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['consolidated_diff'] as $row)
                            <tr>
                                <td>{{ $row['sale_id'] }}</td>
                                <td>{{ $row['item_name'] }}</td>
                                <td>{{ $row['quantity_purchased'] }}</td>
                                <td>{{ number_format($row['subtotal'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">✅ No Differences</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <hr>
    @endforeach
@endsection
