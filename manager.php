
<?php
session_start();
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

// Pasta raiz
$rootDir = __DIR__ . "/uploads";
if(!file_exists($rootDir)) mkdir($rootDir, 0777, true);

// Pasta atual
$currentDir = isset($_GET['dir']) ? realpath($rootDir . "/" . $_GET['dir']) : $rootDir;

// Segurança: não permitir sair da raiz
if(strpos($currentDir, realpath($rootDir)) !== 0){
    $currentDir = $rootDir;
}

// Breadcrumb
$relativeDir = str_replace(realpath($rootDir), "", $currentDir);
$breadcrumb = explode("/", trim($relativeDir, "/"));

// Criar pasta
if(isset($_POST['new_folder'])){
    $name = preg_replace("/[^a-zA-Z0-9_\-]/", "_", $_POST['folder_name']);
    if($name){
        mkdir($currentDir . "/" . $name);
    }
    header("Location: ?dir=" . urlencode(trim($relativeDir, "/")));
    exit;
}

// Upload
if(isset($_POST['upload'])){
    if(isset($_FILES['file']) && $_FILES['file']['error'] === 0){
        move_uploaded_file($_FILES['file']['tmp_name'], $currentDir . "/" . basename($_FILES['file']['name']));
    }
    header("Location: ?dir=" . urlencode(trim($relativeDir, "/")));
    exit;
}

// Deletar
if(isset($_POST['delete'])){
    $target = $currentDir . "/" . basename($_POST['delete']);
    if(is_file($target)) unlink($target);
    elseif(is_dir($target)) rmdir($target);
    header("Location: ?dir=" . urlencode(trim($relativeDir, "/")));
    exit;
}

// Listar arquivos
$items = array_diff(scandir($currentDir), ['.','..']);
?>
<!DOCTYPE html>

<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>VALENTIM CLOUD - File Manager</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
body{margin:0;font-family:Arial, sans-serif;background:#121212;color:#eee;display:flex;height:100vh;}
.sidebar{width:250px;background:#1f1f1f;padding:1rem;overflow-y:auto;}
.sidebar h2{font-size:1.1rem;color:#00f7ff;margin:0 0 1rem;}
.sidebar a{display:block;color:#eee;text-decoration:none;padding:5px;border-radius:5px;}
.sidebar a:hover{background:#333;}
.main{flex:1;display:flex;flex-direction:column;}
.header{background:#1f1f1f;padding:1rem;display:flex;justify-content:space-between;align-items:center;}
.header h1{font-size:1.2rem;color:#00f7ff;margin:0;}
.header form{display:inline;}
.toolbar{background:#222;padding:0.5rem;display:flex;gap:0.5rem;}
.toolbar button{background:#00c6ff;border:none;padding:0.5rem 1rem;border-radius:5px;cursor:pointer;font-weight:bold;}
.toolbar button:hover{background:#0072ff;color:#fff;}
.content{flex:1;overflow:auto;padding:1rem;}
table{width:100%;border-collapse:collapse;}
th,td{padding:8px;text-align:left;border-bottom:1px solid #333;}
tr:hover{background:#1e1e1e;}
.fa-folder{color:#f5c542;}
.fa-file{color:#bbb;}
.breadcrumb a{color:#00f7ff;text-decoration:none;margin-right:5px;}
.breadcrumb a:hover{text-decoration:underline;}
</style>
</head>
<body>

<div class="sidebar">
    <h2><i class="fa fa-bars"></i> Menu</h2>
    <a href="index.php"><i class="fa fa-house"></i> Home</a>
    <a href="?"><i class="fa fa-folder-open"></i> 📁 uploads</a>
    <?php
    function listDirs($base, $parent=""){
        $dirs = array_filter(glob($base."/*"), "is_dir");
        foreach($dirs as $d){
            $rel = str_replace(realpath(__DIR__."/uploads"), "", $d);
            echo "<a style='margin-left:10px;' href='?dir=".urlencode(trim($rel,"/"))."'>📁 ".basename($d)."</a>";
            listDirs($d,$rel);
        }
    }
    listDirs($rootDir);
    ?>
</div>


<div class="main">
    <div class="header">
        <h1>☁️ VALENTIM CLOUD</h1>
        <a href="logout.php" style="color:#ff5555;"><i class="fa fa-right-from-bracket"></i> Sair</a>
    </div>
    <div class="toolbar">
        <form method="post">
            <input type="text" name="folder_name" placeholder="Nova Pasta" required>
            <button type="submit" name="new_folder"><i class="fa fa-folder-plus"></i> Criar Pasta</button>
        </form>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="file" required>
            <button type="submit" name="upload"><i class="fa fa-upload"></i> Upload</button>
        </form>
    </div>
    <div class="content">
        <div class="breadcrumb">
            <a href="?">uploads</a>
            <?php
            $path = "";
            foreach($breadcrumb as $b){
                if(!$b) continue;
                $path .= "/".$b;
                echo " / <a href='?dir=".urlencode(trim($path,"/"))."'>".htmlspecialchars($b)."</a>";
            }
            ?>
        </div>
        <table>
            <tr><th>Nome</th><th>Tamanho</th><th>Modificado</th><th>Ação</th></tr>
            <?php foreach($items as $item):
                $path = $currentDir . "/" . $item;
                ?>
                <tr>
                    <td>
                        <?php if(is_dir($path)): ?>
                            <i class="fa fa-folder"></i> <a href="?dir=<?= urlencode(trim($relativeDir."/".$item,"/")) ?>" style="color:#00f7ff;"><?= htmlspecialchars($item) ?></a>
                        <?php else: ?>
                            <i class="fa fa-file"></i> <?= htmlspecialchars($item) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= is_file($path) ? filesize($path)." bytes" : "-" ?></td>
                    <td><?= date("d/m/Y H:i", filemtime($path)) ?></td>
                    <td>
                        <?php if(is_file($path)): ?>
                            <a href="<?= "uploads/".trim($relativeDir."/".$item,"/") ?>" download><i class="fa fa-download"></i></a>
                        <?php endif; ?>
                        <form method="post" style="display:inline;">
                            <button type="submit" name="delete" value="<?= htmlspecialchars($item) ?>"><i class="fa fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

</body>
</html>
