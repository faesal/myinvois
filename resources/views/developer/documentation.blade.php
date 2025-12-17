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

        <h2>Sample JSON</h2>



        <p>This example will generate <strong>completed</strong> status.</p>



        <div class="code-block">

<pre>{

  "mysynctax_key": "F3kW8nP1zS0aL9tQ4vB7uR6yJ2cX5mT8gH1eK3oN",

  "mysynctax_secret": "K8pR3sL1vF9gQ2wE6nC4bT7jH0mD5yU1aX8zV3kS4tN9fG2qM7rY0uJ5hP",



  "invoice_no": "INV-9002029",

  "sale_id_integrate": 9002029,

  "total_amount": 210.00,

  "payment_note_term": "CASH",

  "items": [

    {

      "item_id":12,

      "sorting_id": 1,

      "invoiced_quantity": 2,

      "unit_price": 60.00,

      "item_description": "Premium Nasi Ayam Set",

      "total": 120.00,

      "price_discount": 0

    },

    {

      "item_id":13,

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







    <!-- ===================== SEND DATA API ===================== -->

    <div class="doc-section" id="send-data">

        <h2>Send Data</h2>



        <p>Submit data using the following endpoint:</p>



        <div class="code-block"><pre>POST https://mysynctax.com/v5/api/myinvois</pre></div>



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

  "qr_url": "https://mysynctax.com/v5/redirect/9QqIe39"

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







    <!-- ===================== CODE SAMPLES ===================== -->

    <div class="doc-section" id="samples">

        <h2>Language Samples</h2>



        <h3>JavaScript (jQuery)</h3>

        <div class="code-block">

<pre>

$.ajax({

  url: "https://mysynctax.com/v5/api/myinvois",

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

$ch = curl_init("https://mysynctax.com/v5/api/myinvois");

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

    var response = await client.PostAsync("https://mysynctax.com/v5/api/myinvois", content);

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

Dim response = Await client.PostAsync("https://mysynctax.com/v5/api/myinvois", content)

Dim result = Await response.Content.ReadAsStringAsync()

</pre>

        </div>



        <h3>Python</h3>

        <div class="code-block">

<pre>

import requests

r = requests.post("https://mysynctax.com/v5/api/myinvois", json=payload)

print(r.json())

</pre>

        </div>



        <h3>Java</h3>

        <div class="code-block">

<pre>

HttpClient client = HttpClient.newHttpClient();

HttpRequest request = HttpRequest.newBuilder()

    .uri(URI.create("https://mysynctax.com/v5/api/myinvois"))

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

axios.post("https://mysynctax.com/v5/api/myinvois", payload)

     .then(res => console.log(res.data));

</pre>

        </div>



    </div>



</div>



@endsection

