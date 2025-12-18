<?php
$data = json_decode(file_get_contents("vip.json"), true);
$pwd = $_POST["password"] ?? "";
$device = md5($_SERVER["HTTP_USER_AGENT"]);
$today = date("Y-m-d");

foreach($data as &$v){
  if($v["password"] === $pwd){
    if($v["expiry"] < $today){
      echo "expired"; exit;
    }
    if($v["device"] && $v["device"] !== $device){
      echo "device"; exit;
    }
    if(!$v["device"]){
      $v["device"] = $device;
      file_put_contents("vip.json", json_encode($data,JSON_PRETTY_PRINT));
    }
    echo "ok"; exit;
  }
}
echo "wrong";
