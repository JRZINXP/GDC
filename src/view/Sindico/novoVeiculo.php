<?php
require_once __DIR__ . '/../../data/conector.php';
session_start();

if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] !== 'Sindico') {
    header('Location: ../../login.php');
    exit;
}

$conexao = (new Conector())->getConexao();
$erro = '';

if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'excluir') {
    $id = (int)$_GET['id'];
    $conexao->query("DELETE FROM Veiculo WHERE id_veiculo = $id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_veiculo = $_POST['id_veiculo'] ?? null;
    $id_morador = (int)$_POST['id_morador'];
    $matricula  = strtoupper(trim($_POST['matricula']));
    $cor        = trim($_POST['cor']);
    $modelo     = trim($_POST['modelo']);

    if (!preg_match('/^[A-Z]{3}-\d{3}-[A-Z]{2}$/', $matricula)) {
        $erro = 'Formato de matrícula inválido';
    } else {

        if ($id_veiculo) {
            $stmt = $conexao->prepare("
                UPDATE Veiculo
                SET id_morador = ?, matricula = ?, cor = ?, modelo = ?
                WHERE id_veiculo = ?
            ");
            $stmt->bind_param("isssi", $id_morador, $matricula, $cor, $modelo, $id_veiculo);
            $stmt->execute();
        } else {
            $check = $conexao->prepare("SELECT id_veiculo FROM Veiculo WHERE matricula = ?");
            $check->bind_param("s", $matricula);
            $check->execute();

            if ($check->get_result()->num_rows === 0) {
                $stmt = $conexao->prepare("
                    INSERT INTO Veiculo (id_morador, matricula, cor, modelo)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->bind_param("isss", $id_morador, $matricula, $cor, $modelo);
                $stmt->execute();
            } else {
                $erro = 'Esta matrícula já está registada';
            }
        }

        if ($erro === '') {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

$stmt = $conexao->prepare("SELECT nome FROM Sindico WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

$userName = $row['nome'];
$iniciais = strtoupper(substr($userName, 0, 1));

$moradores = $conexao->query("
    SELECT m.id_morador, m.nome, un.numero
    FROM Morador m
    LEFT JOIN Unidade un ON m.id_unidade = un.id_unidade
    ORDER BY m.nome
")->fetch_all(MYSQLI_ASSOC);

$veiculos = $conexao->query("
    SELECT v.*, m.nome AS morador_nome, un.numero AS unidade
    FROM Veiculo v
    INNER JOIN Morador m ON v.id_morador = m.id_morador
    LEFT JOIN Unidade un ON m.id_unidade = un.id_unidade
    ORDER BY v.matricula
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Gerir Veículos</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif}
body{background:#f4f6f9;color:#1f2937}

/* HEADER */
.header{
    background:#fff;padding:20px 32px;
    display:flex;justify-content:space-between;align-items:center;
    border-bottom:3px solid #7e22ce;
    box-shadow:0 4px 12px rgba(0,0,0,.06);
}
.header h2{display:flex;gap:10px;align-items:center;font-size:22px}
.header h2 i{color:#7e22ce}
.user{display:flex;align-items:center;gap:14px}
.avatar{
    width:42px;height:42px;border-radius:50%;
    background:#7e22ce;color:#fff;
    display:flex;align-items:center;justify-content:center;font-weight:600
}
.back-btn{
    background:#6b7280;color:#fff;
    padding:10px 18px;border-radius:8px;
    text-decoration:none;display:flex;gap:8px
}
.back-btn i{color:#fff}

/* CONTAINER */
.container{max-width:1200px;margin:40px auto;padding:0 20px}

/* CARD */
.card{
    background:#fff;border-radius:14px;padding:25px;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
    margin-bottom:25px
}
.top{display:flex;justify-content:space-between;align-items:center}

/* BOTÕES */
.btn-success{
    background:#7e22ce;color:#fff;
    padding:14px;border-radius:10px;
    border:none;cursor:pointer;
    display:flex;gap:8px;align-items:center;
    justify-content:center;width:100%;
}
.btn-success:hover{background:#5b21b6}

.btn-cancel{
    background:#e5e7eb;color:#374151;
    padding:14px;border-radius:10px;
    border:none;cursor:pointer;
    width:100%;margin-top:10px;
}
.btn-cancel:hover{background:#d1d5db}

/* TABLE */
table{width:100%;border-collapse:collapse}
th{background:#f3f4f6;padding:16px;text-align:left}
td{padding:16px;border-top:1px solid #e5e7eb}

.badge{
    background:#e0e7ff;color:#3730a3;
    padding:6px 12px;border-radius:8px;
    font-family:monospace
}

/* MODAL */
.modal{
    display:none;position:fixed;inset:0;
    background:rgba(0,0,0,.6);
    align-items:center;justify-content:center;
}
.modal.active{display:flex}
.box{
    background:#fff;border-radius:16px;
    width:100%;max-width:480px;
    padding:25px
}
.modal-header{
    display:flex;align-items:center;
    gap:10px;margin-bottom:20px
}
.modal-header i{color:#7e22ce}

input,select{
    width:100%;padding:12px;margin-bottom:14px;
    border-radius:8px;border:1px solid #d1d5db
}
</style>
</head>

<body>

<header class="header">
    <h2><i class="fas fa-car"></i> Gerir Veículos</h2>
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
    <strong>Total de veículos: <?= count($veiculos) ?></strong>
    <button class="btn-success" style="width:auto" onclick="abrirModal()">
        <i class="fas fa-plus"></i> Novo Veículo
    </button>
</div>

<div class="card">
<table>
<thead>
<tr>
<th>Matrícula</th><th>Morador</th><th>Unidade</th>
<th>Cor</th><th>Modelo</th><th>Ações</th>
</tr>
</thead>
<tbody>
<?php foreach($veiculos as $v): ?>
<tr>
<td><span class="badge"><?= $v['matricula'] ?></span></td>
<td><?= $v['morador_nome'] ?></td>
<td><?= $v['unidade'] ?? 'N/A' ?></td>
<td><?= $v['cor'] ?></td>
<td><?= $v['modelo'] ?></td>
<td>
    <a href="#" onclick='editar(<?= json_encode($v) ?>)'><i class="fas fa-edit"></i></a>
</td>
</tr>
<?php endforeach ?>
</tbody>
</table>
</div>
</div>

<!-- MODAL -->
<div class="modal" id="modal">
<div class="box">

<div class="modal-header">
    <i class="fas fa-car"></i>
    <h3 id="modalTitulo">Novo Veículo</h3>
</div>

<form method="POST">
<input type="hidden" name="id_veiculo" id="id_veiculo">

<select name="id_morador" id="id_morador" required>
<option value="">Selecione o morador</option>
<?php foreach($moradores as $m): ?>
<option value="<?= $m['id_morador'] ?>"><?= $m['nome'] ?> (<?= $m['numero'] ?>)</option>
<?php endforeach ?>
</select>

<input type="text" name="matricula" id="matricula" placeholder="ABC-123-XY" required>
<input type="text" name="cor" id="cor" placeholder="Cor">
<input type="text" name="modelo" id="modelo" placeholder="Modelo">

<button class="btn-success" type="submit">
<i class="fas fa-save"></i> Salvar
</button>

<button type="button" class="btn-cancel" onclick="fecharModal()">
Cancelar
</button>
</form>

</div>
</div>

<script>
function abrirModal(){
    modal.classList.add('active');
    document.getElementById('modalTitulo').innerText='Novo Veículo';
    document.querySelector('form').reset();
    id_veiculo.value='';
}
function fecharModal(){
    modal.classList.remove('active');
}
function editar(v){
    abrirModal();
    document.getElementById('modalTitulo').innerText='Editar Veículo';
    id_veiculo.value=v.id_veiculo;
    id_morador.value=v.id_morador;
    matricula.value=v.matricula;
    cor.value=v.cor;
    modelo.value=v.modelo;
}
</script>

</body>
</html>