<?php
require_once __DIR__ . '/../../data/conector.php';
session_start();

/* AUTENTICAÇÃO */
if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] !== 'Sindico') {
    header('Location: ../../login.php');
    exit;
}

$conexao = (new Conector())->getConexao();

/* ===== EDITAR MORADOR ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar') {

    $id_morador = (int)$_POST['id_morador'];
    $nome = trim($_POST['nome']);
    $telefone = trim($_POST['telefone']);
    $id_unidade = $_POST['id_unidade'] ?: null;

    if ($nome !== '') {
        $stmt = $conexao->prepare("
            UPDATE Morador
            SET nome = ?, telefone = ?, id_unidade = ?
            WHERE id_morador = ?
        ");
        $stmt->bind_param("ssii", $nome, $telefone, $id_unidade, $id_morador);
        $stmt->execute();
    }
}

/* Dados do síndico */
$stmt = $conexao->prepare("SELECT nome FROM Sindico WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

$userName = $row['nome'];
$iniciais = strtoupper(substr($userName, 0, 1));
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Gerenciar Moradores</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
/* RESET */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif
}

body{
    background:#f4f6f9;
    color:#1f2937;
}

/* HEADER (PADRÃO SÍNDICO) */
.header{
    background:#ffffff;
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

.header h2 i{
    color:#7e22ce;
}

/* USER INFO */
.user{
    display:flex;
    align-items:center;
    gap:14px;
}

.avatar{
    width:42px;
    height:42px;
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
    background:#ffffff;
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
    color:#ffffff;
    padding:12px 20px;
    border-radius:8px;
    text-decoration:none;
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
table{
    width:100%;
    border-collapse:collapse;
}

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

tr:hover{
    background:#f9fafb;
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

/* VER */
.view{
    background:#dcfce7;
    color:#166534;
}

/* EDITAR */
.edit{
    background:#dbeafe;
    color:#1e40af;
}

/* EXCLUIR */
.delete{
    background:#fee2e2;
    color:#dc2626;
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
    <h2><i class="fas fa-users"></i> Gerenciar Moradores</h2>

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
        <strong>Lista de moradores</strong>
        <a href="novoUser.php" class="btn-success">
            <i class="fas fa-user-plus"></i> Novo Morador
        </a>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Unidade</th>
                    <th>Telefone</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $query = "
                SELECT m.id_morador, m.nome, m.telefone,
                       u.email, un.numero AS unidade
                FROM Morador m
                INNER JOIN Usuario u ON m.id_usuario = u.id_usuario
                LEFT JOIN Unidade un ON m.id_unidade = un.id_unidade
                ORDER BY m.nome
            ";
            $res = $conexao->query($query);

            if($res && $res->num_rows > 0):
                while($m = $res->fetch_assoc()):
            ?>
                <tr>
                    <td><?= $m['id_morador'] ?></td>
                    <td><?= htmlspecialchars($m['nome']) ?></td>
                    <td><?= htmlspecialchars($m['email']) ?></td>
                    <td><?= htmlspecialchars($m['unidade'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($m['telefone'] ?? 'N/A') ?></td>
                    <td class="actions">
                        <a href="#" class="view" title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="#"
   class="edit"
   title="Editar"
   onclick="abrirEditar(
       '<?= $m['id_morador'] ?>',
       '<?= htmlspecialchars($m['nome'], ENT_QUOTES) ?>',
       '<?= htmlspecialchars($m['telefone'] ?? '', ENT_QUOTES) ?>',
       '<?= $m['unidade'] ?>'
   )">
    <i class="fas fa-edit"></i>
</a>

                        <a href="#" class="delete" title="Excluir"
                           onclick="return confirm('Tem certeza?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr>
                    <td colspan="6" style="text-align:center;color:#6b7280">
                        Nenhum morador cadastrado
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
<!-- MODAL EDITAR MORADOR -->
<div id="modalEditar" style="display:none;
    position:fixed; inset:0;
    background:rgba(0,0,0,.55);
    align-items:center;
    justify-content:center;
    z-index:999;">

    <div style="
        background:#fff;
        border-radius:14px;
        padding:25px;
        width:100%;
        max-width:450px;
        box-shadow:0 20px 40px rgba(0,0,0,.25);">

        <h3 style="margin-bottom:20px;display:flex;gap:10px;align-items:center">
            <i class="fas fa-user-edit" style="color:#7e22ce"></i>
            Editar Morador
        </h3>

        <form method="POST">
            <input type="hidden" name="action" value="editar">
            <input type="hidden" name="id_morador" id="edit_id">

            <div style="margin-bottom:15px">
                <label>Nome</label>
                <input type="text" name="nome" id="edit_nome"
                       style="width:100%;padding:12px;border-radius:8px;border:1px solid #d1d5db"
                       required>
            </div>

            <div style="margin-bottom:15px">
                <label>Telefone</label>
                <input type="text" name="telefone" id="edit_telefone"
                       style="width:100%;padding:12px;border-radius:8px;border:1px solid #d1d5db">
            </div>

            <div style="margin-bottom:20px">
                <label>Unidade</label>
                <select name="id_unidade" id="edit_unidade"
                        style="width:100%;padding:12px;border-radius:8px;border:1px solid #d1d5db">
                    <option value="">Sem unidade</option>
                    <?php
                    $unidades = $conexao->query("SELECT id_unidade, numero FROM Unidade ORDER BY numero");
                    while ($u = $unidades->fetch_assoc()):
                    ?>
                        <option value="<?= $u['id_unidade'] ?>">
                            <?= $u['numero'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button"
                        onclick="fecharEditar()"
                        style="background:#6b7280;color:#fff;padding:10px 18px;border-radius:8px;border:none">
                    Cancelar
                </button>

                <button type="submit"
                        style="background:#7e22ce;color:#fff;padding:10px 18px;border-radius:8px;border:none">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>
<script>
function abrirEditar(id, nome, telefone, unidade){
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nome').value = nome;
    document.getElementById('edit_telefone').value = telefone;
    document.getElementById('edit_unidade').value = unidade || '';

    document.getElementById('modalEditar').style.display = 'flex';
}

function fecharEditar(){
    document.getElementById('modalEditar').style.display = 'none';
}
</script>

</body>
</html>
