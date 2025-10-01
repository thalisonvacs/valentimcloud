<?php
session_start();
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}
$userName = $_SESSION['name'] ?? $_SESSION['user'];
$userRole = $_SESSION['role'] ?? 'user';
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>VALENTIM CLOUD — Painel</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<style>
:root{--accent:#00e0ff;--accent2:#0072ff;--bg1:#071226;--bg2:#071b2a}
*{box-sizing:border-box}
body{
  margin:0;min-height:100vh;display:flex;flex-direction:column;
  background:linear-gradient(135deg,var(--bg1),var(--bg2));
  font-family:Inter, "Segoe UI", Roboto, Arial, sans-serif;color:#eaf6ff;
}
header{
  background:rgba(0,0,0,0.3);backdrop-filter:blur(6px);
  display:flex;justify-content:space-between;align-items:center;
  padding:16px 32px;box-shadow:0 4px 20px rgba(0,0,0,0.5);
}
header h1{margin:0;font-size:20px;color:var(--accent);display:flex;align-items:center;gap:10px}
header nav a{
  margin-left:18px;color:#cfe9ff;text-decoration:none;font-weight:600;transition:.2s;
}
header nav a:hover{color:var(--accent)}

.container{flex:1;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:24px;padding:32px}
.card{
  background:linear-gradient(180deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02));
  border-radius:16px;padding:24px;box-shadow:0 10px 30px rgba(0,0,0,0.6);
  backdrop-filter:blur(8px);transition:.25s;
}
.card:hover{transform:translateY(-5px);box-shadow:0 14px 40px rgba(0,0,0,0.7)}
.card h2{margin-top:0;font-size:18px;color:var(--accent);display:flex;align-items:center;gap:10px}
.card p{color:#9fb6c8;font-size:14px}

footer{
  background:rgba(0,0,0,0.3);backdrop-filter:blur(6px);
  padding:12px;text-align:center;font-size:13px;color:#9fb6c8;
    
}
    .btn {
    display:inline-block;
    padding:10px 20px;
    background:#00c6ff;
    color:#fff;
    border-radius:5px;
    text-decoration:none;
    font-weight:bold;
}
.btn:hover{
    background:#0072ff;
}

</style>
</head>
<body>
<header>
  <h1><i class="fa-solid fa-cloud"></i> Valentim Cloud</h1>
  <nav>
    <span>👤 <?=htmlspecialchars($userName)?> (<?=htmlspecialchars($userRole)?>)</span>
    <a href="manager.php"><i class="fa fa-folder"></i> Arquivos</a>
    <a href="logout.php"><i class="fa fa-right-from-bracket"></i> Sair</a>
  </nav>
</header>

<div class="container">
 <div class="card">
    <h2><i class="fa fa-folder-open"></i> Gerenciador de Arquivos</h2>
    <p>Acesse seus arquivos e pastas com segurança.</p>
    <a href="manager.php" class="btn">Abrir Gerenciador</a>
</div>

  <div class="card">
    <h2><i class="fa fa-key"></i> Cofre de Senhas</h2>
    <p>Armazene suas credenciais com segurança no cofre integrado.</p>
       <a href="vault.php" class="btn">Abrir Cofre de Senhas</a>
  </div>
  <div class="card">
    <h2><i class="fa fa-user-shield"></i> Perfil</h2>
    <p>Gerencie suas informações pessoais e preferências de acesso.</p>
  </div>
  <?php if($userRole === 'admin'): ?>
  <div class="card">
    <h2><i class="fa fa-gears"></i> Administração</h2>
    <p>Ferramentas exclusivas para gerenciamento de usuários e sistema.</p>
  </div>
  <?php endif; ?>
</div>

<footer>
  © <?=date('Y')?> Valentim Cloud — Todos os direitos reservados
</footer>
</body>
</html>
