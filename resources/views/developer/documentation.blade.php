@extends('layouts.developer')



@section('content')



<style>

/* ====== Documentation Page ====== */

.doc-page {

    padding: 25px 35px;

}



.doc-section {

    margin-bottom: 60px;

}



.doc-section h2 {

    font-size: 26px;

    padding-bottom: 10px;

    border-bottom: 2px solid #eee;

}



.doc-section h3 {

    margin-top: 25px;

}



.code-block {

    background: #1e1e1e;

    color: #dcdcdc;

    padding: 18px;

    border-radius: 6px;

    font-size: 14px;

    overflow-x: auto;

    margin-top: 12px;

}



pre {

    margin: 0;

    white-space: pre;

}



/* Smooth scroll anchor */

html {

    scroll-behavior: smooth;

}

</style>

<script src="https://cdn.tailwindcss.com"></script>



<style>

.intro-purple {

    background: linear-gradient(135deg, #6A11CB 0%, #2575FC 100%) !important;

}

</style>



<section class="intro-purple w-full py-20 px-8 text-white rounded-[40px] shadow-lg">

    <div class="max-w-6xl mx-auto">

        <h1 class="text-2xl font-bold mb-4">

            Developer Documentation

        </h1>



        <p class="text-l opacity-95 max-w-3xl">

            Build powerful integrations with our comprehensive API. Connect your 

            POS, ERP, or third-party system to automate invoice submission, 

            manage credentials, and streamline financial compliance.

        </p>



        <div class="mt-10 flex gap-5">

            <a href="#quickstart"

                class="bg-white text-purple-700 px-7 py-3 rounded-2xl font-semibold shadow">

                Quick Start

            </a>



            <a href="#api"

                class="border border-white text-white px-7 py-3 rounded-2xl font-semibold">

                API Reference

            </a>

        </div>

    </div>

</section>





<div class="doc-page">





    <!-- ===================== INTRODUCTION ===================== -->

    <div class="doc-section" id="introduction">

    <h2 class="text-2xl font-semibold text-gray-800 mt-6 mb-2">Introduction</h2> 

    <ol class="list-decimal list-inside text-gray-700 space-y-2"> 

        <li>Validating integration tokens</li> <li>Receiving invoice header + items</li> 

        <li>Preventing duplicate submissions</li> <li>Auto-calculating invoice totals</li> 

        <li>Storing invoice & items into the MySyncTax database</li> </ol> 

        <p class="text-gray-700 mt-4"> For more information, visit 

            <a href="https://mysynctax.com" target="_blank" class="text-blue-600 hover:underline">mysynctax.com</a>. </p>

    </div>







    <!-- ===================== AUTHENTICATION ===================== -->

    <div class="doc-section" id="authentication">

        <h2>Authentication</h2>



        <p>

            All integration requests must contain two parameters inside the JSON body:

        </p>



        <ul>

            <li><strong>mysynctax_key</strong></li>

            <li><strong>mysynctax_secret</strong></li>

        </ul>



        <p>

            These identify the integration and must match the credentials assigned to your company.

        </p>



        <p>

            Requests without both fields will be rejected with <code>401 Unauthorized</code>.

        </p>

    </div>







    <!-- ===================== JSON STRUCTURE ===================== -->

    <div class="doc-section" id="json-structure">

        <h2>JSON Structure</h2>



        <h3>Header-Level Fields</h3>



        <ul>

            <li><strong>invoice_no</strong> – The external invoice number.</li>

            <li><strong>issue_date</strong> – Date of invoice (YYYY-MM-DD HH:MM:SS).</li>

            <li><strong>sale_id_integrate</strong> – ID of the transaction in your system.</li>

            <li><strong>total_amount</strong> – Total invoice amount BEFORE item breakdown (amount_before).</li>

            <li><strong>payment_note_term</strong> – CASH / TRANSFER / CHEQUE / etc.</li>

        </ul>



        <h3>Item-Level Fields</h3>

        <ul>

            <li><strong>item_id</strong> – ID of each item in the invoice.</li>

            <li><strong>sorting_id</strong> – Item sorting or line order.</li>

            <li><strong>invoiced_quantity</strong> – Quantity purchased.</li>

            <li><strong>unit_price</strong> – Unit price.</li>

            <li><strong>price_discount</strong> – Discount per unit.</li>

            <li><strong>total</strong> – Total price for this item (quantity × price).</li>

            <li><strong>item_description</strong> – Item name or description.</li>

        </ul>



        

    </div>





    <!-- ===================== SAMPLE JSON (COMPLETED) ===================== -->

    <div class="doc-section" id="sample-json-completed">

    <h2>Normal Invoice with Receipt QR</h2>

<p>
This API generates a normal invoice together with a <strong>receipt QR code</strong>.
If the customer <strong>does not scan the QR code</strong>, the transaction will be
<strong>automatically treated as a consolidated invoice</strong> in accordance with
LHDN MyInvois rules.
</p>



        <div class="code-block">

<pre>
{
  "mysynctax_key": "F3kW8nP1zS0aL9tQ4vB7uR6yJ2cX5mT8gH1eK3oN",
  "mysynctax_secret": "K8pR3sL1vF9gQ2wE6nC4bT7jH0mD5yU1aX8zV3kS4tN9fG2qM7rY0uJ5hP",
  "invoice_no": "INV-9002029",
  "sale_id_integrate": 9002029,
  "total_amount": 210.00,
  "payment_note_term": "CASH",
  "items": [
    {
      "item_id": 12,
      "sorting_id": 1,
      "invoiced_quantity": 2,
      "unit_price": 60.00,
      "item_description": "Premium Nasi Ayam Set",
      "total": 120.00,
      "price_discount": 0
    },
    {
      "item_id": 13,
      "sorting_id": 2,
      "invoiced_quantity": 1,
      "unit_price": 90.00,
      "item_description": "Iced Coffee Latte",
      "total": 90.00,
      "price_discount": 0
    }
  ]
}

</pre>

        </div>



    </div>


    <div class="doc-section" id="send-data">

        <h2>Send Data</h2>



        <p>Submit data using the following endpoint:</p>



        <div class="code-block"><pre>POST {{url('')}}/api/myinvois</pre></div>



        <h3>Key Notes</h3>

        <ul>

            <li>Duplicate JSON payloads (same hashed content) are rejected with <strong>409</strong>.</li>

            <li>All amounts must be numerical.</li>

            <li>Date formats must use <strong>YYYY-MM-DD HH:MM:SS</strong>.</li>

        </ul>

    </div>







    <!-- ===================== RESPONSES ===================== -->

    <div class="doc-section" id="responses">

        <h2>Response Examples</h2>



        <h3>Success</h3>

        <div class="code-block">

<pre>{

  "status": "ok",

  "mysynctax_uuid": "generated-unique-id"

  "qr_url": "{{url('')}}/redirect/9QqIe39"

}</pre>

        </div>



        <h3>Duplicate</h3>

        <div class="code-block">

<pre>{

  "status": "duplicate_ignored",

  "mysynctax_uuid": "existing-unique-id"

}</pre>

        </div>



        <h3>Unauthorized</h3>

        <div class="code-block">

<pre>{

  "status": "unauthorized",

  "message": "Invalid mysynctax_key or mysynctax_secret"

}</pre>

        </div>

    </div>







    <!-- ===================== ERROR CODES ===================== -->

    <div class="doc-section" id="errors">

        <h2>Error Codes</h2>



        <table class="table table-bordered">

            <tr><th>HTTP Code</th><th>Description</th></tr>

            <tr><td>400</td><td>Invalid or malformed JSON</td></tr>

            <tr><td>401</td><td>API key or secret missing/invalid</td></tr>

            <tr><td>409</td><td>Duplicate invoice submission</td></tr>

            <tr><td>500</td><td>Server processing error</td></tr>

        </table>

    </div>



<!-- ===================== NEW API: NORMAL INVOICE (WITH CUSTOMER) ===================== -->
<div class="doc-section" id="invoice-with-customer">
<h2>ERP - Invoice (With Customer)</h2>

<div class="code-block">
<pre>POST {{url('')}}/api/myinvois/invoice</pre>
</div>

<p>
This API is intended for <strong>ERP / Accounting systems</strong> that manage customer
master data and require direct submission to LHDN.
</p>

<h3>Request Example</h3>
<div class="code-block"><pre>
  {
  "mysynctax_key": "oHwIlgfhsBPP30f7",
  "mysynctax_secret": "fYxPMD2A5hPDWNI6",
  "isAutoToLHDN":1,
  "invoice_no": "INV-567-45",
  "sale_id_integrate": 23422,
  "payment_note_term": "CASH",
  "taxable_amount": 100.00,
  "tax_amount": 6.00,
  "tax_percent":6,
  "total_amount": 106.00,
  "customer": {
    "tin_no": "IG2xxxxxxx0",
    "registration_name": "ABC Trading Sdn Bhd",
    "identification_no": "202xxxxxxxx7",
    "identification_type": "BRN",
    "sst_registration": "SST123456",
    "phone": "0123456789",
    "email": "finance@abctrading.com",
    "address_line_1": "No 10, Jalan Teknologi",
    "address_line_2": "Taman Teknologi",
    "address_line_3": "Seksyen 7",
    "city_name": "Shah Alam",
    "postal_zone": "40000",
    "state_code": "10",
    "country_code": "MYS"
  },
  "items": [
    {
      "item_id": 101,
      "sorting_id": 1,
      "item_description": "USB Keyboard",
      "invoiced_quantity": 2,
      "unit_price": 30.00,
      "price_discount": 0.00,
      "total": 60.00,
      "tax": 6
    },
    {
      "item_id": 102,
      "sorting_id": 2,
      "item_description": "USB Mouse",
      "invoiced_quantity": 1,
      "unit_price": 40.00,
      "price_discount": 0.00,
      "total": 40.00,
      "tax": 6
    }
  ]
}
</pre></div>

<h3>Response Example</h3>
<div class="code-block"><pre>
{
    "status": "ok",
    "invoice_id": 410,
    "mysynctax_uuid": "a461cd55016ece31242098e2b409c086fea6e3f3",
    "customer_status": "updated",
    "qr_lhdn": "{{url('')}}/qr_link/1DB9HRPJ1F1VJZ34PYSRVJDK10",
    "customer_id": 90,
    "result": {
        "headers": {},
        "original": {
            "submissionUid": "X7T2BQBCW6A55280PYSRVJDK10",
            "acceptedDocuments": [
                {
                    "uuid": "1DB9HRPJ1F1VJZ34PYSRVJDK10",
                    "invoiceCodeNumber": "INV-567-3455"
                }
            ],
            "rejectedDocuments": []
        },
        "exception": null
    }
}</pre></div>
</div>

<!-- ===================== NEW API: CREDIT / DEBIT / REFUND ===================== -->
<div class="doc-section" id="note-api">
<h2>Credit Note / Debit Note / Refund</h2>

<div class="code-block">
<pre>POST {{url('')}}/api/myinvois/note</pre>
</div>

<p>
Used to submit adjustments for invoices that have already been submitted to LHDN.
</p>

<h3>Request Example</h3>
<div class="code-block"><pre>{
  "mysynctax_key": "oHwIlgfhsBPP30f7",
  "mysynctax_secret": "fYxPMD2A5hPDWNI6",
  "note_type": "refund",//credit,debit,refund
  "mysynctax_uuid": "bfc2c97e589ceb9fe9cb4b603bb740d011cb3d53",
  "sale_id_integrate": 5643,
  "items": [
    {
      "item_id": 102,
      "qty": 1,
      "price": 50.00,
      "discount": 0.00,
      "tax": 3.00,
      "description": "Item rosak / dipulangkan"
    }
  ]
}</pre></div>

<h3>Response Example</h3>
<div class="code-block"><pre>
{
    "status": "success",
    "note_type": "refund",
    "invoice_id": 409,
    "mysynctax_uuid": "f8259315-45bf-41ba-bd6d-ae9abb93ac32",
    "qr_lhdn": "{{url('')}}/qr_link/9QJTTE3H66GCHRHB4A3PVJDK10",
    "message": "Refund Note submitted successfully",
    "result": {
        "headers": {},
        "original": {
            "submissionUid": "5PBX6XF31R26SH0J3A3PVJDK10",
            "acceptedDocuments": [
                {
                    "uuid": "9QJTTE3H66GCHRHB4A3PVJDK10",
                    "invoiceCodeNumber": "REFUND-NOTE-20251229001155"
                }
            ],
            "rejectedDocuments": []
        },
        "exception": null
    }
}
</pre></div>
</div>

<!-- ===================== ERROR CODES ===================== -->
<div class="doc-section" id="errors">
<h2>Error Codes</h2>
<table class="table table-bordered">
<tr><th>HTTP Code</th><th>Description</th></tr>
<tr><td>400</td><td>Invalid or malformed JSON</td></tr>
<tr><td>401</td><td>API key or secret missing/invalid</td></tr>
<tr><td>404</td><td>Invoice UUID not found</td></tr>
<tr><td>409</td><td>Duplicate submission</td></tr>
<tr><td>422</td><td>Item mismatch</td></tr>
<tr><td>500</td><td>Server processing error</td></tr>
</table>
</div>

</div>

<!-- ===================== SELF-BILLED INVOICE ===================== -->
<div class="doc-section" id="selfbill-invoice">
    <h2>Self-Billed Invoice</h2>

    <p>
        Self-Billed Invoice is used when the supplier does not issue an e-Invoice
        and the buyer generates the invoice on behalf of the supplier, as required
        by LHDN MyInvois.
    </p>

    <div class="code-block">
        POST {{url('')}}/api/myinvois/selfbill/invoice
    </div>

    <h3>Request Example</h3>
    <div class="code-block"><pre>{
  "mysynctax_key": "oHwIlgfhsBPP30f7",
  "mysynctax_secret": "fYxPMD2A5hPDWNI6",

  "invoice_no": "INV-567-456",
  "sale_id_integrate": 456,
  "payment_note_term": "CASH",

  "taxable_amount": 100.00,
  "tax_amount": 6.00,
  "tax_percent": 6,
  "total_amount": 106.00,

  "supplier": {
    "tin_no": "IG20xx848xxxx",
    "registration_name": "ABC Trading Sdn Bhd",
    "identification_no": "2025031xxxx",
    "identification_type": "BRN",
    "sst_registration": "SST123456",
    "phone": "0123456789",
    "email": "finance@abctrading.com",
    "address_line_1": "No 10, Jalan Teknologi",
    "address_line_2": "Taman Teknologi",
    "address_line_3": "Seksyen 7",
    "city_name": "Shah Alam",
    "postal_zone": "40000",
    "state_code": "10",
    "country_code": "MYS"
  },

  "items": [
    {
      "item_id": 101,
      "sorting_id": 1,
      "item_description": "USB Keyboard",
      "invoiced_quantity": 2,
      "unit_price": 30.00,
      "price_discount": 0.00,
      "total": 60.00,
      "tax": 6
    },
    {
      "item_id": 102,
      "sorting_id": 2,
      "item_description": "USB Mouse",
      "invoiced_quantity": 1,
      "unit_price": 40.00,
      "price_discount": 0.00,
      "total": 40.00,
      "tax": 6
    }
  ]
}</pre></div>

    <h3>Response Example</h3>
    <div class="code-block"><pre>

    {
    "status": "ok",
    "invoice_id": 406,
    "mysynctax_uuid": "173a8b67d01a40b2c2e0dd11efcc6wxxxxx",
    "customer_status": "existing",
    "qr_lhdn": "{{url('')}}/qr_link/JGT0YJCDKZ5R23JMX89Fxxxxxx",
    "customer_id": 90,
    "result": {
        "headers": {},
        "original": {
            "submissionUid": "6RTRCJFS0EKWTRGTwxxxxxx",
            "acceptedDocuments": [
                {
                    "uuid": "JGT0YJCDKZ5R23JMXxxxxx",
                    "invoiceCodeNumber": "INV-567-123"
                }
            ],
            "rejectedDocuments": []
        },
        "exception": null
    }
}

    </pre></div>
</div>
<!-- ===================== SELF-BILLED NOTE ===================== -->
<div class="doc-section" id="selfbill-note">
    <h2>Self-Billed Credit / Debit / Refund</h2>

    <p>
        This API is used to submit Credit, Debit, or Refund Notes for
        Self-Billed Invoices that have already been submitted to LHDN.
    </p>

    <div class="code-block">
        POST {{url('')}}/api/myinvois/selfbill/note
    </div>

    <h3>Request Example</h3>
    <div class="code-block"><pre>
  {
  "mysynctax_key": "oHwIlgfhsBPP30f7",
  "mysynctax_secret": "fYxPMD2A5hPDWNI6",
  "note_type": "refund",//credit/debit/refund
  "mysynctax_uuid": "943e75230addb63b7fde84c4b2b9ce8a532ca07a",
  "sale_id_integrate": 456,
  "items": [
    {
      "item_id": 102,
      "qty": 1,
      "price": 50.00,
      "discount": 0.00,
      "tax": 3.00,
      "description": "Item rosak / dipulangkan"
    }
  ]
}</pre></div>

    <h3>Response Example</h3>
    <div class="code-block"><pre>
    {
    "status": "ok",
    "invoice_id": 404,
    "note_type": "refund",
    "mysynctax_uuid": "57f15d7c-b432-4c69-bd66-cadxxxxxx",
    "qr_lhdn": "{{url('')}}/qr_link/xaasxxxxxx",
    "result": {
        "headers": {},
        "original": {
            "submissionUid": "X6BEP9R6MN4xxxxxx",
            "acceptedDocuments": [
                {
                    "uuid": "F440ZCK2EXZBxxxxx",
                    "invoiceCodeNumber": "REFUND-NOTE-20251229000704"
                }
            ],
            "rejectedDocuments": []
        },
        "exception": null
    }
}

    </pre></div>
</div>


<!-- ===================== ADD NEW CUSTOMER ===================== -->
<div class="doc-section" id="add-customer">
    <h2>Add New Customer</h2>

    <p>
        This API is used to create or update customer master data in MySyncTax.
        It supports bulk customer submission and is commonly used by ERP or POS
        systems before invoice or self-billed submission.
    </p>

    <div class="code-block">
        POST {{url('')}}/api/myinvois/add_customer
    </div>

    <h3>Request Example</h3>
    <div class="code-block"><pre>{
  "mysynctax_key": "oHwIlgfhsBPP30f7",
  "mysynctax_secret": "fYxPMD2A5hPDWNI6",
  "customers": [
    {
      "tin_no": "EI00000000010",
      "registration_name": "AHMAD1 BIN ALI",
      "identification_type": "NRIC",
      "identification_no": "900101105555",
      "sst_registration": null,
      "phone": "0134455667",
      "email": "ahmad.ali@gmail.com",
      "city_name": "Kota Bharu",
      "postal_zone": "15000",
      "state_code": "03",//Follow below state_code from LHDN
      "country_code": "MYS",
      "address_line_1": "Lot 123, Kampung Pantai",
      "address_line_2": "Mukim Badang",
      "address_line_3": "Kelantan"
    }
  ]
}</pre></div>
<!-- ===================== LHDN STATE CODE REFERENCE ===================== -->
<div class="doc-section" id="state-code-reference">
    <h2>LHDN State Code Reference</h2>

    <p>
        Use the following <strong>state_code</strong> values when submitting
        customer or supplier address information to MySyncTax.
        These codes follow the official LHDN lookup.
    </p>

    <table class="table table-bordered" style="width:400px">
        <thead>
            <tr>
                <th>State Name</th>
                <th>State Code</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Johor</td><td>01</td></tr>
            <tr><td>Kedah</td><td>02</td></tr>
            <tr><td>Kelantan</td><td>03</td></tr>
            <tr><td>Melaka</td><td>04</td></tr>
            <tr><td>Negeri Sembilan</td><td>05</td></tr>
            <tr><td>Pahang</td><td>06</td></tr>
            <tr><td>Pulau Pinang</td><td>07</td></tr>
            <tr><td>Perak</td><td>08</td></tr>
            <tr><td>Perlis</td><td>09</td></tr>
            <tr><td>Selangor</td><td>10</td></tr>
            <tr><td>Terengganu</td><td>11</td></tr>
            <tr><td>Sabah</td><td>12</td></tr>
            <tr><td>Sarawak</td><td>13</td></tr>
            <tr><td>Wilayah Persekutuan Kuala Lumpur</td><td>14</td></tr>
            <tr><td>Wilayah Persekutuan Labuan</td><td>15</td></tr>
            <tr><td>Wilayah Persekutuan Putrajaya</td><td>16</td></tr>
            <tr><td>Not Applicable</td><td>17</td></tr>
        </tbody>
    </table>
</div>

    <h3>Response Example</h3>
    <div class="code-block"><pre>{
  "status": "ok",
  "message": "Customers processed successfully",
  "results": [
    {
      "tin_no": "EI00000000010",
      "status": "created",
      "id_customer": 93
    }
  ]
}</pre></div>
</div>


<!-- ===================== ADD / UPDATE SUPPLIER ===================== -->
<div class="doc-section" id="add-supplier">
    <h2>Add / Update Supplier</h2>

    <p>
        This API is used to create or update supplier master data in MySyncTax.
        It is commonly required for <strong>Self-Billed Invoice</strong> and
        ERP-based invoice submissions.
    </p>

    <div class="code-block">
        POST {{url('')}}/api/myinvois/add_supplier
    </div>

    <h3>Request Example</h3>
    <div class="code-block"><pre>{
  "mysynctax_key": "oHwIlgfhsBPP30f7",
  "mysynctax_secret": "fYxPMD2A5hPDWNI6",
  "supplier": [
    {
      "tin_no": "EI00000000010",
      "registration_name": "AHMAD1 BIN ALI",
      "identification_type": "NRIC",
      "identification_no": "900101105555",
      "sst_registration": null,
      "phone": "0134455667",
      "email": "ahmad.ali@gmail.com",
      "city_name": "Kota Bharu",
      "postal_zone": "15000",
      "state_code": "03",
      "country_code": "MYS",
      "address_line_1": "Lot 123, Kampung Pantai",
      "address_line_2": "Mukim Badang",
      "address_line_3": "Kelantan"
    }
  ]
}</pre></div>

    <h3>Response Example</h3>
    <div class="code-block"><pre>{
  "status": "ok",
  "message": "Supplier processed successfully",
  "results": [
    {
      "tin_no": "EI00000000010",
      "status": "updated",
      "id_customer": 93
    }
  ]
}</pre></div>
</div>


    <!-- ===================== SEND DATA API ===================== -->


    <!-- ===================== CODE SAMPLES ===================== -->

    <div class="doc-section" id="samples">

        <h2>Language Samples</h2>



        <h3>JavaScript (jQuery)</h3>

        <div class="code-block">

<pre>

$.ajax({

  url: "{{url('')}}/api/myinvois",

  type: "POST",

  contentType: "application/json",

  data: JSON.stringify(payload),

  success: function(res){

      console.log(res);

  }

});

</pre>

        </div>



        <h3>PHP (cURL)</h3>

        <div class="code-block">

<pre>

$ch = curl_init("{{url('')}}/api/myinvois");

curl_setopt($ch, CURLOPT_POST, 1);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

curl_close($ch);

echo $response;

</pre>

        </div>



        <h3>.NET C#</h3>

        <div class="code-block">

<pre>

using(var client = new HttpClient()) {

    var json = JsonConvert.SerializeObject(payload);

    var content = new StringContent(json, Encoding.UTF8, "application/json");

    var response = await client.PostAsync("{{url('')}}/api/myinvois", content);

    var result = await response.Content.ReadAsStringAsync();

}

</pre>

        </div>



        <h3>VB.NET</h3>

        <div class="code-block">

<pre>

Dim client As New HttpClient()

Dim json = JsonConvert.SerializeObject(payload)

Dim content = New StringContent(json, Encoding.UTF8, "application/json")

Dim response = Await client.PostAsync("{{url('')}}/api/myinvois", content)

Dim result = Await response.Content.ReadAsStringAsync()

</pre>

        </div>



        <h3>Python</h3>

        <div class="code-block">

<pre>

import requests

r = requests.post("{{url('')}}/api/myinvois", json=payload)

print(r.json())

</pre>

        </div>



        <h3>Java</h3>

        <div class="code-block">

<pre>

HttpClient client = HttpClient.newHttpClient();

HttpRequest request = HttpRequest.newBuilder()

    .uri(URI.create("{{url('')}}/api/myinvois"))

    .POST(HttpRequest.BodyPublishers.ofString(jsonPayload))

    .header("Content-Type", "application/json")

    .build();

HttpResponse<String> response = client.send(request, HttpResponse.BodyHandlers.ofString());

System.out.println(response.body());

</pre>

        </div>



        <h3>Node.js</h3>

        <div class="code-block">

<pre>

const axios = require("axios");

axios.post("{{url('')}}/api/myinvois", payload)

     .then(res => console.log(res.data));

</pre>

        </div>



    </div>



</div>



@endsection

