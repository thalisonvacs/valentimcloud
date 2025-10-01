<?php
// login.php (substitua seu arquivo atual por este)
// Usa config.php que deve definir $USERS (username => ['pass'=>..., 'name'=>..., 'role'=>...])
session_start();
require_once __DIR__ . '/config.php';

$error = '';

// Se já logado -> redireciona
if (isset($_SESSION['user'])) {
    // prefer index.php, senão manager.php
    if (file_exists(__DIR__ . '/index.php')) header('Location: index.php');
    else header('Location: manager.php');
    exit;
}

function redirect_after_login(){
    if (file_exists(__DIR__ . '/index.php')) header('Location: index.php');
    else header('Location: manager.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // aceita 'user', 'username' ou 'email' como campo de entrada
    $rawUser = $_POST['user'] ?? $_POST['username'] ?? $_POST['email'] ?? '';
    $rawPass = $_POST['password'] ?? '';

    $userInput = trim(strtolower((string)$rawUser));
    $passInput = (string)$rawPass;

    if ($userInput === '' || $passInput === '') {
        $error = 'Usuário e senha são obrigatórios.';
    } else {
        // procura usuário case-insensitive (também checa se $USERS tem 'email' dentro do array de info)
        $foundKey = null;
        foreach ($USERS as $key => $info) {
            if (strtolower($key) === $userInput) { $foundKey = $key; break; }
            if (isset($info['email']) && strtolower($info['email']) === $userInput) { $foundKey = $key; break; }
        }

        if ($foundKey === null) {
            $error = 'Usuário não encontrado.';
        } else {
            $stored = $USERS[$foundKey]['pass'] ?? '';
            $ok = false;

            // se stored parece ser um hash do password_hash (bcrypt starts with $2y$) -> password_verify
            if (strlen($stored) > 0 && (substr($stored,0,4) === '$2y$' || substr($stored,0,6) === '$argon')) {
                if (password_verify($passInput, $stored)) $ok = true;
            } else {
                // comparação direta (texto simples)
                if ($passInput === $stored) $ok = true;
            }

            if ($ok) {
                $_SESSION['user'] = $foundKey;
                $_SESSION['name'] = $USERS[$foundKey]['name'] ?? $foundKey;
                $_SESSION['role'] = $USERS[$foundKey]['role'] ?? 'user';
                $_SESSION['last_activity'] = time();
                redirect_after_login();
            } else {
                $error = 'Senha incorreta.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>VALENTIM CLOUD — Login</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<style>
:root{--accent:#00e0ff;--accent2:#0072ff;--bg1:#071226;--bg2:#071b2a}
*{box-sizing:border-box}
body{
  margin:0;height:100vh;display:flex;align-items:center;justify-content:center;
  background:linear-gradient(135deg,var(--bg1),var(--bg2));
  font-family:Inter, "Segoe UI", Roboto, Arial, sans-serif;color:#eaf6ff;
}
.card{
  width:420px;background:linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
  border-radius:16px;padding:32px;box-shadow:0 20px 50px rgba(2,6,23,0.7);backdrop-filter: blur(8px);
}
.brand{display:flex;align-items:center;gap:12px;margin-bottom:18px}
.brand .logo{font-size:28px;color:var(--accent);width:56px;height:56px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--accent),var(--accent2));box-shadow:0 6px 20px rgba(0,198,255,0.08)}
.brand h1{font-size:20px;margin:0}
.subtitle{color:#9fb6c8;margin-bottom:18px}

/* form */
.form-row{margin-bottom:14px;position:relative}
.input{
  width:100%;padding:12px 14px;border-radius:10px;border:none;background:rgba(255,255,255,0.03);color:#eaf6ff;font-size:15px;
  outline:none;transition:all .18s ease;
}
.input:focus{transform:translateY(-2px);box-shadow:0 6px 18px rgba(0,0,0,0.6)}
.icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--accent);font-size:14px}
.input.with-icon{padding-left:40px}

/* actions */
.actions{display:flex;gap:12px;margin-top:6px}
.btn{flex:1;padding:12px;border-radius:10px;border:none;font-weight:700;cursor:pointer}
.btn.primary{background:linear-gradient(90deg,var(--accent),var(--accent2));color:#001824;box-shadow:0 8px 30px rgba(0,198,255,0.06)}
.btn.ghost{background:transparent;border:1px solid rgba(255,255,255,0.04);color:#cfe9ff}

/* error */
.alert{margin-top:12px;padding:10px;border-radius:8px;background:rgba(255,90,90,0.07);color:#ffb6b6}

/* footer */
.footer{margin-top:18px;font-size:12px;color:#9fb6c8;text-align:center}
</style>
</head>
<body>
  <div class="card">
    <div class="brand">
      <div class="logo"><i class="fa-solid fa-cloud"></i></div>
      <div>
        <h1>Valentim Cloud</h1>
        <div class="subtitle">Acesso seguro ao seu gerenciador</div>
      </div>
    </div>

    <form method="post" autocomplete="off" novalidate>
      <div class="form-row">
        <span class="icon"><i class="fa fa-user"></i></span>
        <input class="input with-icon" name="username" placeholder="Usuário / email" />
      </div>

      <div class="form-row">
        <span class="icon"><i class="fa fa-lock"></i></span>
        <input class="input with-icon" name="password" type="password" placeholder="Senha" />
      </div>

      <div class="actions">
        <button class="btn primary" type="submit">Entrar</button>
        <button class="btn ghost" type="button" onclick="document.querySelector('[name=username]').value='';document.querySelector('[name=password]').value=''">Limpar</button>
      </div>

      <?php if($error): ?><div class="alert"><?=htmlspecialchars($error)?></div><?php endif; ?>
    </form>

    <div class="footer">© <?=date('Y')?> Valentim Cloud — Projeto privado</div>
  </div>
</body>
</html>
