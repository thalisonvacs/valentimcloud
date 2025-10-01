<?php
require 'config.php';
check_activity_timeout();
if(!isset($_SESSION['user'])){ header('Location: login.php'); exit; }
$file = $_GET['file'] ?? '';
$path = realpath(UPLOAD_DIR . $file);
if($path && strpos($path, realpath(UPLOAD_DIR)) === 0 && file_exists($path)){
  header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename="'.basename($path).'"');
  header('Content-Length: ' . filesize($path));
  readfile($path);
  exit;
}
http_response_code(404); echo "Arquivo não encontrado.";
