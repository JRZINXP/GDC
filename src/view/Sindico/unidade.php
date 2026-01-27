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

$mensagem = '';
$tipo_mensagem = '';

/* ADICIONAR */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'adicionar') {
    $numero = trim($_POST['numero']);
    $rua = trim($_POST['rua']);
    $tipo = trim($_POST['tipo_unidade']);

    if ($numero && $tipo) {
        $stmt = $conexao->prepare("
            INSERT INTO Unidade (numero, rua, tipo_unidade)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("sss", $numero, $rua, $tipo);
        $stmt->execute();

        $mensagem = 'Unidade cadastrada com sucesso!';
        $tipo_mensagem = 'sucesso';
    } else {
        $mensagem = 'Preencha os campos obrigatórios.';
        $tipo_mensagem = 'erro';
    }
}

/* EDITAR */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'editar') {
    $id = (int)$_POST['id_unidade'];
    $numero = trim($_POST['numero']);
    $rua = trim($_POST['rua']);
    $tipo = trim($_POST['tipo_unidade']);

    if ($id && $numero && $tipo) {
        $stmt = $conexao->prepare("
            UPDATE Unidade
            SET numero = ?, rua = ?, tipo_unidade = ?
            WHERE id_unidade = ?
        ");
        $stmt->bind_param("sssi", $numero, $rua, $tipo, $id);
        $stmt->execute();

        $mensagem = 'Unidade atualizada com sucesso!';
        $tipo_mensagem = 'sucesso';
    }
}

/* EXCLUIR */
if (($_GET['action'] ?? '') === 'excluir') {
    $id = (int)$_GET['id'];
    $stmt = $conexao->prepare("DELETE FROM Unidade WHERE id_unidade = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: unidade.php");
    exit;
}

/* LISTAR */
$unidades = $conexao->query("
    SELECT u.*,
    (SELECT COUNT(*) FROM Morador m WHERE m.id_unidade = u.id_unidade) moradores
    FROM Unidade u
    ORDER BY u.numero
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Gerir Unidades</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

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
.top{display:flex;justify-content:space-between;align-items:center}

/* BOTÕES */
.btn{
    padding:14px;border-radius:10px;border:none;
    cursor:pointer;display:flex;gap:8px;
    align-items:center;justify-content:center;
    font-weight:500;width:100%
}
.btn-success{background:#7e22ce;color:#fff}
.btn-success:hover{background:#5b21b6}
.btn-danger{background:#e5e7eb;color:#374151;margin-top:10px}
.btn-danger:hover{background:#d1d5db}

/* GRID */
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px}
.unit{
    background:#fff;border-radius:12px;padding:20px;
    box-shadow:0 6px 15px rgba(0,0,0,.08);
    border-left:4px solid #7e22ce
}
.actions{display:flex;justify-content:flex-end;gap:8px}
.actions a{
    width:36px;height:36px;border-radius:6px;
    display:flex;align-items:center;justify-content:center;text-decoration:none
}
.edit{background:#dcfce7;color:#166534}
.delete{background:#dc2626;color:#fff}

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
    width:100%;max-width:480px;
    padding:28px;
    box-shadow:0 25px 50px rgba(0,0,0,.25)
}

/* MODAL HEADER */
.modal-header{
    display:flex;align-items:center;
    gap:10px;margin-bottom:20px
}
.modal-header h3{
    display:flex;align-items:center;
    gap:10px;font-size:18px
}
.modal-header i{color:#7e22ce}

/* FORM */
.form-group{margin-bottom:16px}
label{font-weight:500;margin-bottom:6px;display:block}
input,select{
    width:100%;padding:14px;
    border-radius:10px;border:1px solid #d1d5db;
    font-size:14px
}
input:focus,select:focus{
    outline:none;border-color:#7e22ce;
    box-shadow:0 0 0 3px rgba(126,34,206,.15)
}
</style>
</head>

<body>

<header class="header">
    <h2><i class="fas fa-home"></i> Gerir Unidades</h2>
    <div class="user">
        <div class="avatar"><?= $iniciais ?></div>
        <strong><?= htmlspecialchars($userName) ?></strong>
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</header>

<div class="container">

<div class="card top">
    <strong>Unidades cadastradas</strong>
    <button class="btn btn-success" style="width:auto" onclick="abrirModal()">
        <i class="fas fa-plus"></i> Nova Unidade
    </button>
</div>

<div class="grid">
<?php foreach($unidades as $u): ?>
<div class="unit">
    <h3><?= htmlspecialchars($u['numero']) ?></h3>
    <p><?= htmlspecialchars($u['rua'] ?? '—') ?></p>
    <small><?= htmlspecialchars($u['tipo_unidade']) ?></small>

    <div class="actions">
        <a href="#" class="edit"
           onclick="editarUnidade(
               <?= $u['id_unidade'] ?>,
               '<?= htmlspecialchars($u['numero'],ENT_QUOTES) ?>',
               '<?= htmlspecialchars($u['rua']??'',ENT_QUOTES) ?>',
               '<?= htmlspecialchars($u['tipo_unidade'],ENT_QUOTES) ?>'
           )">
           <i class="fas fa-edit"></i>
        </a>
    </div>
</div>
<?php endforeach; ?>
</div>
</div>

<!-- MODAL -->
<div class="modal" id="modal">
<div class="modal-box">

    <div class="modal-header">
        <h3>
            <i class="fas fa-home"></i>
            <span id="modalTitulo">Nova Unidade</span>
        </h3>
    </div>

<form method="POST">
<input type="hidden" name="action" id="action" value="adicionar">
<input type="hidden" name="id_unidade" id="id_unidade">

<div class="form-group">
<label>Número</label>
<input type="text" name="numero" id="numero" required>
</div>

<div class="form-group">
<label>Rua / Bloco</label>
<input type="text" name="rua" id="rua">
</div>

<div class="form-group">
<label>Tipo de Unidade</label>
<select name="tipo_unidade" id="tipo_unidade" required>
<option value="">Selecione</option>
<option>Apartamento</option>
<option>Casa</option>
</select>
</div>

<button class="btn btn-success" type="submit">
<i class="fas fa-save"></i> Salvar
</button>

<button type="button" class="btn btn-danger" onclick="fecharModal()">
Cancelar
</button>

</form>
</div>
</div>

<script>
const modal = document.getElementById('modal');
const action = document.getElementById('action');
const id_unidade = document.getElementById('id_unidade');
const numero = document.getElementById('numero');
const rua = document.getElementById('rua');
const tipo_unidade = document.getElementById('tipo_unidade');

function abrirModal(){
    modal.classList.add('active');
    action.value='adicionar';
    id_unidade.value='';
    numero.value='';
    rua.value='';
    tipo_unidade.value='';
    document.getElementById('modalTitulo').innerText = 'Nova Unidade';
}

function editarUnidade(id,n,r,t){
    modal.classList.add('active');
    action.value='editar';
    id_unidade.value=id;
    numero.value=n;
    rua.value=r;
    tipo_unidade.value=t;
    document.getElementById('modalTitulo').innerText = 'Editar Unidade';
}

function fecharModal(){
    modal.classList.remove('active');
}
</script>

</body>
</html>
