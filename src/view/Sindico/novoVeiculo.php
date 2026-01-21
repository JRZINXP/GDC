<?php
require_once __DIR__ . '/../../data/conector.php';
session_start();

/* AUTENTICAÇÃO */
if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] !== 'Sindico') {
    header('Location: ../../login.php');
    exit;
}

$conexao = (new Conector())->getConexao();
/* REGISTRAR VEÍCULO */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_morador'], $_POST['matricula'])) {

    $id_morador = (int) $_POST['id_morador'];
    $matricula  = strtoupper(trim($_POST['matricula']));

    if ($id_morador > 0 && $matricula !== '') {

        // Verificar se matrícula já existe
        $check = $conexao->prepare("SELECT id_veiculo FROM Veiculo WHERE matricula = ?");
        $check->bind_param("s", $matricula);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows === 0) {

            $stmt = $conexao->prepare("
                INSERT INTO Veiculo (id_morador, matricula)
                VALUES (?, ?)
            ");
            $stmt->bind_param("is", $id_morador, $matricula);
            $stmt->execute();

            // REDIRECIONA para evitar reenvio do form
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;

        } else {
            $erro = "Esta matrícula já está registada.";
        }
    } else {
        $erro = "Dados inválidos.";
    }
}


/* Dados do síndico */
$stmt = $conexao->prepare("SELECT nome FROM Sindico WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

$userName = $row['nome'];
$iniciais = strtoupper(substr($userName, 0, 1));

/* Moradores */
$moradores = $conexao->query("
    SELECT m.id_morador, m.nome, u.email, un.numero AS unidade
    FROM Morador m
    INNER JOIN Usuario u ON m.id_usuario = u.id_usuario
    LEFT JOIN Unidade un ON m.id_unidade = un.id_unidade
    ORDER BY m.nome
")->fetch_all(MYSQLI_ASSOC);

/* Veículos */
$veiculos = $conexao->query("
    SELECT v.id_veiculo, v.matricula,
           m.nome AS morador_nome,
           u.email AS morador_email,
           un.numero AS unidade
    FROM Veiculo v
    INNER JOIN Morador m ON v.id_morador = m.id_morador
    INNER JOIN Usuario u ON m.id_usuario = u.id_usuario
    LEFT JOIN Unidade un ON m.id_unidade = un.id_unidade
    ORDER BY v.matricula
")->fetch_all(MYSQLI_ASSOC);

$total_veiculos = count($veiculos);
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

/* HEADER – PADRÃO SÍNDICO */
.header{
    background:#fff;
    padding:20px 32px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-bottom:3px solid #7e22ce;
    box-shadow:0 4px 12px rgba(0,0,0,.06);
}
.header h2{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:22px;
}
.header h2 i{color:#7e22ce}

/* USER */
.user{
    display:flex;
    align-items:center;
    gap:14px;
}
.avatar{
    width:42px;height:42px;
    border-radius:50%;
    background:#7e22ce;
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:600;
}

/* BOTÃO VOLTAR */
.back-btn{
    background:#6b7280;
    color:#fff;
    padding:10px 18px;
    border-radius:8px;
    text-decoration:none;
    display:flex;
    align-items:center;
    gap:8px;
    transition:.3s;
}
.back-btn i{color:#fff}
.back-btn:hover{
    background:#4b5563;
    transform:translateY(-2px);
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
    border-radius:14px;
    padding:25px;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
    margin-bottom:25px;
}

/* TOPO */
.top{
    display:flex;
    justify-content:space-between;
    align-items:center;
}

/* BOTÃO NOVO */
.btn-success{
    background:#7e22ce;
    color:#fff;
    padding:12px 20px;
    border-radius:8px;
    border:none;
    cursor:pointer;
    display:flex;
    align-items:center;
    gap:8px;
    font-weight:500;
    transition:.3s;
}
.btn-success i{color:#fff}
.btn-success:hover{
    background:#5b21b6;
    transform:translateY(-2px);
}

/* TABELA */
table{width:100%;border-collapse:collapse}
th{
    background:#f3f4f6;
    padding:16px;
    text-align:left;
    font-size:14px;
}
td{
    padding:16px;
    border-top:1px solid #e5e7eb;
}
tr:hover{background:#f9fafb}

/* BADGE MATRÍCULA */
.badge{
    background:#e0e7ff;
    color:#3730a3;
    padding:6px 12px;
    border-radius:8px;
    font-weight:600;
    font-family:monospace;
}

/* AÇÕES */
.actions{
    display:flex;
    gap:10px;
}
.actions a{
    width:36px;
    height:36px;
    border-radius:8px;
    display:flex;
    align-items:center;
    justify-content:center;
    text-decoration:none;
    transition:.3s;
}

/* DELETE */
.delete{
    background:#fee2e2;
    color:#dc2626;
}
.delete:hover{
    background:#fecaca;
}

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
    border-radius:14px;
    width:100%;
    max-width:500px;
    padding:25px;
    box-shadow:0 10px 25px rgba(0,0,0,.2);
}
.form-group{margin-bottom:15px}
label{font-weight:500}
input,select{
    width:100%;
    padding:12px;
    border:1px solid #d1d5db;
    border-radius:8px;
}

/* RESPONSIVO */
@media(max-width:768px){
    .top{
        flex-direction:column;
        align-items:flex-start;
        gap:15px;
    }
    .header{
        flex-direction:column;
        align-items:flex-start;
        gap:12px;
    }
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
        <strong>Total de veículos: <?= $total_veiculos ?></strong>
        <button class="btn-success" onclick="abrirModal()">
            <i class="fas fa-plus"></i> Novo Veículo
        </button>
    </div>

    <div class="card">
        <?php if(empty($veiculos)): ?>
            <p style="text-align:center;color:#6b7280">Nenhum veículo registado</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Matrícula</th>
                    <th>Morador</th>
                    <th>Unidade</th>
                    <th>Email</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($veiculos as $v): ?>
                <tr>
                    <td><span class="badge"><?= htmlspecialchars($v['matricula']) ?></span></td>
                    <td><?= htmlspecialchars($v['morador_nome']) ?></td>
                    <td><?= htmlspecialchars($v['unidade'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($v['morador_email']) ?></td>
                    <td class="actions">
                        <a href="?action=excluir&id=<?= $v['id_veiculo'] ?>"
                           class="delete"
                           title="Excluir"
                           onclick="return confirm('Excluir veículo?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        <?php endif ?>
    </div>
</div>

<!-- MODAL -->
<div id="modalVeiculo" class="modal">
    <div class="modal-box">
        <h3><i class="fas fa-car"></i> Novo Veículo</h3><br>
        <form method="POST">
            <div class="form-group">
                <label>Morador</label>
                <select name="id_morador" required>
                    <option value="">-- selecione --</option>
                    <?php foreach($moradores as $m): ?>
                        <option value="<?= $m['id_morador'] ?>">
                            <?= htmlspecialchars($m['nome']) ?> (<?= $m['unidade'] ?>)
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="form-group">
                <label>Matrícula</label>
                <input type="text" name="matricula" placeholder="ABC-123-XY" required>
            </div>

            <button class="btn-success" type="submit">
                <i class="fas fa-save"></i> Salvar
            </button>
            <button type="button" class="back-btn" onclick="fecharModal()">
                Cancelar
            </button>
        </form>
    </div>
</div>

<script>
function abrirModal(){
    document.getElementById('modalVeiculo').classList.add('active');
}
function fecharModal(){
    document.getElementById('modalVeiculo').classList.remove('active');
}
</script>

</body>
</html>
