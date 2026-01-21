<?php
require_once __DIR__ . '/../../data/conector.php';
session_start();

/* AUTENTICAÇÃO */
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

/* ADICIONAR UNIDADE */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'adicionar') {
    $numero = trim($_POST['numero']);
    $rua = trim($_POST['rua']);

    if ($numero) {
        $stmt = $conexao->prepare("INSERT INTO Unidade (numero, rua) VALUES (?, ?)");
        $stmt->bind_param("ss", $numero, $rua);
        $stmt->execute();

        $mensagem = 'Unidade cadastrada com sucesso!';
        $tipo_mensagem = 'sucesso';
    } else {
        $mensagem = 'Número da unidade é obrigatório.';
        $tipo_mensagem = 'erro';
    }
}

/* EDITAR UNIDADE */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'editar') {
    $id = (int)$_POST['id_unidade'];
    $numero = trim($_POST['numero']);
    $rua = trim($_POST['rua']);

    if ($id && $numero) {
        $stmt = $conexao->prepare("
            UPDATE Unidade 
            SET numero = ?, rua = ?
            WHERE id_unidade = ?
        ");
        $stmt->bind_param("ssi", $numero, $rua, $id);
        $stmt->execute();

        $mensagem = 'Unidade atualizada com sucesso!';
        $tipo_mensagem = 'sucesso';
    } else {
        $mensagem = 'Erro ao editar unidade.';
        $tipo_mensagem = 'erro';
    }
}

/* EXCLUIR */
if (isset($_GET['action']) && $_GET['action'] === 'excluir') {
    $id = (int)$_GET['id'];
    $stmt = $conexao->prepare("DELETE FROM Unidade WHERE id_unidade = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: unidade.php");
    exit;
}

/* LISTAR UNIDADES */
$unidades = $conexao->query("
    SELECT u.*, 
    (SELECT COUNT(*) FROM Morador m WHERE m.id_unidade = u.id_unidade) AS moradores
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
    background:#fff;
    padding:20px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-bottom:3px solid #7e22ce;
    box-shadow:0 4px 6px rgba(0,0,0,.08);
}
.header h2{display:flex;gap:10px;align-items:center}
.header i{color:#7e22ce}

.user{
    display:flex;
    align-items:center;
    gap:15px;
}
.avatar{
    width:45px;height:45px;
    border-radius:50%;
    background:#7e22ce;
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:600;
}
.back-btn{
    background:#6b7280;
    color:#fff;
    padding:10px 18px;
    border-radius:6px;
    text-decoration:none;
    display:flex;
    align-items:center;
    gap:8px;
}
.back-btn i{
    color: #fff;
}

/* CONTAINER */
.container{
    max-width:1200px;
    margin:40px auto;
    padding:0 20px;
}

/* CARD */
.card{
    background:#fff;
    border-radius:12px;
    padding:25px;
    box-shadow:0 6px 15px rgba(0,0,0,.08);
    margin-bottom:25px;
}

.top{
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.btn{
    padding:10px 18px;
    border-radius:6px;
    border:none;
    cursor:pointer;
    display:flex;
    align-items:center;
    gap:8px;
    font-weight:500;
}
.btn-success{background:#10b981;color:#fff}
.btn-success:hover{background:#059669}
.btn-danger{background:#ef4444;color:#fff}

/* GRID */
.grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(280px,1fr));
    gap:20px;
}

.unit{
    background:#fff;
    border-radius:12px;
    padding:20px;
    box-shadow:0 6px 15px rgba(0,0,0,.08);
    border-left:4px solid #7e22ce;
}
.unit h3{margin-bottom:10px}

.actions{
    display:flex;
    justify-content:flex-end;
    gap:8px;
}
.actions a{
    width:36px;height:36px;
    border-radius:6px;
    display:flex;
    align-items:center;
    justify-content:center;
    text-decoration:none;
}
.edit{
    background:#dcfce7;
    color:#166534;
}
.delete{background:#dc2626;color:#fff}

/* MODAL */
.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.5);
    align-items:center;
    justify-content:center;
}
.modal.active{display:flex}
.modal-box{
    background:#fff;
    border-radius:12px;
    padding:25px;
    width:100%;
    max-width:500px;
}
.form-group{margin-bottom:15px}
input{
    width:100%;
    padding:10px;
    border-radius:6px;
    border:1px solid #d1d5db;
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

<?php if($mensagem): ?>
    <div class="card" style="border-left:4px solid <?= $tipo_mensagem=='sucesso'?'#10b981':'#ef4444' ?>">
        <?= htmlspecialchars($mensagem) ?>
    </div>
<?php endif; ?>

<div class="card top">
    <strong>Unidades cadastradas</strong>
    <button class="btn btn-success" onclick="abrirModal()">
        <i class="fas fa-plus"></i> Nova Unidade
    </button>
</div>

<div class="grid">
<?php foreach($unidades as $u): ?>
    <div class="unit">
        <h3><?= htmlspecialchars($u['numero']) ?></h3>
        <p><?= htmlspecialchars($u['rua'] ?? '—') ?></p>
        <small><?= $u['moradores'] ?> morador(es)</small>

        <div class="actions">
            <a href="#" class="edit"
               onclick="editarUnidade(<?= $u['id_unidade'] ?>,'<?= htmlspecialchars($u['numero'],ENT_QUOTES) ?>','<?= htmlspecialchars($u['rua']??'',ENT_QUOTES) ?>')">
                <i class="fas fa-edit"></i>
            </a>
            <?php if($u['moradores']==0): ?>
            <a href="?action=excluir&id=<?= $u['id_unidade'] ?>" class="delete"
               onclick="return confirm('Excluir unidade?')">
                <i class="fas fa-trash"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>
</div>

<!-- MODAL -->
<div id="modal" class="modal">
    <div class="modal-box">
        <h3>Unidade</h3><br>
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

            <button class="btn btn-success" type="submit">
                <i class="fas fa-save"></i> Salvar
            </button>
            <button type="button" class="btn btn-danger" onclick="fecharModal()">Cancelar</button>
        </form>
    </div>
</div>

<script>
function abrirModal(){
    document.getElementById('modal').classList.add('active');
    document.getElementById('action').value='adicionar';
    document.getElementById('id_unidade').value='';
    document.getElementById('numero').value='';
    document.getElementById('rua').value='';
}

function editarUnidade(id,numero,rua){
    document.getElementById('modal').classList.add('active');
    document.getElementById('action').value='editar';
    document.getElementById('id_unidade').value=id;
    document.getElementById('numero').value=numero;
    document.getElementById('rua').value=rua;
}

function fecharModal(){
    document.getElementById('modal').classList.remove('active');
}
</script>

</body>
</html>
