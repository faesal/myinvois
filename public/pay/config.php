<?php
// === Basic DB + toyyibPay config (EDIT THESE) ===============================
define('DB_HOST',     '127.0.0.1');
define('DB_NAME',     'liveapps_nlbh');
define('DB_USER',     'liveapps_nlbh');
define('DB_PASS',     'D6Ee{7yp~Uy,');
define('DB_CHARSET',  'utf8mb4');

//define('TOYYIB_SECRET',   'vj9yfxtb-txk8-x076-b3mj-scuiak1bvkbv'); // your real secret
//define('TOYYIB_CATEGORY', 'z7fts4tr');    

define('TOYYIB_SECRET',   'd54034sf-3f88-vr56-mhtw-x604fmq2xkcn'); // your real secret
define('TOYYIB_CATEGORY', '1n04z34r');                               // your category code
define('TOYYIB_SANDBOX',  false); // true = dev.toyyibpay.com, false = toyyibpay.com

// Optional: your platform’s toyyibPay username + % share for split
define('PLATFORM_USERNAME',  ''); // e.g. 'mycompany' or leave '' to disable
define('PLATFORM_FEE_PCT',   0);  // e.g. 3 for 3%

// Your public URLs (must be HTTPS in production)
define('CALLBACK_URL', 'https://nlbh.liveapps88.com//pay/callback.php');
define('RETURN_URL',   'https://nlbh.liveapps88.com/pay/return.php');

// ===========================================================================

function pdo(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;
  $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET;
  $opt = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ];
  return $pdo = new PDO($dsn, DB_USER, DB_PASS, $opt);
}

function now(): string {
  return date('Y-m-d H:i:s');
}

function toyyib_create_bill(array $payload): array {
  $url = TOYYIB_SANDBOX
    ? 'https://dev.toyyibpay.com/index.php/api/createBill'
    : 'https://toyyibpay.com/index.php/api/createBill';

  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_POST           => 1,
    CURLOPT_POSTFIELDS     => http_build_query($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 25,
  ]);
  $response = curl_exec($ch);
  if (curl_errno($ch)) {
    throw new RuntimeException('cURL error: '.curl_error($ch));
  }
  curl_close($ch);

  $json = json_decode($response, true);
  if (!is_array($json)) {
    throw new RuntimeException('Invalid toyyibPay response: '.$response);
  }
  return $json;
}

function pay_url_from_bill(string $billCode): string {
  return (TOYYIB_SANDBOX ? 'https://dev.toyyibpay.com/' : 'https://toyyibpay.com/').$billCode;
}
