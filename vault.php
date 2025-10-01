<?php
// vault.php (versão segura: visualização via AJAX, auto-hide em 15s)
session_start();
require_once __DIR__ . '/config.php';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// Ensure storage
$dataDir = dirname(PASSWORDS_FILE);
if(!file_exists($dataDir)) mkdir($dataDir, 0777, true);
if(!file_exists(PASSWORDS_FILE)) file_put_contents(PASSWORDS_FILE, json_encode(new stdClass()));

// Helpers (encryption)
function encrypt_data($plaintext){
    $key = hash('sha256', SECRET_KEY, true);
    $iv  = openssl_random_pseudo_bytes(16);
    $cipher = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $cipher);
}
function decrypt_data($blob){
    if(!$blob) return '';
    $data = base64_decode($blob);
    if(strlen($data) < 17) return '';
    $key = hash('sha256', SECRET_KEY, true);
    $iv = substr($data, 0, 16);
    $cipher = substr($data, 16);
    return openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
}
function load_store(){ 
    $raw = file_get_contents(PASSWORDS_FILE);
    $arr = json_decode($raw, true);
    if(!is_array($arr)) $arr = [];
    return $arr;
}
function save_store($arr){
    file_put_contents(PASSWORDS_FILE, json_encode($arr, JSON_PRETTY_PRINT));
}
function clean_cpf($s){ return preg_replace('/\D+/', '', (string)$s); }

// Vault session validity (10 minutes)
$vault_ttl = 10*60;
if(isset($_SESSION['vault_verified']) && (time() - $_SESSION['vault_verified'] > $vault_ttl)){
    unset($_SESSION['vault_verified']);
}

// --- AJAX endpoint: return decrypted password JSON
if(isset($_GET['action']) && $_GET['action'] === 'get_password'){
    header('Content-Type: application/json; charset=utf-8');
    $label = $_GET['label'] ?? '';
    $label = (string)$label;

    // require vault_verified
    if(!isset($_SESSION['vault_verified'])){
        echo json_encode(['ok'=>false,'error'=>'Você precisa validar o CPF para ver as senhas.']);
        exit;
    }
    $store = load_store();
    if(!isset($store[$user][$label])){
        echo json_encode(['ok'=>false,'error'=>'Item não encontrado.']);
        exit;
    }
    $plain = decrypt_data($store[$user][$label]);
    $obj = json_decode($plain, true);
    $password = $obj['password'] ?? '';
    // For extra safety, do not return long strings or sensitive metadata other than password itself
    echo json_encode(['ok'=>true,'password'=>$password]);
    exit;
}

// --- Normal POST flow (CPF check, add, delete) ---
$step = 'ask_cpf';
$msg = '';
$err = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    // CPF verification
    if(isset($_POST['cpf_check'])){
        $input = clean_cpf($_POST['cpf'] ?? '');
        $expected = isset($USERS[$user]['cpf']) ? clean_cpf($USERS[$user]['cpf']) : '';
        if($expected !== '' && $input === $expected){
            $_SESSION['vault_verified'] = time();
            $step = 'vault';
        } else {
            $err = 'CPF incorreto.';
            $step = 'ask_cpf';
        }
    }

    // Add entry
    if(isset($_POST['add_pass']) && isset($_SESSION['vault_verified'])){
        $label = trim($_POST['label'] ?? '');
        $senha = $_POST['senha'] ?? '';
        if($label === '' || $senha === ''){
            $err = 'Preencha todos os campos.';
            $step = 'vault';
        } else {
            $store = load_store();
            if(!isset($store[$user]) || !is_array($store[$user])) $store[$user] = [];
            $store[$user][$label] = encrypt_data(json_encode(['password'=>$senha,'created'=>time()]));
            save_store($store);
            $msg = "Senha '$label' adicionada.";
            $step = 'vault';
        }
    }

    // Delete label
    if(isset($_POST['delete_label']) && isset($_SESSION['vault_verified'])){
        $lbl = $_POST['delete_label'];
        $store = load_store();
        if(isset($store[$user][$lbl])){
            unset($store[$user][$lbl]);
            save_store($store);
            $msg = "Senha removida.";
        } else $err = "Item não encontrado.";
        $step = 'vault';
    }
}

// if already verified in session
if(isset($_SESSION['vault_verified'])) $step = 'vault';

