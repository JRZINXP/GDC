<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../data/conector.php';

if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'Morador') {
    header('Location: ../../login.php');
    exit;
}

$conexao = (new Conector())->getConexao();

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo'])) {
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
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Minhas Ocorrências</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif
        }

        body {
            background: #f4f6f9
        }

        .layout {
            display: flex;
            min-height: 100vh
        }

        .sidebar {
            width: 240px;
            background: #9743d7;
            color: #fff;
            padding: 25px 20px;
            display: flex;
            flex-direction: column
        }

        .sidebar h2 {
            font-size: 20px;
            margin-bottom: 30px;
            display: flex;
            gap: 10px;
            align-items: center
        }

        .sidebar nav a {
            color: #fff;
            text-decoration: none;
            padding: 14px 16px;
            border-radius: 10px;
            display: flex;
            gap: 12px;
            margin-bottom: 10px
        }

        .sidebar nav a.active {
            background: #fff;
            color: #9743d7;
            font-weight: 600
        }

        .sidebar nav a.active i {
            color: #9743d7
        }

        .sidebar .logout {
            margin-top: auto;
            background: rgba(0, 0, 0, .25)
        }

        .content {
            flex: 1;
            padding: 40px
        }

        .dashboard-header {
            background: #fff;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #9743d7;
            margin-bottom: 30px
        }

        .header-left h2 {
            display: flex;
            gap: 10px;
            align-items: center
        }

        .header-left i {
            color: #9743d7
        }

        .header-subtitle {
            font-size: 14px;
            color: #555
        }

        .user-info {
            display: flex;
            gap: 10px;
            align-items: center
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #9743d7;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600
        }

        .container {
            background: #fff;
            padding: 30px;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .08)
        }

        .page-actions {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px
        }

        .btn-primary {
            background: #9743d7;
            color: #fff;
            border: none;
            padding: 12px 18px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            gap: 8px;
            align-items: center
        }

        .btn-primary:hover {
            background: #7e22ce
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        th {
            background: #f3f4f6;
            padding: 14px;
            text-align: left
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #e5e7eb
        }

        .status {
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600
        }

        .status-Pendente {
            background: #fff7ed;
            color: #9a3412
        }

        .status-EmAnalise {
            background: #ede9fe;
            color: #4a148c
        }

        .status-Resolvido {
            background: #ecfdf5;
            color: #065f46
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .55);
            justify-content: center;
            align-items: center;
            z-index: 1000
        }

        .modal {
            background: #fff;
            width: 520px;
            padding: 30px;
            border-radius: 16px
        }

        .modal h3 {
            display: flex;
            gap: 10px;
            margin-bottom: 20px
        }

        .form-group {
            margin-bottom: 15px
        }

        label {
            font-weight: 500
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border-radius: 8px;
            border: 1px solid #d1d5db
        }

        textarea {
            resize: none
        }

        .close {
            background: #6b7280
        }
    </style>
</head>

<body>

    <div class="layout">

        <aside class="sidebar">
            <h2><i class="fas fa-home"></i> Morador</h2>
            <nav>
                <a href="index.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="agendar_visita.php"><i class="fas fa-users"></i> Visitas</a>
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
                    <button class="btn-primary" onclick="abrirNova()">
                        <i class="fas fa-plus"></i> Nova Ocorrência
                    </button>
                </div>

                <?php if (empty($ocorrencias)): ?>
                    <p style="color:#777">Nenhuma ocorrência registada.</p>
                <?php else: ?>

                    <table>
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Título</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Resposta</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php foreach ($ocorrencias as $o): ?>
                                <tr>
                                    <td><?= htmlspecialchars($o['tipo']) ?></td>
                                    <td><?= htmlspecialchars($o['titulo']) ?></td>
                                    <td>
                                        <span class="status status-<?= str_replace(' ', '', $o['status']) ?>">
                                            <?= $o['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($o['data_abertura'])) ?></td>
                                    <td>
                                        <?php if (!empty($o['resposta_sindico'])): ?>
                                            <button class="btn-primary"
                                                onclick='verResposta(<?= json_encode($o) ?>)'>
                                                <i class="fas fa-eye"></i> Ver
                                            </button>
                                        <?php else: ?>
                                            <span style="color:#777;font-size:13px">Aguardando</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        </tbody>
                    </table>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <div class="modal-overlay" id="modalNova">
        <div class="modal">
            <h3><i class="fas fa-plus"></i> Nova Ocorrência</h3>

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
                    <textarea name="descricao" rows="4" required></textarea>
                </div>

                <button class="btn-primary">
                    <i class="fas fa-check"></i> Registar
                </button>

                <button type="button" class="btn-primary close" onclick="fecharNova()">
                    Cancelar
                </button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modalResposta">
        <div class="modal">
            <h3><i class="fas fa-reply"></i> Resposta do Síndico</h3>

            <p><strong>Status:</strong> <span id="rStatus"></span></p>
            <p><strong>Respondido em:</strong> <span id="rData"></span></p>

            <hr style="margin:15px 0">

            <p id="rTexto"></p>

            <button class="btn-primary close" onclick="fecharResposta()">
                Fechar
            </button>
        </div>
    </div>

    <script>
        function abrirNova() {
            document.getElementById('modalNova').style.display = 'flex'
        }

        function fecharNova() {
            document.getElementById('modalNova').style.display = 'none'
        }

        function verResposta(o) {
            document.getElementById('modalResposta').style.display = 'flex'
            document.getElementById('rStatus').innerText = o.status
            document.getElementById('rTexto').innerText = o.resposta_sindico

            if (o.data_resolucao) {
                const d = new Date(o.data_resolucao)
                document.getElementById('rData').innerText =
                    d.toLocaleDateString() + ' ' + d.toLocaleTimeString()
            } else {
                document.getElementById('rData').innerText = '--'
            }
        }

        function fecharResposta() {
            document.getElementById('modalResposta').style.display = 'none'
        }
    </script>

    <script src="../../../assets/js/auto-logout.js"></script>

</body>

</html>