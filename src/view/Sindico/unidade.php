<?php
require_once __DIR__ . '/../../data/conector.php';
session_start();

if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] !== 'Sindico') {
    header('Location: ../../login.php');
    exit;
}

$conexao = (new Conector())->getConexao();


$stmt = $conexao->prepare("SELECT nome FROM Sindico WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

$userName = $row['nome'];
$iniciais = strtoupper(substr($userName, 0, 1));

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
    }
}

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
    }
}

if (($_GET['action'] ?? '') === 'excluir') {
    $id = (int)$_GET['id'];
    $stmt = $conexao->prepare("DELETE FROM Unidade WHERE id_unidade = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: unidade.php");
    exit;
}

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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif
        }

        body {
            background: #f4f6f9;
            color: #1f2937
        }

        /* HEADER */
        .dashboard-header {
            background: #fff;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, .1);
            border-bottom: 3px solid #7e22ce;
            position: sticky;
            top: 0;
            z-index: 100
        }

        .dashboard-header h2 {
            display: flex;
            gap: 10px;
            align-items: center
        }

        .dashboard-header i {
            color: #7e22ce
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1.25rem
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #7e22ce;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600
        }

        .user-name {
            font-weight: 500
        }

        .user-role {
            font-size: .8rem;
            color: #6b7280
        }

        .back-btn {
            background: #6c757d;
            color: #fff;
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            display: flex;
            gap: 8px;
            align-items: center
        }

        .back-btn i {
            color: #fff
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px
        }

        .card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, .08)
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px
        }

        .btn {
            padding: 12px 18px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            display: flex;
            gap: 8px;
            align-items: center;
            font-weight: 500
        }

        .btn-success {
            background: #7e22ce;
            color: #fff
        }

        .btn-success:hover {
            background: #5b21b6
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px
        }

        .table th,
        .table td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb
        }

        .table th {
            background: #f9fafb;
            font-size: .85rem;
            text-transform: uppercase;
            color: #6b7280
        }

        .table tr:hover {
            background: #f4f6f9
        }

        .acoes {
            display: flex;
            gap: 10px
        }

        .btn-acao {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none
        }

        .edit {
            background: #dcfce7;
            color: #166534
        }

        .delete {
            background: #dc2626;
            color: #fff
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .6);
            align-items: center;
            justify-content: center
        }

        .modal.active {
            display: flex
        }

        .modal-box {
            background: #fff;
            border-radius: 16px;
            padding: 28px;
            width: 100%;
            max-width: 480px
        }

        .form-group {
            margin-bottom: 16px
        }

        label {
            font-weight: 500;
            margin-bottom: 6px;
            display: block
        }

        input,
        select {
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            border: 1px solid #d1d5db
        }
    </style>
</head>

<body>

    <header class="dashboard-header">
        <h2><i class="fas fa-building"></i> Condomínio Digital</h2>

        <div class="user-info">
            <div class="user-avatar"><?= $iniciais ?></div>
            <div>
                <div class="user-name"><?= $userName ?></div>
                <div class="user-role"><i class="fas fa-user-shield"></i> Síndico</div>
            </div>
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </header>

    <div class="container">

        <div class="card top">
            <strong>Unidades cadastradas</strong>
            <button class="btn btn-success" onclick="abrirModal()">
                <i class="fas fa-plus"></i> Nova Unidade
            </button>
        </div>

        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nº</th>
                        <th>Rua / Bloco</th>
                        <th>Tipo</th>
                        <th>Moradores</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unidades as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['numero']) ?></td>
                            <td><?= htmlspecialchars($u['rua'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($u['tipo_unidade']) ?></td>
                            <td><?= (int)$u['moradores'] ?></td>
                            <td class="acoes">
                                <a href="#" class="btn-acao edit"
                                    onclick="editarUnidade(
                       <?= $u['id_unidade'] ?>,
                       '<?= htmlspecialchars($u['numero'], ENT_QUOTES) ?>',
                       '<?= htmlspecialchars($u['rua'] ?? '', ENT_QUOTES) ?>',
                       '<?= htmlspecialchars($u['tipo_unidade'], ENT_QUOTES) ?>'
                   )">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <a href="unidade.php?action=excluir&id=<?= $u['id_unidade'] ?>"
                                    class="btn-acao delete"
                                    onclick="return confirm('Deseja eliminar esta unidade?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

    <div class="modal" id="modal">
        <div class="modal-box">

            <h3 id="modalTitulo">Nova Unidade</h3>

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
            </form>

        </div>
    </div>

    <script>
        const modal = document.getElementById('modal');
        const action = document.getElementById('action');
        const id_unidade = document.getElementById('id_unidade');
        const numero = document.getElementById('numero');
        const rua = document.getElementById('rua');
        const tipo = document.getElementById('tipo_unidade');

        function abrirModal() {
            modal.classList.add('active');
            action.value = 'adicionar';
            id_unidade.value = '';
            numero.value = '';
            rua.value = '';
            tipo.value = '';
            document.getElementById('modalTitulo').innerText = 'Nova Unidade';
        }

        function editarUnidade(id, n, r, t) {
            modal.classList.add('active');
            action.value = 'editar';
            id_unidade.value = id;
            numero.value = n;
            rua.value = r;
            tipo.value = t;
            document.getElementById('modalTitulo').innerText = 'Editar Unidade';
        }
    </script>

<script src="../../../assets/js/auto-logout.js"></script>

</body>

</html>