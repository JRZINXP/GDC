<?php
require_once __DIR__ . '/../../data/conector.php';
session_start();

if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] !== 'Sindico') {
    header('Location: ../../login.php');
    exit;
}

$conexao = (new Conector())->getConexao();

/* EDITAR MORADOR */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'editar') {
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

/* DESATIVAR MORADOR */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'desativar') {
    $id_morador = (int)$_POST['id_morador'];
    // Desativa o morador
    $stmt = $conexao->prepare("UPDATE Morador SET activo = 0 WHERE id_morador = ?");
    $stmt->bind_param("i", $id_morador);
    $stmt->execute();

    // Busca o id_usuario relacionado
    $stmt = $conexao->prepare("SELECT id_usuario FROM Morador WHERE id_morador = ?");
    $stmt->bind_param("i", $id_morador);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $id_usuario = $row['id_usuario'];
        // Desativa também o usuário
        $stmt2 = $conexao->prepare("UPDATE Usuario SET activo = 0 WHERE id_usuario = ?");
        $stmt2->bind_param("i", $id_usuario);
        $stmt2->execute();
    }
}

/* DADOS DO SÍNDICO */
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

        .dashboard-header {
            background: white;
            color: #1f2937;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-bottom: 3px solid #7e22ce;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .dashboard-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .dashboard-header h2 i {
            color: #7e22ce;
        }

        .header-subtitle {
            font-size: .875rem;
            color: #6b7280;
            margin-top: .25rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: #7e22ce;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .user-details {
            text-align: right;
        }

        .user-name {
            font-weight: 500;
            font-size: 1rem;
            color: #1f2937;
        }

        .user-role {
            font-size: .75rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: .25rem;
            margin-top: .125rem;
        }

        .back-btn {
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            background: #6c757d;
        }

        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px
        }

        .card {
            background: #fff;
            border-radius: 14px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .08);
            margin-bottom: 25px;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .btn-success {
            background: #7e22ce;
            color: #fff;
            padding: 12px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: .3s;
            text-align: center;
        }

        .btn-success:hover {
            background: #5b21b6;
            transform: translateY(-2px)
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        th {
            background: #f3f4f6;
            padding: 16px;
            text-align: left
        }

        td {
            padding: 16px;
            border-top: 1px solid #e5e7eb
        }

        tr:hover {
            background: #f9fafb
        }

        .actions {
            display: flex;
            gap: 10px
        }

        .actions a {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none
        }

        .edit {
            background: #dbeafe;
            color: #1e40af
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .6);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
            z-index: 999;
        }

        .modal.active {
            display: flex
        }

        .box {
            background: #fff;
            border-radius: 16px;
            width: 100%;
            max-width: 480px;
            padding: 28px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, .25);
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h3 {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-header i {
            color: #7e22ce
        }

        input,
        select {
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            border: 1px solid #d1d5db;
            margin-bottom: 14px;
        }

        .modal-actions {
            margin-top: 10px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .modal-actions .btn-success {
            width: 100%;
        }

        .btn-cancel {
            width: 100%;
            background: #ef4444;
            color: #fff;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: .3s;
        }

        .btn-cancel:hover {
            background: #dc2626;
        }

        .back-btn,
        .back-btn i {
            color: #ffffff !important;
        }

        .delete {
            background: #fee2e2;
            color: #991b1b;
            transition: .3s;
        }

        .delete:hover {
            background: #fca5a5;
        }
    </style>
</head>

<body>

    <header class="dashboard-header">
        <div>
            <h2><i class="fas fa-building"></i> Condominio Digital</h2>
            <div class="header-subtitle">Gestão de moradores</div>
        </div>
        <div class="user-info">
            <div class="user-avatar"><?php echo $iniciais; ?></div>
            <div class="user-details">
                <div class="user-name"><?php echo $userName; ?></div>
                <div class="user-role"><i class="fas fa-user-shield"></i> Síndico</div>
            </div>
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </header>

    <div class="container">

        <div class="card top">
            <strong>Lista de moradores</strong>
            <button class="btn-success" onclick="abrirModalNovo()">
                <i class="fas fa-user-plus"></i> Novo Morador
            </button>
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
                    $res = $conexao->query("
    SELECT m.id_morador, m.nome, m.telefone,
           u.email, un.numero AS unidade
    FROM Morador m
    INNER JOIN Usuario u ON m.id_usuario = u.id_usuario
    LEFT JOIN Unidade un ON m.id_unidade = un.id_unidade
    WHERE m.activo = 1
    ORDER BY m.nome
");
                    if ($res->num_rows):
                        while ($m = $res->fetch_assoc()):
                    ?>
                            <tr>
                                <td><?= $m['id_morador'] ?></td>
                                <td><?= htmlspecialchars($m['nome']) ?></td>
                                <td><?= htmlspecialchars($m['email']) ?></td>
                                <td><?= htmlspecialchars($m['unidade'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($m['telefone'] ?? 'N/A') ?></td>
                                <td class="actions">
                                    <a href="#" class="edit"
                                        onclick="abrirEditar(
               '<?= $m['id_morador'] ?>',
               '<?= htmlspecialchars($m['nome'], ENT_QUOTES) ?>',
               '<?= htmlspecialchars($m['telefone'] ?? '', ENT_QUOTES) ?>',
               '<?= $m['unidade'] ?>'
           )">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" class="delete"
                                        onclick="abrirDesativar('<?= $m['id_morador'] ?>', '<?= htmlspecialchars($m['nome'], ENT_QUOTES) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center">Nenhum morador</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL NOVO MORADOR -->
    <div class="modal" id="modalNovo">
        <div class="box">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Novo Morador</h3>
            </div>

            <form action="../../controller/Sindico/user.php" method="post">
                <input type="text" name="nome" placeholder="Nome completo" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="senha" placeholder="Senha" required>

                <select name="unidade" required>
                    <option value="">Selecione a unidade</option>
                    <?php
                    $u = $conexao->query("SELECT id_unidade, numero FROM Unidade ORDER BY numero");
                    while ($un = $u->fetch_assoc()):
                    ?>
                        <option value="<?= $un['id_unidade'] ?>"><?= $un['numero'] ?></option>
                    <?php endwhile; ?>
                </select>

                <input type="text" name="telefone" placeholder="Telefone">

                <div class="modal-actions">
                    <button class="btn-success" type="submit">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                    <button type="button" class="btn-cancel" onclick="fecharModalNovo()">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR MORADOR -->
    <div class="modal" id="modalEditar">
        <div class="box">
            <div class="modal-header">
                <h3><i class="fas fa-user-edit"></i> Editar Morador</h3>
            </div>

            <form method="POST">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="id_morador" id="edit_id">

                <input type="text" name="nome" id="edit_nome" required>
                <input type="text" name="telefone" id="edit_telefone">

                <select name="id_unidade" id="edit_unidade">
                    <option value="">Sem unidade</option>
                    <?php
                    $u = $conexao->query("SELECT id_unidade, numero FROM Unidade ORDER BY numero");
                    while ($un = $u->fetch_assoc()):
                    ?>
                        <option value="<?= $un['id_unidade'] ?>"><?= $un['numero'] ?></option>
                    <?php endwhile; ?>
                </select>

                <div class="modal-actions">
                    <button class="btn-success" type="submit">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                    <button type="button" class="btn-cancel" onclick="fecharEditar()">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="modalDesativar">
        <div class="box">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-circle"></i> Desativar Morador</h3>
            </div>

            <p style="margin-bottom: 20px; color: #6b7280;">
                Tem certeza que deseja desativar o morador <strong id="desativar_nome"></strong>? Esta ação não pode ser desfeita.
            </p>

            <form method="POST">
                <input type="hidden" name="acao" value="desativar">
                <input type="hidden" name="id_morador" id="desativar_id">

                <div class="modal-actions">
                    <button class="btn-cancel" type="submit" style="background: #dc2626;">
                        <i class="fas fa-trash"></i> Desativar
                    </button>
                    <button type="button" class="btn-cancel" onclick="fecharDesativar()" style="background: #6c757d;">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalNovo() {
            document.getElementById('modalNovo').classList.add('active')
        }

        function fecharModalNovo() {
            document.getElementById('modalNovo').classList.remove('active')
        }

        function abrirEditar(id, nome, telefone, unidade) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nome').value = nome;
            document.getElementById('edit_telefone').value = telefone;
            document.getElementById('edit_unidade').value = unidade || '';
            document.getElementById('modalEditar').classList.add('active');
        }

        function fecharEditar() {
            document.getElementById('modalEditar').classList.remove('active')
        }

        function abrirDesativar(id, nome) {
            document.getElementById('desativar_id').value = id;
            document.getElementById('desativar_nome').textContent = nome;
            document.getElementById('modalDesativar').classList.add('active');
        }

        function fecharDesativar() {
            document.getElementById('modalDesativar').classList.remove('active')
        }
    </script>

<script src="../../../assets/js/auto-logout.js"></script>

</body>

</html>