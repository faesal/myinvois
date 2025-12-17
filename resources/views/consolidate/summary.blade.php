@component('mail::message')
# 🧾 Consolidate Daily 

<table style="width:100%; border-collapse: collapse;" border="1" cellpadding="8">
    <thead style="background-color:#f2f2f2;">
        <tr>
            <th>Connection</th>
            <th>Jumlah Item</th>
            <th>Jumlah Sebelum (RM)</th>
            <th>Jumlah Selepas (RM)</th>
            <th>Tarikh</th>
        </tr>
    </thead>
    <tbody>
    @foreach ($summary as $row)
        <tr>
            <td>{{ $row->connection_integrate }}</td>
            <td align="center">{{ number_format($row->consolidate_total_item) }}</td>
            <td align="right">{{ number_format($row->consolidate_total_amount_before, 2) }}</td>
            <td align="right">{{ number_format($row->consolidate_total_amount_after, 2) }}</td>
            <td align="center">{{ \Carbon\Carbon::parse($row->tarikh)->format('d/m/Y') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

@component('mail::button', ['url' => url('/consolidate/logs')])
Lihat Log Penuh
@endcomponent

Terima kasih,<br>
{{ config('app.name') }}
@endcomponent
