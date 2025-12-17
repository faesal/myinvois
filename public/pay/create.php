<?php
  $some_data = array(
    'enterpriseUserSecretKey' => 'd54034sf-3f88-vr56-mhtw-x604fmq2xkcn',
    'fullname' => 'John Doe Sdn Bhd',
    'username' => 'nlbh',
    'email' => 'nlbh@xideasoft.com',
    'password' => 'xabc123',
    'phone' => '01110391337',
    'bankAccountType' => '1',
    'bank'=>1,
    'accountNo'=>'162263282063',
    'accountHolderName'=>'John Doe',
    'registrationNo'=>'BBYUUI',
    'package' => 1
  );

  $curl = curl_init();
  curl_setopt($curl, CURLOPT_POST, 1);
  curl_setopt($curl, CURLOPT_URL, 'https://toyyibpay.com/index.php/api/createAccount');  
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $some_data);

  $result = curl_exec($curl);
  $info = curl_getinfo($curl);  
  curl_close($curl);
  $obj = json_decode($result);
  echo $result;
?>