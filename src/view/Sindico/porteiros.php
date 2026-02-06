<?php
session_start();
require_once __DIR__ . '/../../data/conector.php';

if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] !== 'Sindico') {
    header('Location: ../../login.php');
    exit;
}

$conexao = (new Conector())->getConexao();

$stmt = $conexao->prepare("SELECT nome FROM Sindico WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$sindico = $stmt->get_result()->fetch_assoc();

$userName = $sindico['nome'];
$iniciais = strtoupper(substr($userName, 0, 1));

$stmt = $conexao->prepare("
    SELECT p.id_porteiro, p.nome, u.email
    FROM Porteiro p
    INNER JOIN Usuario u ON u.id_usuario = p.id_usuario
    WHERE u.tipo = 'Porteiro'
    ORDER BY p.nome
");
$stmt->execute();
$porteiros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gestão de Porteiros</title>
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

        /* CONTAINER */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px
        }

        /* CARD */
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
            align-items: center;
        }

        .btn-primary {
            background: #7e22ce;
            color: #fff;
            padding: 12px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            background: #5b21b6
        }

        /* TABLE */
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

        .actions {
            display: flex;
            gap: 10px
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .btn-reset {
            background: #7e22ce;
            color: #fff
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626
        }

        /* MODAL */
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
            max-width: 450px;
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

        input {
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            border: 1px solid #d1d5db;
            margin-bottom: 14px;
        }

        /* BOTÕES DO MODAL */
        .modal-actions {
            margin-top: 10px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .modal-actions .btn-primary {
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
    </style>
</head>

<body>

    <header class="dashboard-header">
        <div>
            <h2><i class="fas fa-building"></i> Condominio Digital</h2>
            <div class="header-subtitle">Gestão de Porteiros</div>
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
            <strong>Porteiros cadastrados</strong>
            <button class="btn-primary" onclick="abrirModalNovo()">
                <i class="fas fa-user-plus"></i> Novo Porteiro
            </button>
        </div>

        <div class="card">
            <?php if (empty($porteiros)): ?>
                <p style="text-align:center;color:#6b7280">Nenhum porteiro cadastrado</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($porteiros as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['nome']) ?></td>
                                <td><?= htmlspecialchars($p['email']) ?></td>
                                <td class="actions">
                                    <a href="../../controller/Sindico/porteiro.php?action=reset&id=<?= $p['id_porteiro'] ?>"
                                        class="btn-icon btn-reset">
                                        <i class="fas fa-key"></i>
                                    </a>
                                    <a href="../../controller/Sindico/porteiro.php?action=delete&id=<?= $p['id_porteiro'] ?>"
                                        class="btn-icon btn-delete"
                                        onclick="return confirm('Excluir porteiro?')">
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

    <!-- MODAL NOVO PORTEIRO -->
    <div class="modal" id="modalNovo">
        <div class="box">

            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Novo Porteiro</h3>
            </div>

            <form action="../../controller/Sindico/porteiro.php" method="POST">
                <input type="text" name="nome" placeholder="Nome completo" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="senha" placeholder="Senha" required>
                <input type="password" name="confirmar_senha" placeholder="Confirmar senha" required>

                <div class="modal-actions">
                    <button class="btn-primary" type="submit">
                        <i class="fas fa-save"></i> Criar Porteiro
                    </button>
                    <button type="button" class="btn-cancel" onclick="fecharModal()">
                        Cancelar
                    </button>
                </div>
            </form>

        </div>
    </div>

    <script>
        function abrirModalNovo() {
            document.getElementById('modalNovo').classList.add('active');
        }

        function fecharModal() {
            document.getElementById('modalNovo').classList.remove('active');
        }
    </script>

<script src="../../../assets/js/auto-logout.js"></script>

</body>

</html>