// load user entries
$user_entries = [];
if($step === 'vault'){
    $store = load_store();
    if(isset($store[$user]) && is_array($store[$user])){
        $user_entries = $store[$user];
    } else $user_entries = [];
}

// ---- HTML / UI below ----
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Valentim Cloud — Cofre de Senhas</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<style>
:root{--accent:#00e0ff;--accent2:#0072ff;--bg1:#071226;--bg2:#071b2a}
*{box-sizing:border-box}
body{margin:0;height:100vh;display:flex;flex-direction:column;background:linear-gradient(135deg,var(--bg1),var(--bg2));font-family:Inter,Arial,sans-serif;color:#eaf6ff}
.header{display:flex;justify-content:space-between;align-items:center;padding:16px 24px;background:rgba(0,0,0,0.25);backdrop-filter:blur(6px)}
.header h1{color:var(--accent);margin:0}
.container{padding:24px;display:flex;gap:20px;flex-wrap:wrap}
.card{background:linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01));padding:20px;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,0.6);min-width:320px}
.form-row{margin-bottom:12px}
.input{width:100%;padding:10px;border-radius:8px;border:none;background:rgba(255,255,255,0.03);color:#eaf6ff}
.btn{padding:10px 14px;border-radius:8px;border:none;background:var(--accent);color:#001824;font-weight:700;cursor:pointer}
.btn.ghost{background:transparent;border:1px solid rgba(255,255,255,0.04);color:#cfe9ff}
.list{min-width:420px;max-width:900px}
.list table{width:100%;border-collapse:collapse}
.list th,.list td{padding:10px;border-bottom:1px solid rgba(255,255,255,0.03);text-align:left}
.icon{color:var(--accent)}
.notice{padding:10px;border-radius:8px;margin-bottom:10px}
.notice.success{background:rgba(0,255,127,0.06);color:#bff7d6}
.notice.error{background:rgba(255,60,60,0.06);color:#ffbdbd}
.link{color:var(--accent);text-decoration:none}
.small{font-size:13px;color:#9fb6c8}
footer{margin-top:auto;padding:12px;text-align:center;color:#9fb6c8}

/* Modal */
.modal{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(2,6,23,0.6);z-index:999;visibility:hidden;opacity:0;transition:opacity .18s,visibility .18s}
.modal.show{visibility:visible;opacity:1}
.modal-card{background:linear-gradient(180deg,#071226,#071b2a);padding:18px;border-radius:12px;min-width:320px;max-width:90%;position:relative;color:#eaf6ff}
.modal-close{position:absolute;right:12px;top:10px;border:0;background:transparent;color:#fff;font-size:18px;cursor:pointer}
.modal-body{font-family:monospace;background:rgba(255,255,255,0.02);padding:12px;border-radius:8px;margin-top:8px}
.modal-actions{margin-top:12px;display:flex;gap:8px;justify-content:flex-end}
</style>
</head>
<body>
<div class="header">
  <h1><i class="fa fa-key"></i> Cofre de Senhas</h1>
  <div>
    <a href="index.php" class="link">Home</a> &nbsp; | &nbsp;
    <a href="manager.php" class="link">Gerenciador de Arquivos</a> &nbsp; | &nbsp;
    <a href="logout.php" class="link">Sair</a>
  </div>
</div>

<div style="padding:20px;">
  <?php if($msg): ?><div class="notice success"><?=htmlspecialchars($msg)?></div><?php endif;?>
  <?php if($err): ?><div class="notice error"><?=htmlspecialchars($err)?></div><?php endif;?>

  <?php if($step === 'ask_cpf'): ?>
    <div class="card" style="max-width:480px;">
      <h3>Confirme seu CPF para abrir o cofre</h3>
      <p class="small">Digite apenas números. Ex: 04883833232</p>
      <form method="post">
        <div class="form-row">
          <input class="input" name="cpf" placeholder="CPF (somente números)" pattern="\d*" required />
        </div>
        <div>
          <button class="btn" type="submit" name="cpf_check">Abrir Cofre</button>
          <a href="index.php" class="btn ghost" style="margin-left:8px;">Cancelar</a>
        </div>
      </form>
    </div>

  <?php else: /* vault */ ?>

    <div class="container">
      <div class="card" style="flex:0 1 360px;">
        <h3>Adicionar nova senha</h3>
        <form method="post">
          <div class="form-row"><input class="input" name="label" placeholder="Nome (ex: Conta Banco)" required></div>
          <div class="form-row"><input class="input" name="senha" placeholder="Senha" required></div>
          <div style="display:flex;gap:8px;">
            <button class="btn" type="submit" name="add_pass">Salvar</button>
            <a href="vault.php" class="btn ghost" style="align-self:center;">Atualizar</a>
          </div>
        </form>
      </div>

      <div class="card list" style="flex:1 1 520px;">
        <h3>Suas senhas — <?=htmlspecialchars($_SESSION['name'])?></h3>
        <p class="small">Somente você tem acesso a essas senhas. Clique no ícone de olho para visualizar (aparecerá por 15s).</p>

        <table>
          <thead><tr><th>Nome</th><th>Última mod.</th><th>Ações</th></tr></thead>
          <tbody>
            <?php if(empty($user_entries)): ?>
              <tr><td colspan="3" class="small">Nenhuma senha salva.</td></tr>
            <?php else: ?>
              <?php foreach($user_entries as $label => $enc):
                   $plain = decrypt_data($enc);
                   $obj = json_decode($plain, true);
                   $created = $obj['created'] ?? null;
                   ?>
                   <tr>
                     <td><?=htmlspecialchars($label)?></td>
                     <td class="small"><?= $created ? date('d/m/Y H:i', $created) : '-' ?></td>
                     <td>
                       <button class="btn" type="button" data-label="<?=htmlspecialchars($label)?>" onclick="viewPassword(this)"><i class="fa fa-eye"></i> Ver</button>
                       <form method="post" style="display:inline;">
                         <button class="btn ghost" type="submit" name="delete_label" value="<?=htmlspecialchars($label)?>" onclick="return confirm('Remover <?=htmlspecialchars($label)?>?')">Excluir</button>
                       </form>
                     </td>
                   </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php endif; ?>
</div>

<!-- Modal (shows password) -->
<div id="pwModal" class="modal" role="dialog" aria-hidden="true">
  <div class="modal-card" role="document">
    <button class="modal-close" onclick="hideModal()">✕</button>
    <h3>Senha</h3>
    <div id="pwBody" class="modal-body">—</div>
    <div class="modal-actions">
      <button class="btn ghost" onclick="hideModal()">Ocultar</button>
      <button class="btn" onclick="copyPassword()">Copiar</button>
    </div>
    <div style="margin-top:8px;color:#9fb6c8;font-size:13px;">A senha será ocultada automaticamente em <span id="countdown">15</span>s.</div>
  </div>
</div>

<footer style="padding:12px;text-align:center;color:#9fb6c8">© <?=date('Y')?> Valentim Cloud — Cofre de Senhas</footer>

<script>
// Modal & fetch logic
let modal = document.getElementById('pwModal');
let pwBody = document.getElementById('pwBody');
let countdownEl = document.getElementById('countdown');
let hideTimer = null;
let countdownTimer = null;
let remaining = 15;

function showModal(text){
  pwBody.textContent = text;
  remaining = 15;
  countdownEl.textContent = remaining;
  modal.classList.add('show');
  // start countdown
  clearTimers();
  countdownTimer = setInterval(()=>{
    remaining--;
    countdownEl.textContent = remaining;
    if(remaining <= 0) hideModal();
  }, 1000);
  // ensure hide after 15s
  hideTimer = setTimeout(hideModal, 15000);
}

function hideModal(){
  modal.classList.remove('show');
  pwBody.textContent = '—';
  clearTimers();
}

function clearTimers(){
  if(hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
  if(countdownTimer) { clearInterval(countdownTimer); countdownTimer = null; }
}

async function viewPassword(button){
  const label = button.getAttribute('data-label');
  if(!label) return alert('Rótulo inválido');
  try{
    const res = await fetch('vault.php?action=get_password&label=' + encodeURIComponent(label), { credentials: 'same-origin' });
    const j = await res.json();
    if(!j.ok) return alert('Erro: ' + (j.error||'desconhecido'));
    // show in modal and auto-hide
    showModal(j.password);
  } catch(e){
    alert('Erro ao obter senha: ' + e.message);
  }
}

function copyPassword(){
  const txt = pwBody.textContent || '';
  if(!txt) return;
  navigator.clipboard.writeText(txt).then(()=> {
    alert('Senha copiada para a área de transferência');
  }).catch(()=> {
    alert('Não foi possível copiar automaticamente. Selecione e copie manualmente.');
  });
}
</script>
</body>
</html>
