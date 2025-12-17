<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
require __DIR__.'/config.php';

// 1) order_id from GET
$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
if ($orderId <= 0) {
  http_response_code(400);
  exit('Missing or invalid order_id');
}

// 2) Load order + restaurant
$sql = "SELECT o.id, o.restaurant_id, o.is_guest, o.delivery_address, o.grand_total,
               o.billcode, o.payment_status,
               r.restaurant_name, r.api_username
        FROM orders o
        LEFT JOIN restaurants r ON r.id = o.restaurant_id
        WHERE o.id = :id
        LIMIT 1";
$stmt = pdo()->prepare($sql);
$stmt->execute([':id' => $orderId]);
$order = $stmt->fetch();

if (!$order) {
  http_response_code(404);
  exit('Order not found');
}

// 3) Payer info (from guest delivery_address if available)
$contactName  = 'Customer';
$contactEmail = '';
$contactPhone = '';

if ((int)$order['is_guest'] === 1) {
  $addr = json_decode($order['delivery_address'] ?? '{}', true);
  $contactName  = $addr['contact_person_name']  ?? 'Guest';
  $contactEmail = $addr['contact_person_email'] ?? '';
  $contactPhone = $addr['contact_person_number'] ?? '';
}

// 4) Amount (in sen)
$grandTotalRm = (float)$order['grand_total'];
$amountSen    = (int) round($grandTotalRm * 100);
if ($amountSen <= 0) {
  http_response_code(400);
  exit('Grand total must be > 0');
}

// 5) Build split: 1 sen to toyyib charge, remainder to restaurant API_USERNAME
$split = [];

// Add toyyib 1 sen charge (if amount allows)
$chargeSen = min(100, $amountSen);
if ($chargeSen > 0) {
  $split[] = [
    'id'     => 'toyyib_charge', // **this is the toyyib account to receive RM0.01**
    'amount' => '100'
  ];
}

$restaurantUser = trim((string)($order['api_username'] ?? ''));
$restaurantSen  = max(0, $amountSen - $chargeSen-100);

if ($restaurantUser === '') {
  http_response_code(500);
  exit('Restaurant API_USERNAME missing for split payment.');
}
if ($restaurantSen <= 0) {
  http_response_code(500);
  exit('Order amount too small for split.');
}

$split[] = [
  'id'     => $restaurantUser,
  'amount' => (string)$restaurantSen
];

// Flags
$billSplitPayment    = 1;
$billSplitPaymentArg = json_encode($split, JSON_UNESCAPED_SLASHES);

// 6) Create bill payload
$billName = $order['restaurant_name'] ?: ('Order #'.$orderId);
$payload = [
  'userSecretKey'            => TOYYIB_SECRET,
  'categoryCode'             => TOYYIB_CATEGORY,
  'billName'                 => $billName,
  'billDescription'          => 'Order #'.$orderId.' – '.$billName,
  'billPriceSetting'         => 1,
  'billPayorInfo'            => 0,
  'billAmount'               => '100', // sen
  'billTo'                   => $contactName,
  'billEmail'                => $contactEmail,
  'billPhone'                => $contactPhone,
  'billExternalReferenceNo'  => $orderId,
  'billSplitPayment'         => 1,
  'billSplitPaymentArgs'     => '',
  //'billSplitPayment'         => 1,
  //'billSplitPaymentArgs'     => $billSplitPaymentArg,
  'billCallbackUrl'          => CALLBACK_URL,
  'billReturnUrl'            => RETURN_URL,
];


// 7) Create bill with toyyibPay
try {
  $json = toyyib_create_bill($payload);
} catch (Throwable $e) {
  http_response_code(502);
  exit('Payment gateway error: '.$e->getMessage());
}

// 8) Handle response, save BillCode, redirect
if (isset($json[0]['BillCode'])) {
  $billCode = $json[0]['BillCode'];

  $upd = pdo()->prepare(
    "UPDATE orders
       SET billcode = :bill,
           payment_method = 'toyyibpay',
           payment_status = IFNULL(payment_status,'pending'),
           updated_at = :now
     WHERE id = :id"
  );
  $upd->execute([
    ':bill' => $billCode,
    ':now'  => now(),
    ':id'   => $orderId,
  ]);

  header('Location: '.pay_url_from_bill($billCode));
  exit;
}

// If we reach here, something went wrong
http_response_code(500);
echo 'Error creating bill:<br><pre>'.htmlspecialchars(print_r($json, true)).'</pre>';
