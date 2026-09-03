<?php

use App\Models\Offer;
use Twilio\Rest\Client;
use App\Models\OfferProduct;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Modules\SmsSetting\App\Models\SmsSetting;
use Illuminate\Support\Facades\Validator;

if (!function_exists('validateMyInvoisSdkFormat')) {
    /**
     * Validate payload fields against LHDN MyInvois SDK format rules (Enforced from 15 August 2026).
     *
     * @param array $payload
     * @return \Illuminate\Contracts\Validation\Validator
     */
    function validateMyInvoisSdkFormat(array $payload)
    {
        $rules = [
            // 1. Supplier's Business Activity Description (Max 300 chars)
            'supplier.business_activity_description' => 'nullable|string|max:300',

            // 2. e-Invoice Code / Number (Max 50 chars)
            'invoice_no'                             => 'required|string|max:50',

            // 3. e-Invoice Date (Format: YYYY-MM-DD)
            'issue_date'                             => 'nullable|date_format:Y-m-d',

            // 4. Frequency of Billing (Max 50 chars)
            'billing_frequency'                      => 'nullable|string|max:50',

            // 6. Supplier's Bank Account Number (Max 150 chars)
            'supplier.bank_account_number'           => 'nullable|string|max:150',

            // 7. Payment Terms (Max 300 chars)
            'payment_terms'                          => 'nullable|string|max:300',

            // 8. Prepayment Reference Number (Max 150 chars)
            'prepayment_reference_number'            => 'nullable|string|max:150',

            // 9. Incoterms (Max 3 chars)
            'incoterms'                              => 'nullable|string|max:3',

            // 10. Authorisation Number for Certified Exporter (Max 300 chars)
            'certified_exporter_authorisation_number' => 'nullable|string|max:300',

            // =========================================================
            // LINE ITEMS VALIDATION
            // =========================================================
            'items'                                  => 'required|array|min:1',

            // 5. Unit of Measurement / UoM (Valid UN/ECE code format)
            'items.*.uom'                            => 'nullable|string|max:10',
            'items.*.item_description'               => 'required|string|max:300',
        ];

        $messages = [
            'supplier.business_activity_description.max'  => 'Supplier\'s Business Activity Description must not exceed 300 characters.',
            'invoice_no.max'                              => 'e-Invoice Code / Number must not exceed 50 characters.',
            'issue_date.date_format'                      => 'e-Invoice Date must follow the format YYYY-MM-DD (e.g., 2026-07-01).',
            'billing_frequency.max'                       => 'Frequency of Billing must not exceed 50 characters.',
            'supplier.bank_account_number.max'            => 'Supplier\'s Bank Account Number must not exceed 150 characters.',
            'payment_terms.max'                           => 'Payment Terms must not exceed 300 characters.',
            'prepayment_reference_number.max'             => 'Prepayment Reference Number must not exceed 150 characters.',
            'incoterms.max'                               => 'Incoterms must not exceed 3 characters.',
            'certified_exporter_authorisation_number.max' => 'Authorisation Number for Certified Exporter must not exceed 300 characters.',
            'items.*.uom.max'                             => 'Unit of Measurement (UoM) code must follow valid standard format.',
        ];

        return Validator::make($payload, $rules, $messages);
    }
}

function admin_lang(){
    return 'en';
}

function front_lang() {
    return session()->has('front_lang') ? session('front_lang') : 'en';
}



function html_decode($text){
    $decode_text = htmlspecialchars_decode($text, ENT_QUOTES);
    return $decode_text;
}

function amount($amount) {
    $amount = number_format($amount, 2, '.', ',');

    return $amount;
}


function currency($price){
    // currency information will be loaded by Session value

    $currency_icon = Session::get('currency_icon');
    $currency_code = Session::get('currency_code');
    $currency_rate = Session::get('currency_rate');
    $currency_position = Session::get('currency_position');

    $price = $price * $currency_rate;
    $price = amount($price, 2, '.', ',');

    if($currency_position == 'before_price'){
        $price = $currency_icon.$price;
    }elseif($currency_position == 'before_price_with_space'){
        $price = $currency_icon.' '.$price;
    }elseif($currency_position == 'after_price'){
        $price = $price.$currency_icon;
    }elseif($currency_position == 'after_price_with_space'){
        $price = $price.' '.$currency_icon;
    }else{
        $price = $currency_icon.$price;
    }

    return $price;
}


function check_icon(){
    return asset('frontend/assets/images/icon/check.png');
}

function spinner_icon(){
    return asset('frontend/assets/images/icon/Button.png');
}

