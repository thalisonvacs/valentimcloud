<?php
require 'config.php';
require 'api_call.php';
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if($action === 'verify'){
  $code = $input['code'] ?? '';
  $trust = $input['trust_code'] ?? '';
  if(!isset($_SESSION['pending_email'])) {
    echo json_encode(['ok'=>false,'error'=>'session_missing']); exit;
  }
  $email = $_SESSION['pending_email'];
  // chama apps script verify_code
  $resp = api_post(['action'=>'verify_code','email'=>$email,'code'=>$code,'trust_code'=>$trust]);
  if($resp && isset($resp['ok']) && $resp['ok']){
    // autenticar localmente: setar sessão
    $_SESSION['user'] = $resp['email'];
    $_SESSION['name'] = $resp['name'] ?? $resp['email'];
    $_SESSION['role'] = $resp['role'] ?? 'user';
    // limpar pending
    unset($_SESSION['pending_email']);
    unset($_SESSION['pending_name']);
    $_SESSION['last_activity'] = time();
    echo json_encode(['ok'=>true]);
    exit;
  } else {
    echo json_encode(['ok'=>false,'error'=>$resp['error'] ?? 'invalid']);
    exit;
  }
}

if($action === 'resend'){
  if(!isset($_SESSION['pending_email'])) { echo json_encode(['ok'=>false,'error'=>'session_missing']); exit; }
  $email = $_SESSION['pending_email'];
  $resp = api_post(['action'=>'send_code','email'=>$email]);
  if($resp && isset($resp['ok']) && $resp['ok']) echo json_encode(['ok'=>true]);
  else echo json_encode(['ok'=>false,'error'=>'fail_send']);
  exit;
}

echo json_encode(['ok'=>false,'error'=>'unknown']);
