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
    <title>Ocorrências – Síndico</title>

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
            background: linear-gradient(180deg, #f4f6f9, #eef1f6);
            color: #1f2937
        }

        /* ===== HEADER ===== */
        .dashboard-header {
            background: #fff;
            padding: 22px 36px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 5px solid #7e22ce;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .08);
            position: sticky;
            top: 0;
            z-index: 10
        }

        .dashboard-header h2 {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 22px;
            font-weight: 600
        }

        .dashboard-header i {
            color: #7e22ce
        }

        .header-subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-top: 4px
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 16px
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #7e22ce;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600
        }

        .user-details {
            text-align: right
        }

        .user-role {
            font-size: 12px;
            color: #6b7280
        }

        .back-btn {
            margin-left: 20px;
            text-decoration: none;
            background: #ede9fe;
            color: #7e22ce;
            padding: 10px 16px;
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: .3s
        }

        .back-btn:hover {
            background: #ddd6fe;
            transform: translateX(-3px)
        }

        /* ===== CONTAINER ===== */
        .container {
            max-width: 1300px;
            margin: 40px auto;
            padding: 0 24px
        }

        /* ===== GRID ===== */
        .ocorrencias-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 26px
        }

        /* ===== CARD ===== */
        .ocorrencia-card {
            background: #ffffff;
            padding: 24px;
            border-radius: 22px;
            box-shadow: 0 14px 35px rgba(0, 0, 0, .12);
            border-left: 6px solid #d1d5db;
            transition: .3s
        }

        .ocorrencia-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 45px rgba(0, 0, 0, .16)
        }

        /* STATUS CORES */
        .ocorrencia-card.status-Pendente {
            border-color: #f59e0b
        }

        .ocorrencia-card.status-EmAnalise {
            border-color: #7c3aed
        }

        .ocorrencia-card.status-Resolvido {
            border-color: #10b981
        }

        /* HEADER DO CARD */
        .ocorrencia-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px
        }

        .ocorrencia-header h3 {
            font-size: 18px;
            font-weight: 600
        }

        .tipo {
            background: #ede9fe;
            color: #4a148c;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600
        }

        /* TEXTO */
        .descricao {
            font-size: 14px;
            color: #374151;
            margin-bottom: 18px
        }

        /* FOOTER */
        .ocorrencia-footer {
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .status {
            padding: 7px 16px;
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

        .btn-primary {
            background: #7e22ce;
            color: #fff;
            padding: 10px 18px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: .3s
        }

        .btn-primary:hover {
            background: #5b21b6
        }

        /* ===== MODAL ===== */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .6);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
            z-index: 999
        }

        .modal.active {
            display: flex
        }

        .modal-box {
            background: #fff;
            width: 100%;
            max-width: 520px;
            padding: 28px;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, .25)
        }

        .modal-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px
        }

        .modal-header i {
            color: #7e22ce
        }

        textarea,
        select {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: 1px solid #d1d5db;
            margin-bottom: 14px
        }
    </style>
</head>

<body>

    <header class="dashboard-header">
        <div>
            <h2><i class="fas fa-building"></i> Condomínio Digital</h2>
            <div class="header-subtitle">Ocorrências</div>
        </div>

        <div class="user-info">
            <div class="user-avatar"><?= $iniciais ?></div>
            <div class="user-details">
                <div><?= htmlspecialchars($userName) ?></div>
                <div class="user-role"><i class="fas fa-user-shield"></i> Síndico</div>
            </div>
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </header>

    <div class="container">

        <div class="ocorrencias-grid">

            <?php foreach ($ocorrencias as $o): ?>
                <div class="ocorrencia-card status-<?= str_replace(' ', '', $o['status']) ?>">

                    <div class="ocorrencia-header">
                        <h3><?= htmlspecialchars($o['titulo']) ?></h3>
                        <span class="tipo"><?= htmlspecialchars($o['tipo']) ?></span>
                    </div>

                    <p class="descricao">
                        <?= nl2br(htmlspecialchars($o['descricao'])) ?>
                    </p>

                    <p style="font-size:13px;color:#6b7280;margin-bottom:16px">
                        <strong>Morador:</strong> <?= htmlspecialchars($o['morador']) ?>
                    </p>

                    <div class="ocorrencia-footer">
                        <span class="status status-<?= str_replace(' ', '', $o['status']) ?>">
                            <?= $o['status'] ?>
                        </span>

                        <?php if ($o['status'] !== 'Resolvido'): ?>
                            <button class="btn-primary"
                                onclick='abrirModal(<?= json_encode($o) ?>)'>
                                <i class="fas fa-reply"></i> Responder
                            </button>
                        <?php endif; ?>

                    </div>

                </div>
            <?php endforeach; ?>

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
        function abrirModal(o) {
            document.getElementById('modal').classList.add('active');
            document.getElementById('id_ocorrencia').value = o.id_ocorrencia;
            document.getElementById('status').value = o.status;
            document.getElementById('resposta').value = o.resposta_sindico ?? '';
        }

        function fecharModal() {
            document.getElementById('modal').classList.remove('active');
        }
    </script>

</body>

</html>