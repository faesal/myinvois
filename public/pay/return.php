<?php
/**
 * Payment Return Handler (string billcode aware)
 * Example:
 *   ?status_id=1&billcode=v9xcgwpf&order_id=46&msg=ok&transaction_id=TP2508250284802668
 *
 * Status mapping:
 *   1 = success
 *   2 = pending
 *   3 = failed (unsuccessful)
 *   4 = pending
 */

@ini_set('display_errors', '0');
@error_reporting(E_ALL & ~E_NOTICE);
date_default_timezone_set('Asia/Kuala_Lumpur');

/* -------------------- Small helpers -------------------- */
function starts_with($h, $n){ return $n !== '' && strpos($h,$n)===0; }
function ends_with($h,$n){ if($n==='')return true; return substr($h,-strlen($n))===$n; }

function project_root() {
    // /project/public/payment/return.php -> /project
    $twoUp = dirname(__DIR__, 2);
    if (is_file($twoUp.'/.env')) return $twoUp;
    // /project/payment/return.php -> /project
    $oneUp = dirname(__DIR__, 1);
    if (is_file($oneUp.'/.env')) return $oneUp;
    return $twoUp;
}

function log_dir() {
    $root = project_root();
    $p = $root . '/storage/logs';
    if (!is_dir($p)) { @mkdir($p, 0775, true); if (!is_dir($p)) return __DIR__; }
    return $p;
}
function log_line(array $data) {
    $line = '['.date('Y-m-d H:i:s').'] return.php '.json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
    @file_put_contents(log_dir().'/payment-return.log', $line, FILE_APPEND);
}

/* -------------------- Env loading (Dotenv or fallback) -------------------- */
function load_env() {
    $root = project_root();
    $envPath = $root . '/.env';
    $autoload = $root . '/vendor/autoload.php';

    if (is_file($autoload)) {
        require_once $autoload;
        if (class_exists('Dotenv\\Dotenv')) {
            Dotenv\Dotenv::createImmutable($root)->safeLoad();
            return;
        }
    }
    if (is_file($envPath)) {
        $lines = @file($envPath, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            foreach ($lines as $line) {
                $t = ltrim($line);
                if ($t==='' || $t[0]==='#' || starts_with($t,'##')) continue;
                if (preg_match('/^\s*([A-Za-z0-9_]+)\s*=\s*(.*)\s*$/', $line, $m)) {
                    $key = $m[1]; $val = trim($m[2]);
                    if ((starts_with($val,'"')&&ends_with($val,'"'))||(starts_with($val,"'")&&ends_with($val,"'"))) {
                        $val = substr($val,1,-1);
                    }
                    $_ENV[$key]=$val; $_SERVER[$key]=$val; if (function_exists('putenv')) @putenv("$key=$val");
                }
            }
        }
    }
}
function env_str($k,$d=null){
    if(isset($_ENV[$k]) && $_ENV[$k]!=='') return $_ENV[$k];
    if(isset($_SERVER[$k]) && $_SERVER[$k]!=='') return $_SERVER[$k];
    $v=getenv($k); if($v===false || $v==='') return $d; return $v;
}

/* -------------------- DB -------------------- */
function db_pdo() {
    $driver = strtolower((string)env_str('DB_CONNECTION','mysql'));
    if ($driver!=='mysql') throw new RuntimeException('Only mysql is supported (DB_CONNECTION='.$driver.').');
    $host = env_str('DB_HOST','127.0.0.1');
    $port = env_str('DB_PORT','3306');
    $db   = env_str('DB_DATABASE');
    $user = env_str('DB_USERNAME');
    $pass = env_str('DB_PASSWORD','');
    if(!$db || !$user) throw new RuntimeException('Database credentials missing from .env (DB_DATABASE / DB_USERNAME).');

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    return new PDO($dsn, $user, $pass, array(
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // allow reusing placeholders safely across drivers
        PDO::ATTR_EMULATE_PREPARES   => true,
    ));
}

