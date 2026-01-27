<?php
require_once __DIR__ . '/../../data/conector.php';
session_start();

if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] !== 'Sindico') {
    header('Location: ../../login.php');
    exit;
}

$conexao = (new Conector())->getConexao();

/* DADOS DO SÍNDICO */
$stmt = $conexao->prepare("SELECT nome FROM Sindico WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

$userName = $row['nome'];
$iniciais = strtoupper(substr($userName, 0, 1));

/* RESPONDER OCORRÊNCIA */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'responder') {

    $id = (int) $_POST['id_ocorrencia'];
    $resposta = trim($_POST['resposta']);
    $status = $_POST['status'];

    if ($id && $resposta && $status) {
        $stmt = $conexao->prepare("
            UPDATE Ocorrencia
            SET resposta_sindico = ?, status = ?, data_resolucao = NOW()
            WHERE id_ocorrencia = ?
        ");
        $stmt->bind_param("ssi", $resposta, $status, $id);
        $stmt->execute();
    }

    header("Location: ocorrencias.php");
    exit;
}

/* LISTAR OCORRÊNCIAS */
$ocorrencias = $conexao->query("
    SELECT o.*, m.nome AS morador
    FROM Ocorrencia o
    JOIN Morador m ON m.id_morador = o.id_morador
    ORDER BY o.data_abertura DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Ocorrências</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif}
body{background:#f4f6f9;color:#1f2937}

/* HEADER */
.header{
    background:#fff;padding:20px 30px;
    display:flex;justify-content:space-between;align-items:center;
    border-bottom:3px solid #7e22ce;
    box-shadow:0 4px 6px rgba(0,0,0,.08);
}
.header h2{display:flex;gap:10px;align-items:center}
.header i{color:#7e22ce}
.user{display:flex;align-items:center;gap:15px}
.avatar{
    width:45px;height:45px;border-radius:50%;
    background:#7e22ce;color:#fff;
    display:flex;align-items:center;justify-content:center;font-weight:600
}
.back-btn{
    background:#6b7280;color:#fff;padding:10px 18px;border-radius:6px;
    text-decoration:none;display:flex;gap:8px
}
.back-btn i{color:#fff}

/* CONTAINER */
.container{max-width:1200px;margin:40px auto;padding:0 20px}

/* CARD */
.card{
    background:#fff;border-radius:12px;padding:25px;
    box-shadow:0 6px 15px rgba(0,0,0,.08);margin-bottom:25px
}

/* TABLE */
table{width:100%;border-collapse:collapse}
th{background:#f3f4f6;padding:16px;text-align:left}
td{padding:16px;border-bottom:1px solid #e5e7eb}

/* STATUS */
.status{
    padding:6px 14px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
}
.status-Pendente{background:#fff3cd;color:#856404}
.status-EmAnalise{background:#ede9fe;color:#4a148c}
.status-Resolvido{background:#dcfce7;color:#166534}

/* BOTÃO */
.btn-primary{
    background:#7e22ce;
    color:#fff;
    padding:10px 16px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    display:flex;
    gap:8px;
    align-items:center;
}

/* MODAL */
.modal{
    display:none;position:fixed;inset:0;
    background:rgba(15,23,42,.6);
    backdrop-filter:blur(4px);
    align-items:center;justify-content:center;
    z-index:999
}
.modal.active{display:flex}
.modal-box{
    background:#fff;border-radius:16px;
    width:100%;max-width:520px;
    padding:28px;
    box-shadow:0 25px 50px rgba(0,0,0,.25)
}
.modal-header{
    display:flex;align-items:center;
    gap:10px;margin-bottom:20px
}
.modal-header i{color:#7e22ce}
textarea,select{
    width:100%;padding:14px;
    border-radius:10px;border:1px solid #d1d5db;
    margin-bottom:14px
}
</style>
</head>

<body>

<header class="header">
    <h2><i class="fas fa-exclamation-triangle"></i> Ocorrências</h2>
    <div class="user">
        <div class="avatar"><?= $iniciais ?></div>
        <strong><?= htmlspecialchars($userName) ?></strong>
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</header>

<div class="container">

<div class="card">

<table>
<thead>
<tr>
    <th>Morador</th>
    <th>Tipo</th>
    <th>Título</th>
    <th>Status</th>
    <th>Ação</th>
</tr>
</thead>
<tbody>

<?php foreach($ocorrencias as $o): ?>
<tr>
    <td><?= htmlspecialchars($o['morador']) ?></td>
    <td><?= $o['tipo'] ?></td>
    <td><?= htmlspecialchars($o['titulo']) ?></td>
    <td>
        <span class="status status-<?= str_replace(' ', '', $o['status']) ?>">
            <?= $o['status'] ?>
        </span>
    </td>
    <td>
        <button class="btn-primary"
            onclick='abrirModal(<?= json_encode($o) ?>)'>
            <i class="fas fa-reply"></i> Responder
        </button>
    </td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

</div>
</div>

<!-- MODAL -->
<div class="modal" id="modal">
<div class="modal-box">

<div class="modal-header">
    <i class="fas fa-reply"></i>
    <strong>Responder Ocorrência</strong>
</div>

<form method="POST">
<input type="hidden" name="action" value="responder">
<input type="hidden" name="id_ocorrencia" id="id_ocorrencia">

<label>Status</label>
<select name="status" id="status">
    <option value="Pendente">Pendente</option>
    <option value="Em Analise">Em Analise</option>
    <option value="Resolvido">Resolvido</option>
</select>

<label>Resposta</label>
<textarea name="resposta" id="resposta" rows="4" required></textarea>

<button class="btn-primary" type="submit">
    <i class="fas fa-save"></i> Enviar
</button>

<button type="button" class="btn-primary" style="background:#6b7280;margin-top:10px"
        onclick="fecharModal()">
    Cancelar
</button>

</form>
</div>
</div>

<script>
function abrirModal(o){
    document.getElementById('modal').classList.add('active');
    document.getElementById('id_ocorrencia').value = o.id_ocorrencia;
    document.getElementById('status').value = o.status;
    document.getElementById('resposta').value = o.resposta_sindico ?? '';
}
function fecharModal(){
    document.getElementById('modal').classList.remove('active');
}
</script>

</body>
</html>
