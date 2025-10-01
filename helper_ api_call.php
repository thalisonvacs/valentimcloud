<?php
function api_post($payload){
  $ch = curl_init(AUTH_URL);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
  $resp = curl_exec($ch);
  curl_close($ch);
  return json_decode($resp, true);
}
?>