/** Check if orders.billcode column is string-like (char/text) or integer */
function billcode_column_is_string(PDO $pdo) {
    static $isString = null;
    if ($isString !== null) return $isString;
    $row = $pdo->query("SHOW COLUMNS FROM `orders` LIKE 'billcode'")->fetch();
    $type = strtolower($row['Type'] ?? '');
    $isString = (strpos($type,'char')!==false || strpos($type,'text')!==false);
    return $isString;
}

/* -------------------- Request parsing -------------------- */
function get_request() {
    $in = array_merge($_GET ?? array(), $_POST ?? array());

    $statusId = null;
    if (isset($in['status_id'])) $statusId = (int)$in['status_id'];
    elseif (isset($in['status'])) $statusId = (int)$in['status'];

    // billcode (keep as STRING, up to 64)
    $billRaw  = $in['billcode'] ?? ($in['billCode'] ?? ($in['bill_code'] ?? null));
    $billcode = $billRaw !== null ? substr((string)$billRaw, 0, 64) : null;

    // transaction id (cap 200)
    $txn = $in['transaction_id'] ?? ($in['transactionId'] ?? ($in['txn_id'] ?? ($in['txnid'] ?? null)));
    if ($txn !== null) $txn = substr((string)$txn, 0, 200);

    // order id (int)
    $orderId = $in['order_id'] ?? ($in['orderId'] ?? ($in['id'] ?? null));
    $orderId = ($orderId !== null && preg_match('/^\d+$/', (string)$orderId)) ? (int)$orderId : null;

    // Optional extras
    $amount = $in['amount'] ?? ($in['amt'] ?? null);
    $method = $in['payment_method'] ?? ($in['channel'] ?? null);
    $msg    = $in['msg'] ?? null;

    // Map to text
    $statusText = 'unknown';
    switch ($statusId) {
        case 1: $statusText='success'; break;
        case 2: $statusText='pending'; break;
        case 3: $statusText='failed';  break;
        case 4: $statusText='pending'; break;
    }

    return array(
        'status_id'      => $statusId,
        'status_text'    => $statusText,
        'billcode'       => $billcode,        // STRING
        'transaction_id' => $txn,
        'order_id'       => $orderId,
        'amount'         => $amount,
        'payment_method' => $method,
        'msg'            => $msg,
        'raw'            => $in,
    );
}

/* -------------------- Update logic -------------------- */
function select_exists(PDO $pdo, $whereSql, $params) {
    $sql = "SELECT id FROM orders $whereSql LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return (bool)$st->fetchColumn();
}

