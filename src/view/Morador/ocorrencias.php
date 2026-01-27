<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../data/conector.php';

/* PROTEÇÃO */
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'Morador') {
    header('Location: ../../login.php');
    exit;
}

$conexao = (new Conector())->getConexao();

/* BUSCAR MORADOR */
$stmt = $conexao->prepare("SELECT id_morador, nome FROM Morador WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$morador = $stmt->get_result()->fetch_assoc();

if (!$morador) {
    session_destroy();
    header("Location: ../../login.php");
    exit;
}

$idMorador   = $morador['id_morador'];
$nomeMorador = $morador['nome'];
$iniciais    = strtoupper(substr($nomeMorador, 0, 1));

/* REGISTAR OCORRÊNCIA */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'];
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);

    if ($tipo && $titulo && $descricao) {
        $stmt = $conexao->prepare("
            INSERT INTO Ocorrencia (id_morador, tipo, titulo, descricao)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("isss", $idMorador, $tipo, $titulo, $descricao);
        $stmt->execute();
    }

    header("Location: ocorrencias.php");
    exit;
}

/* LISTAR OCORRÊNCIAS */
$stmt = $conexao->prepare("
    SELECT *
    FROM Ocorrencia
    WHERE id_morador = ?
    ORDER BY data_abertura DESC
");
$stmt->bind_param("i", $idMorador);
$stmt->execute();
$ocorrencias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Ocorrências</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif}
body{background:#f4f6f9}

/* LAYOUT */
.layout{display:flex;min-height:100vh}

/* SIDEBAR */
.sidebar{
    width:240px;
    background:#9743d7;
    color:#fff;
    padding:25px 20px;
    display:flex;
    flex-direction:column;
}
.sidebar h2{
    font-size:20px;
    margin-bottom:30px;
    display:flex;
    gap:10px;
    align-items:center;
}
.sidebar nav a{
    display:flex;
    align-items:center;
    gap:12px;
    padding:14px 16px;
    color:#fff;
    text-decoration:none;
    border-radius:10px;
    margin-bottom:10px;
    transition:.2s;
}
.sidebar nav a:hover:not(.active){
    background:rgba(255,255,255,.18);
}
.sidebar nav a.active{
    background:#fff;
    color:#9743d7;
    font-weight:600;
}
.sidebar nav a.active i{color:#9743d7}
.sidebar .logout{
    margin-top:auto;
    background:rgba(0,0,0,.25);
}

/* CONTENT */
.content{flex:1;padding:40px}

/* HEADER */
.dashboard-header{
    background:#fff;
    padding:20px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-bottom:3px solid #9743d7;
    margin-bottom:30px;
}
.header-left h2{
    display:flex;
    gap:10px;
    align-items:center;
}
.header-left h2 i{color:#9743d7}
.header-subtitle{font-size:14px;color:#555}
.user-info{display:flex;gap:10px;align-items:center}
.user-avatar{
    width:42px;height:42px;
    border-radius:50%;
    background:#9743d7;
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:600;
}

/* CONTAINER */
.container{
    background:#fff;
    padding:30px;
    border-radius:14px;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

/* ACTIONS */
.page-actions{
    display:flex;
    justify-content:flex-end;
    margin-bottom:20px;
}
.btn-primary{
    background:#9743d7;
    color:#fff;
    border:none;
    padding:12px 18px;
    border-radius:10px;
    cursor:pointer;
    display:flex;
    gap:8px;
    align-items:center;
}
.btn-primary:hover{background:#7e22ce}

/* TABLE */
table{width:100%;border-collapse:collapse}
th{background:#f3f4f6;padding:14px;text-align:left}
td{padding:14px;border-bottom:1px solid #e5e7eb}

/* STATUS */
.status{
    padding:5px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
}
.status-Pendente{background:#fff3cd;color:#856404}
.status-Em{background:#ede9fe;color:#4a148c}
.status-Resolvido{background:#dcfce7;color:#166534}

/* MODAL */
.modal-overlay{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.55);
    justify-content:center;
    align-items:center;
    z-index:1000;
}
.modal{
    background:#fff;
    width:500px;
    padding:30px;
    border-radius:14px;
}
.modal h3{
    display:flex;
    gap:10px;
    margin-bottom:20px;
}
.form-group{margin-bottom:15px}
label{font-weight:500}
input,select{
    width:100%;
    padding:12px;
    margin-top:6px;
    border-radius:8px;
    border:1px solid #d1d5db;
}
input:focus,select:focus{
    outline:none;
    border-color:#9743d7;
}
.close{
    background:#6c757d;
    color:#fff;
    margin-top:10px;
}
</style>
</head>

<body>

<div class="layout">

<aside class="sidebar">
        <h2><i class="fas fa-home"></i> Morador</h2>
        <nav>
            <a href="index.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="agendar_visita.php" ><i class="fas fa-users"></i> Visitas</a>
            <a href="reservas.php"><i class="fas fa-calendar-check"></i> Reservas</a>
            <a href="encomendas.php"><i class="fas fa-box"></i> Encomendas</a>
            <a href="avisos.php"><i class="fas fa-bullhorn"></i> Avisos</a>
            <a href="ocorrencias.php" class="active"><i class="fas fa-exclamation-triangle"></i> Ocorrências</a>
        <a href="../../logout.php?logout=1" class="logout">
            <i class="fas fa-sign-out-alt"></i> Sair
        </a>
    </nav>
</aside>

<main class="content">

<header class="dashboard-header">
    <div class="header-left">
        <h2><i class="fas fa-exclamation-triangle"></i> Minhas Ocorrências</h2>
        <div class="header-subtitle">Registe e acompanhe ocorrências</div>
    </div>
    <div class="user-info">
        <div class="user-avatar"><?= $iniciais ?></div>
        <strong><?= htmlspecialchars($nomeMorador) ?></strong>
    </div>
</header>

<div class="container">

<div class="page-actions">
    <button class="btn-primary" onclick="abrirModal()">
        <i class="fas fa-plus"></i> Nova Ocorrência
    </button>
</div>

<?php if(empty($ocorrencias)): ?>
    <p style="color:#777">Nenhuma ocorrência registada.</p>
<?php else: ?>
<table>
<thead>
<tr>
    <th>Tipo</th>
    <th>Título</th>
    <th>Status</th>
    <th>Data</th>
</tr>
</thead>
<tbody>
<?php foreach($ocorrencias as $o): ?>
<tr>
    <td><?= $o['tipo'] ?></td>
    <td><?= htmlspecialchars($o['titulo']) ?></td>
    <td>
        <span class="status status-<?= str_replace(' ', '', $o['status']) ?>">
            <?= $o['status'] ?>
        </span>
    </td>
    <td><?= date('d/m/Y H:i', strtotime($o['data_abertura'])) ?></td>
</tr>
<?php endforeach ?>
</tbody>
</table>
<?php endif ?>

</div>
</main>
</div>

<!-- MODAL -->
<div class="modal-overlay" id="modal">
<div class="modal">
<h3><i class="fas fa-exclamation-circle"></i> Nova Ocorrência</h3>

<form method="POST">
<div class="form-group">
<label>Tipo</label>
<select name="tipo" required>
<option value="">Selecione</option>
<option>Reclamacao</option>
<option>Manutencao</option>
<option>Sugestao</option>
<option>Outro</option>
</select>
</div>

<div class="form-group">
<label>Título</label>
<input type="text" name="titulo" required>
</div>

<div class="form-group">
<label>Descrição</label>
<input type="text" name="descricao" required>
</div>

<button class="btn-primary">
<i class="fas fa-check"></i> Registar
</button>

<button type="button" class="btn-primary close" onclick="fecharModal()">
Cancelar
</button>
</form>
</div>
</div>

<script>
function abrirModal(){document.getElementById('modal').style.display='flex'}
function fecharModal(){document.getElementById('modal').style.display='none'}
</script>

</body>
</html>