function getAllResourceFiles($dir, &$results = array()) {
    $files = scandir($dir);
    foreach ($files as $key => $value) {
        $path = $dir ."/". $value;
        if (!is_dir($path)) {
            $results[] = $path;
        } else if ($value != "." && $value != "..") {
            getAllResourceFiles($path, $results);
        }
    }
    return $results;
}

function getRegexBetween($content) {

    preg_match_all("%\{{ __\(['|\"](.*?)['\"]\) }}%i", $content, $matches1, PREG_PATTERN_ORDER);
    preg_match_all("%\{{__\(['|\"](.*?)['\"]\)}}%i", $content, $matches1_1, PREG_PATTERN_ORDER);
    preg_match_all("%\@lang\(['|\"](.*?)['\"]\)%i", $content, $matches2, PREG_PATTERN_ORDER);
    preg_match_all("%trans\(['|\"](.*?)['\"]\)%i", $content, $matches3, PREG_PATTERN_ORDER);
    $Alldata = [$matches1[1], $matches1_1[1], $matches2[1], $matches3[1]];
    $data = [];
    foreach ($Alldata as  $value) {
        if(!empty($value)){
            foreach ($value as $val) {
                $data[$val] = $val;
            }
        }
    }
    return $data;
}

function generateLang($path = ''){

    // user panel
    $paths = getAllResourceFiles(resource_path('views'));

    $paths = array_merge($paths, getAllResourceFiles(app_path()));

    $paths = array_merge($paths, getAllResourceFiles(base_path('Modules')));

    // end user panel

    $AllData= [];
    foreach ($paths as $key => $path) {
    $AllData[] = getRegexBetween(file_get_contents($path));
    }
    $modifiedData = [];
    foreach ($AllData as  $value) {
        if(!empty($value)){
            foreach ($value as $val) {
                $modifiedData[$val] = $val;
            }
        }
    }

    $modifiedData = var_export($modifiedData, true);

    file_put_contents('lang/en/translate.php', "<?php\n return {$modifiedData};\n ?>");

}

function calculateFinalPrice($product, $price = 0)
{
    if($price == 0){
        $price = $product->offer_price > 0 ? $product->offer_price : $product->price;
    }else{
        $price = $price;
    }

    $isOfferSale = OfferProduct::where([
        "product_id" => $product->id,
        "status" => 1,
    ])->first();

    $today = date("Y-m-d H:i:s");
    if ($isOfferSale) {
        $offer = Offer::first();
        if ($offer && $offer->status == 1) {
            if ($today <= $offer->end_time) {
                $offerAmount = ($offer->offer / 100) * $price;
                $price -= $offerAmount;
            }
        }
    }

    return $price;
}



function sendMobileOTP($to_phone, $message) {
    $setting_data = SmsSetting::all();

    $sms_setting = array();

    foreach($setting_data as $data_item){
        $sms_setting[$data_item->key] = $data_item->value;
    }

    $sms_setting = (object) $sms_setting;

    if($sms_setting->twilio_status == 'active'){
        try{
            $account_sid = $sms_setting->twilio_sid;
            $auth_token = $sms_setting->twilio_auth_token;
            $twilio_number = $sms_setting->twilio_phone_number;
            $recipients = $sms_setting->default_phone_code.$to_phone;
            $client = new Client($account_sid, $auth_token);
            $client->messages->create($recipients,
                    ['from' => $twilio_number, 'body' => $message] );
        }catch(Exception $ex){
            Log::info('Twilio Failed:' . $ex->getMessage());
        }

    }

    if($sms_setting->biztech_status == 'active'){
        try{
            $apikey = $sms_setting->twilio_sid;
            $clientid = $sms_setting->twilio_sid;
            $senderid = $sms_setting->twilio_sid;
            $senderid = urlencode($senderid);
            $message = $message;
            $msg_type = true;  // true or false for unicode message
            $message  = urlencode($message);
            $mobilenumbers = $sms_setting->default_phone_code.$to_phone;//8801700000000 or 8801700000000,9100000000
            $url = "https://api.smsq.global/api/v2/SendSMS?ApiKey=$apikey&ClientId=$clientid&SenderId=$senderid&Message=$message&MobileNumbers=$mobilenumbers&Is_Unicode=$msg_type";
            $ch = curl_init();
            curl_setopt ($ch, CURLOPT_URL, $url);
            curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOBODY, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            $response = json_decode($response);

            Log::info('biztech success');

        }catch(Exception $ex){
            Log::info('Bizitech Failed:' . $ex->getMessage());
        }
    }


}
