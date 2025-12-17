@extends('admin.master_layout')

@section('title')
  <title>{{ __('translate.Order Details') }}</title>
@endsection

@section('body-header')
  <h3 class="crancy-header__title m-0">{{ __('translate.Order Details') }}</h3>
  <p class="crancy-header__text">{{ __('translate.Manage Order') }} >> {{ __('translate.Order Details') }}</p>
@endsection

@push('css_section')
<style>
  /* --------- Receipt Card (right) --------- */
  .receipt-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden}
  .receipt-card__body{padding:22px}
  .receipt-card__center{text-align:center}
  .receipt-card__section{border-top:1px dashed #e5e7eb;margin-top:14px;padding-top:14px}
  .receipt-row{display:flex;justify-content:space-between;gap:12px;padding:4px 0}
  .receipt-muted{color:#6b7280}
  .nowrap{white-space:nowrap}

  /* Header */
  .brand-circle{width:92px;height:92px;border-radius:999px;background:#f8fafc;
    border:1px solid #e5e7eb;display:flex;align-items:center;justify-content:center;margin:0 auto 10px}
  .brand-circle img{max-width:72px;max-height:72px;object-fit:contain}
  .receipt-branch{font-size:28px;line-height:1.1;font-weight:800;letter-spacing:.2px}
  .receipt-address{font-size:14px}

  /* Pickup ID */
  .pickup-title{margin-top:14px;font-weight:700;color:#111827;letter-spacing:.5px}
  .pickup-id{font-size:56px;line-height:1;font-weight:900;margin:2px 0 6px}

  /* Notice */
  .notice{background:#fff3e2;border:1px solid #ffe3b0;border-radius:12px;padding:14px}
  .notice strong{display:block;margin-bottom:6px;color:#92400e}

  /* Line items (right) */
  .line-item{margin-bottom:10px}
  .line-item__top{display:flex;justify-content:space-between;font-weight:600}
  .line-item__sub{font-size:12px;color:#6b7280}

  /* Totals + badges */
  .total-row{font-weight:800}
  .badge-soft{padding:.28rem .6rem;border-radius:999px;font-size:.72rem;font-weight:700}
  .badge-paid{background:#e8f6ec;color:#15803d}
  .badge-pending{background:#fff7df;color:#b45309}
  .badge-failed,.badge-unpaid{background:#fde2e1;color:#b91c1c}
  .badge-printed{background:#e8f6ec;color:#166534}
  .badge-unprinted{background:#eef2ff;color:#4338ca}

  /* --------- Items table (left) --------- */
  .crancy-table__main{width:100%;border-collapse:separate;border-spacing:0}
  .crancy-table__main thead th{background:#f8fafc;border-bottom:1px solid #e5e7eb;padding:12px 16px;font-weight:600}
  .crancy-table__main tbody td{border-bottom:1px solid #f1f5f9;padding:12px 16px;vertical-align:middle}
  .item-title{font-weight:600}
  .item-sub{font-size:.85rem;color:#6b7280}

  /* Info cards */
  .zum_icvoice_item_main{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px}
  .zum_invoice_text h2{font-size:16px;margin:0 0 8px}

  @media print{
    header,nav,.no-print,.crancy-header__text,.btn{display:none!important}
    .container__bscreen{max-width:100%!important}
    .receipt-card{border:none}
  }
</style>
@endpush

@section('body-content')
<section class="crancy-adashboard crancy-show mt-5">
  <div class="zum_icvoice">
    <div class="container container__bscreen">
      <div class="crancy-product-card">

        <div class="create_new_btn_inline_box mb-4">
          <h4 class="crancy-product-card__title">{{ __('translate.Order Details') }}</h4>
          <a href="{{ route('admin.order.index') }}" class="crancy-btn no-print">
            <i class="fa fa-list"></i> {{ __('translate.Order List') }}
          </a>
        </div>

        @php
          $address = json_decode($order->delivery_address ?? '{}');
          $tz      = config('app.timezone','Asia/Kuala_Lumpur');

          // money parts
          $itemsTotal = collect($order->items ?? [])->sum(function($i){ return (float)($i->total ?? 0); });
          $subtotal   = (float)($order->total ?? $itemsTotal);
          $sst        = (float)($order->sst ?? 0);
          $vat        = (float)($order->vat ?? 0);
          $delv       = (float)($order->delivery_charge ?? 0);
          $service    = (float)($order->service_charge ?? 0);
          $gateway    = (float)($order->fpx_fee ?? ($order->gateway_fee ?? 0));
          $grand      = (float)($order->grand_total ?? 0);
          $calcTotal  = $grand ?: ($subtotal + $sst + $vat + $delv + $service + $gateway);

          $pickupMethod = $order->pickup_method
              ?? ($order->order_type === 'pickup' ? __('Self Pickup') : ($order->order_type ? ucfirst($order->order_type) : '—'));

          $logo = $order->restaurant->logo ?? null;        // adjust if you store with Storage::url
          $branchLabel = $order->restaurant->branch_name
              ?? $order->restaurant->restaurant_name
              ?? __('Restaurant');
          $branchAddress = $order->restaurant->address ?? '';
        @endphp

        {{-- ===== Row: Info panels ===== --}}
        <div class="row mb-4 g-8">
          {{-- Billing + Payment info --}}
          <div class="col-lg-4 col-md-8">
            <div class="zum_icvoice_item_main">
             
                <div class="zum_invoice_text"><h2>Customer Details</h2></div>
                <div class="zum_icvoice_item">
                  <ul class="zum_invoice_lixt d-flex flex-column gap-2">
                    <li>{{__('translate.Full Name')}} : <span>{{ $address->contact_person_name ?? '' }}</span></li>
                    <li>
                      <a href="mailto:{{ $address->contact_person_email ?? '' }}">
                        {{__('translate.Email')}} : <span>{{ $address->contact_person_email ?? '' }}</span>
                      </a>
                    </li>
                    <li>
                      <a href="tel:{{ $address->contact_person_number ?? '' }}">
                        {{__('translate.Phone')}} : <span>{{ $address->contact_person_number ?? '' }}</span>
                      </a>
                    </li>
                    <li>{{__('translate.Address')}} : <span>{{ $address->address ?? '' }}</span></li>
                    <li>Delivery Method : <span>{{ ucfirst(str_replace("_"," ",$order->order_type)) }}</span></li>
                    
                  </ul>
                </div>
            

              <div class="zum_icvoice_item mt-3">
                <h2>{{__('translate.Payment Information')}}:</h2>
                <ul class="zum_invoice_lixt d-flex flex-column gap-2">
                  <li>{{__('translate.Method')}} : <span>FPX</span></li>
                 
                </ul>
              </div>

             
            </div>
          </div>

         
          {{-- Payment status + Assign delivery man --}}
          <div class="col-lg-4 col-md-6">
            <div class="order_status_box zum_icvoice_item_main">
              <form class="order_status">
                <div class="order_status_item">
                  <div class="order_status_inner">
                    <label class="form-label">{{__('translate.Payment status')}}</label>
                    <select class="form-select"
                            onchange="showPaymentConfirmationModal({{ $order->id }}, this.value)">
                      @if($order->payment_status == 'pending')
                        <option value="pending" {{ $order->payment_status == 'pending' ? 'selected' : '' }}>{{ __('translate.Pending') }}</option>
                        <option value="success" {{ $order->payment_status == 'success' ? 'selected' : '' }}>{{ __('translate.Success') }}</option>
                      @elseif($order->payment_status == 'success')
                        <option value="success" selected>{{ __('translate.Success') }}</option>
                      @else
                        <option selected>{{ ucfirst($order->payment_status) }}</option>
                      @endif
                    </select>
                  </div>
                </div>

              </form>
            </div>
          </div>
        </div>

        {{-- ===== Row: Items table + Receipt ===== --}}
        <div class="row g-8">
          {{-- LEFT: items table --}}
          <div class="col-lg-12">
            <div class="zum_icvoice_item_main">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <h2 class="m-0">Order ID - #{{ $order->id }}</h2>
                
                
             
              </div>

              @php
                $items = collect($order->items ?? []);
              @endphp

              @if($items->isEmpty())
                <div class="text-muted">{{ __('translate.No data found') }}</div>
              @else
                <div class="table-responsive">
                  <table class="crancy-table__main w-100">
                    <thead>
                      <tr>
                        <th style="width:40%">Items</th>
                        <th style="width:10%" class="text-end">{{ __('translate.Qty') }}</th>
                        <th style="width:15%" class="text-end">{{ __('translate.Price') }}</th>
                        <th style="width:15%" class="text-end">{{ __('translate.Total') }}</th>
                        <th style="width:20%" class="text-end">{{ __('translate.Action') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                    @foreach($items as $it)
                      @php
                        $qty     = max(1, (int)($it->qty ?? 1));
                        $line    = (float)($it->total ?? 0);
                        $unit    = $qty ? $line / $qty : 0;

                        // Resolve product name (fallback to stored snapshot)
                        $prod    = optional(Modules\Product\App\Models\Product::find($it->product_id));
                        $name    = $prod->name ?? ($it->product_name ?? __('translate.Product'));

                        // Optional options/addons/notes (supports array or json string)
                        $optText = '';
                        $options = $it->options ?? $it->variants ?? $it->addons ?? null;
                        if (is_string($options)) {
                          $decoded = json_decode($options, true);
                          $options = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                        }
                        if (is_array($options) && !empty($options)) {
                          $pairs = [];
                          foreach ($options as $k => $v) {
                            if (is_array($v)) {
                              $pairs[] = implode(', ', array_map('strval', $v));
                            } else {
                              $pairs[] = is_string($k) ? ($k.': '.(string)$v) : (string)$v;
                            }
                          }
                          $optText = implode(' · ', array_filter($pairs));
                        }
                        $note = trim((string)($it->note ?? $it->item_note ?? ''));
                      @endphp
                      <tr>
                        <td>
                          <div class="item-title">{{ $name }}</div>
                          @if($optText || $note)
                            <div class="item-sub">
                              @if($optText)<span>{{ $optText }}</span>@endif
                              @if($optText && $note) <span> · </span> @endif
                              @if($note)<span>{{ $note }}</span>@endif
                            </div>
                          @endif
                        </td>
                        <td class="text-end">{{ $qty }}</td>
                        <td class="text-end">{{ currency($unit) }}</td>
                        <td class="text-end">{{ currency($line) }}</td>
                        <td class="text-end">
                          <button type="button"
                                  class="btn btn-sm btn-outline-danger no-print"
                                  onclick="itemDeleteConfrimation({{ $it->id ?? 0 }})"
                                  data-bs-toggle="modal"
                                  data-bs-target="#deleteModal">
                            <i class="bi bi-trash"></i> {{ __('translate.Delete') }}
                          </button>
                        </td>
                      </tr>
                    @endforeach
                    </tbody>
                  </table>
                </div>
              @endif

              {{-- Optional: footer showing computed totals to mirror receipt --}}
              <div class="d-flex justify-content-end mt-3">
                <div style="min-width:320px">
                  <div class="d-flex justify-content-between py-1">
                    <span>{{ __('Subtotal') }}</span><span>{{ currency($subtotal) }}</span>
                  </div>
                  @if($sst>0)     <div class="d-flex justify-content-between py-1"><span>{{ __('SST') }}</span><span>{{ currency($sst) }}</span></div>@endif
                  @if($vat>0)     <div class="d-flex justify-content-between py-1"><span>{{ __('VAT') }}</span><span>{{ currency($vat) }}</span></div>@endif
                  <div class="d-flex justify-content-between py-1"><span>{{ __('FPX') }}</span><span>RM 2.00</span></div>
                  @if($service>0) <div class="d-flex justify-content-between py-1"><span>{{ __('Service Charge') }}</span><span>{{ currency($service) }}</span></div>@endif
                  @if($delv>0)    <div class="d-flex justify-content-between py-1"><span>{{ __('Delivery Charge') }}</span><span>{{ currency($delv) }}</span></div>@endif
                  <div class="d-flex justify-content-between py-1 fw-bold">
                    <span>{{ __('Total') }}</span><span>{{ currency($calcTotal) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div> {{-- /row items + receipt --}}

      </div> {{-- /card --}}
    </div>
  </div>
</section>

{{-- ===== Delete Confirmation Modal ===== --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">{{ __('translate.Delete Confirmation') }}</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body"><p>{{ __('translate.Are you realy want to delete this item?') }}</p></div>
    <div class="modal-footer">
      <form action="" id="item_delect_confirmation" class="delet_modal_form" method="POST">
        @csrf @method('DELETE')
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('translate.Close') }}</button>
        <button type="submit" class="btn btn-primary btn-type-dlt">{{ __('translate.Yes, Delete') }}</button>
      </form>
    </div>
  </div></div>
</div>

{{-- ===== Payment status modal ===== --}}
<div class="modal fade" id="paymentConfirmationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">{{ __('translate.Payment Status Change Confirmation') }}</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body"><p>{{ __('translate.Are you sure you want to change the payment status of this order?') }}</p></div>
    <div class="modal-footer">
      <form action="" id="paymentConfirmationForm" class="delet_modal_form" method="POST">
        @csrf
        <input type="hidden" name="payment_status" id="newPaymentStatus">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('translate.Close') }}</button>
        <button type="submit" class="btn btn-primary">{{ __('translate.Yes, Change') }}</button>
      </form>
    </div>
  </div></div>
</div>

{{-- ===== Assign delivery man modal ===== --}}
<div class="modal fade" id="showDeliveryManModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">{{ __('translate.Status Change Confirmation') }}</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body"><p>{{ __('translate.Are you sure you want to deliver this order to this delivery man?') }}</p></div>
    <div class="modal-footer">
      <form action="" id="showDeliveryManForm" class="delet_modal_form" method="POST">
        @csrf
        <input type="hidden" name="delivery_man_id" id="newDelivery_id">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('translate.Close') }}</button>
        <button type="submit" class="btn btn-primary">{{ __('translate.Yes, Change') }}</button>
      </form>
    </div>
  </div></div>
</div>
@endsection

@push('js_section')
<script>
"use strict";

function itemDeleteConfrimation(id){
  $("#item_delect_confirmation").attr("action",'{{ url("admin/order-delete/") }}' + "/" + id);
}

function showPaymentConfirmationModal(orderId, selectedValue) {
  $("#paymentConfirmationForm").attr("action", '{{ url("admin/payment-status-change/") }}' + "/" + orderId);
  $("#newPaymentStatus").val(selectedValue);
  $('#paymentConfirmationModal').modal('show');
}

function showDeliveryManModal(orderId, selectedValue) {
  $("#showDeliveryManForm").attr("action", '{{ url("admin/deliveryman/") }}' + "/" + orderId);
  $("#newDelivery_id").val(selectedValue);
  $('#showDeliveryManModal').modal('show');
}
</script>
@endpush
