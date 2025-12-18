<?php
// ===== CONFIG =====
$BOT_TOKEN = getenv("BOT_TOKEN");
$ADMIN_ID  = getenv("ADMIN_ID");
$DATA_FILE = "vip.json";

$update = json_decode(file_get_contents("php://input"), true);
if(!$update) exit;

$msg = $update["message"] ?? null;
if(!$msg) exit;

$chat_id = $msg["chat"]["id"];
$text = trim($msg["text"] ?? "");

// ===== ADMIN CHECK =====
if($chat_id != $ADMIN_ID){
  send("❌ You are not admin");
  exit;
}

// ===== LOAD VIP DATA =====
$vip = json_decode(file_get_contents($DATA_FILE), true);

// ===== COMMANDS =====
if(strpos($text,"/addvip") === 0){
  $p = explode(" ", $text);
  if(count($p) < 3){
    send("Usage:\n/addvip password days");
    exit;
  }
  $pass = $p[1];
  $days = intval($p[2]);
  $exp  = date("Y-m-d", strtotime("+$days days"));

  $vip[] = [
    "password"=>$pass,
    "expiry"=>$exp,
    "device"=>""
  ];
  save($vip);
  send("✅ VIP Added\n🔑 $pass\n⏰ Exp: $exp");
}

elseif(strpos($text,"/removevip") === 0){
  $p = explode(" ", $text);
  $pass = $p[1] ?? "";
  $vip = array_values(array_filter($vip, fn($v)=>$v["password"]!=$pass));
  save($vip);
  send("❌ VIP Removed: $pass");
}

elseif($text=="/viplist"){
  if(!$vip){ send("VIP list empty"); exit; }
  $out="👑 VIP LIST\n\n";
  foreach($vip as $v){
    $out.="🔑 {$v['password']} | ⏰ {$v['expiry']}\n";
  }
  send($out);
}

elseif(strpos($text,"/extend") === 0){
  $p = explode(" ", $text);
  if(count($p)<3){ send("Usage: /extend password days"); exit; }
  foreach($vip as &$v){
    if($v["password"]==$p[1]){
      $v["expiry"] = date("Y-m-d", strtotime($v["expiry"]." +".$p[2]." days"));
      save($vip);
      send("⏳ Extended\n{$v['password']} → {$v['expiry']}");
      exit;
    }
  }
  send("VIP not found");
}

elseif($text=="/cleanexpired"){
  $today = date("Y-m-d");
  $vip = array_values(array_filter($vip, fn($v)=>$v["expiry"]>=$today));
  save($vip);
  send("🧹 Expired VIP cleaned");
}

else{
  send("❓ Unknown command");
}

// ===== FUNCTIONS =====
function send($t){
  global $BOT_TOKEN, $chat_id;
  file_get_contents("https://api.telegram.org/bot$BOT_TOKEN/sendMessage?chat_id=$chat_id&text=".urlencode($t));
}
function save($d){
  global $DATA_FILE;
  file_put_contents($DATA_FILE, json_encode($d,JSON_PRETTY_PRINT));
}