function update_order(PDO $pdo, array $req) {
    // ---------- WHERE (prefer order_id) ----------
    $where = '';
    $whereKey = '';

    if ($req['order_id'] !== null) {
        // force integer
        $where = 'WHERE id = ' . (int)$req['order_id'];
        $whereKey = 'id';
    } elseif (!empty($req['billcode'])) {
        if (billcode_column_is_string($pdo)) {
            $where = 'WHERE billcode = ' . $pdo->quote($req['billcode']);
            $whereKey = 'billcode(str)';
        } elseif (preg_match('/^\d+$/', (string)$req['billcode'])) {
            $where = 'WHERE billcode = ' . (int)$req['billcode'];
            $whereKey = 'billcode(int)';
        }
    }

    if ($where === '' && !empty($req['transaction_id'])) {
        $where = 'WHERE transaction_id = ' . $pdo->quote($req['transaction_id']);
        $whereKey = 'transaction_id';
    }

    if ($where === '') {
        return ['updated' => false, 'reason' => 'No suitable key (id/billcode/transaction_id)'];
    }

    // ---------- SET ----------
    $set = [];
    // payment_status: success/pending/failed
    $set[] = 'payment_status = ' . $pdo->quote($req['status_text']);
    $set[] = 'updated_at = NOW()';

    // always set txn id if given
    if (!empty($req['transaction_id'])) {
        $set[] = 'transaction_id = ' . $pdo->quote($req['transaction_id']);
    }

    // billcode storage (if column allows)
    $billcodeWasStored = false;
    if (!empty($req['billcode'])) {
        if (billcode_column_is_string($pdo)) {
            $set[] = 'billcode = ' . $pdo->quote($req['billcode']);
            $billcodeWasStored = true;
        } elseif (preg_match('/^\d+$/', (string)$req['billcode'])) {
            $set[] = 'billcode = ' . (int)$req['billcode'];
            $billcodeWasStored = true;
        }
    }

    // Build ONE tnx_info append (if needed)
    $extras = [];
    if (!empty($req['billcode']) && !$billcodeWasStored) {
        $extras[] = 'billcode=' . $req['billcode'];
    }
    if (!empty($req['payment_method'])) {
        $extras[] = 'method=' . $req['payment_method'];
    }
    if (!empty($req['msg'])) {
        $extras[] = 'msg=' . $req['msg'];
    }

 
    $where = 'WHERE id = ' . (int)$req['order_id'];
    $whereKey = 'id';

    $sql = 'UPDATE orders SET ' . implode(', ', $set) . ' ' . $where . ' LIMIT 1';

    // Execute without parameters
    $affected = $pdo->exec($sql);

    // Optional: log the final SQL for debugging
    // log_line(['sql' => $sql, 'where_key' => $whereKey, 'affected' => $affected]);

    if ($affected === 1) {
        return ['updated' => true, 'reason' => 'ok'];
    }

    // If 0 rows affected, check if the row exists (means "no change")
    $exists = false;
    $checkSql = 'SELECT id FROM orders ' . $where . ' LIMIT 1';
    $rs = $pdo->query($checkSql);
    if ($rs && $rs->fetchColumn()) {
        $exists = true;
    }

    return $exists
        ? ['updated' => true, 'reason' => 'no_changes']
        : ['updated' => false, 'reason' => 'not_found'];
}



/* -------------------- Main -------------------- */
try {
    load_env();
    $req = get_request();

    // Quick diagnostics: add &diag=1 to the URL temporarily
    if (isset($_GET['diag'])) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "ROOT: ".project_root()."\n";
        echo ".env: ".(is_file(project_root().'/.env')?'found':'missing')."\n";
        echo "DB: ".env_str('DB_DATABASE','(null)')." / USER: ".(env_str('DB_USERNAME')?'(set)':'(null)')."\n";
        echo "order_id: ".($req['order_id']===null?'(null)':$req['order_id'])."\n";
        echo "billcode: ".($req['billcode']??'(null)')."\n";
        echo "txn: ".($req['transaction_id']??'(null)')."\n";
        exit;
    }

    $logCopy = $req; $logCopy['raw']=null; $logCopy['_from']=$_SERVER['REMOTE_ADDR']??'';
    log_line($logCopy);

    if ($req['status_id'] === null) throw new InvalidArgumentException('Missing status_id');

    $pdo = db_pdo();
    $res = update_order($pdo, $req);

    $human = ($req['status_text']==='success'?'Successful transaction':($req['status_text']==='pending'?'Pending transaction':($req['status_text']==='failed'?'Unsuccessful transaction':'Unknown status')));

    // Optional JSON: /payment/return.php?...&format=json
    if (isset($_GET['format']) && $_GET['format']==='json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array(
            'ok' => $res['updated'],
            'reason' => $res['reason'],
            'status' => $req['status_text'],
            'order_id' => $req['order_id'],
            'billcode' => $req['billcode'],
            'transaction_id' => $req['transaction_id'],
        ));
        exit;
    }

    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    if($req['status_id']==1){
    header('Location: ' . $scheme . '://' . $_SERVER['HTTP_HOST'] . '/email/'.$req['order_id']);
    }else{
    header('Location: ' . $scheme . '://' . $_SERVER['HTTP_HOST'] . '/user/orders');
    }
    exit;
} catch (Throwable $e) {
    log_line(array('error'=>$e->getMessage()));
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Payment return error: '.$e->getMessage();
}
