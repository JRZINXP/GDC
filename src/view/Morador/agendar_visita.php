<?php
session_start();
require_once __DIR__ . '/../../data/conector.php';

/* PROTEÇÃO */
if (
    !isset($_SESSION['id']) ||
    !isset($_SESSION['tipo_usuario']) ||
    $_SESSION['tipo_usuario'] !== 'Morador'
) {
    header('Location: ../../login.php');
    exit;
}

$conexao = (new Conector())->getConexao();

/* Buscar morador */
$stmt = $conexao->prepare("SELECT id_morador, nome FROM Morador WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$morador = $stmt->get_result()->fetch_assoc();

$idMorador = $morador['id_morador'];
$nomeMorador = $morador['nome'];
$iniciais  = strtoupper(substr($nomeMorador, 0, 1));

/* Buscar visitas */
$stmt = $conexao->prepare("
    SELECT a.data, a.hora, v.nome AS visitante, v.documento
    FROM Agendamento a
    JOIN Visitante v ON v.id_visitante = a.id_visitante
    WHERE a.id_morador = ?
    ORDER BY a.data DESC, a.hora DESC
");
$stmt->bind_param("i", $idMorador);
$stmt->execute();
$visitas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Minhas Visitas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f4f6f9;
            margin: 0;
        }

        /* CONTAINER */
        .container {
            max-width: 1100px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .08);
        }

        /* HEADER */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .header h2 {
            color: #333;
            display: flex;
            gap: 10px;
        }

        .btn-add {
            background: #9743d7;
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: .3s;
        }

        .btn-add:hover {
            background: #8639c2;
            transform: translateY(-2px);
        }

        /* GRID DE VISITAS */
        .visitas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        /* CARD */
        .visita-card {
            background: #fafafa;
            border-radius: 12px;
            padding: 20px;
            border-left: 5px solid #9743d7;
            box-shadow: 0 6px 15px rgba(0, 0, 0, .06);
            transition: .3s;
        }

        .visita-card:hover {
            transform: translateY(-4px);
        }

        .visita-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .visita-header h4 {
            margin: 0;
            color: #333;
        }

        .status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pendente {
            background: #fff3cd;
            color: #856404;
        }

        /* INFO */
        .visita-info {
            font-size: 14px;
            color: #555;
        }

        .visita-info div {
            margin-bottom: 6px;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .visita-info i {
            color: #9743d7;
        }

        /* MODAL */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .55);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal {
            background: #ffffff;
            width: 500px;
            padding: 30px;
            border-radius: 14px;
        }

        .modal h3 {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            font-size: 14px;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        input:focus {
            outline: none;
            border-color: #9743d7;
        }

        .modal button {
            width: 100%;
            padding: 14px;
            background: #9743d7;
            color: #fff;
            border: none;
            border-radius: 8px;
            margin-top: 10px;
        }

        .close {
            background: #6c757d;
        }

        /* ===== LAYOUT ===== */
        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 240px;
            background: #9743d7;
            color: #ffffff;
            padding: 25px 20px;
            display: flex;
            flex-direction: column;
        }

        /* TÍTULO */
        .sidebar h2 {
            font-size: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        /* NAV */
        .sidebar nav {
            display: flex;
            flex-direction: column;
        }

        /* LINKS */
        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            color: #ffffff;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 10px;
            font-size: 15px;
            transition: background 0.2s ease, color 0.2s ease;
            background: transparent;
            /* IMPORTANTE */
        }

        /* ÍCONES */
        .sidebar nav a i {
            color: #ffffff;
        }

        /* HOVER (somente quando NÃO ativo) */
        .sidebar nav a:hover:not(.active) {
            background: rgba(255, 255, 255, 0.18);
        }

        /* ===== ITEM ATIVO — BRANCO REAL ===== */
        .sidebar nav a.active {
            background: #ffffff !important;
            color: #9743d7 !important;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* ÍCONE DO ATIVO */
        .sidebar nav a.active i {
            color: #9743d7 !important;
        }

        /* LOGOUT */
        .sidebar .logout {
            margin-top: auto;
            background: rgba(0, 0, 0, 0.25);
        }

        /* ===== CONTEÚDO ===== */
        .content {
            flex: 1;
            padding: 40px;
            background: #f4f6f9;
        }

        /* ===== LAYOUT ===== */
        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 240px;
            background: #9743d7;
            color: #ffffff;
            padding: 25px 20px;
            display: flex;
            flex-direction: column;
        }

        /* TÍTULO */
        .sidebar h2 {
            font-size: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        /* NAV */
        .sidebar nav {
            display: flex;
            flex-direction: column;
        }

        /* LINKS */
        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            color: #ffffff;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 10px;
            font-size: 15px;
            transition: background 0.2s ease, color 0.2s ease;
            background: transparent;
            /* IMPORTANTE */
        }

        /* ÍCONES */
        .sidebar nav a i {
            color: #ffffff;
        }

        /* HOVER (somente quando NÃO ativo) */
        .sidebar nav a:hover:not(.active) {
            background: rgba(255, 255, 255, 0.18);
        }

        /* ===== ITEM ATIVO — BRANCO REAL ===== */
        .sidebar nav a.active {
            background: #ffffff !important;
            color: #9743d7 !important;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* ÍCONE DO ATIVO */
        .sidebar nav a.active i {
            color: #9743d7 !important;
        }

        /* LOGOUT */
        .sidebar .logout {
            margin-top: auto;
            background: rgba(0, 0, 0, 0.25);
        }

        /* ===== CONTEÚDO ===== */
        .content {
            flex: 1;
            padding: 40px;
            background: #f4f6f9;
        }

        /* ===== HEADER ===== */
        .dashboard-header {
            background: #ffffff;
            padding: 22px 36px;
            display: flex;
            justify-content: space-between;
            align-items: center;

            border-radius: 14px;
            /* aparência clean */
            margin: 25px;
            /* AQUI está o espaço */
            margin-bottom: 30px;

            border-bottom: 4px solid #9743d7;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        }

        /* ESQUERDA */
        .header-left h2 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 22px;
            font-weight: 600;
            color: #1f2937;
        }

        .header-left h2 i {
            color: #9743d7;
        }

        .header-subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-top: 4px;
        }

        /* DIREITA */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        /* BOTÃO */
        .btn-primary {
            background: #9743d7;
            color: #ffffff;
            border: none;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;

            display: flex;
            align-items: center;
            gap: 8px;

            transition: 0.25s ease;
        }

        .btn-primary:hover {
            background: #7e22ce;
            transform: translateY(-2px);
        }

        /* USER */
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #9743d7;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        /* CONTEÚDO */
        .page-content {
            padding: 0 25px 40px;
        }

        /* RESPONSIVO */
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                margin: 15px;
            }

            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
        }
        .page-actions {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 25px;
}

    </style>
</head>

<body>

    <div class="layout">

        <aside class="sidebar">
            <h2>
                <i class="fas fa-home"></i>
                Morador
            </h2>

            <nav>
                <a href="index.php">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>

                <a href="agendar_visita.php" class="active" style="background: rgba(255,255,255,0.2);">
                    <i class="fas fa-users"></i> Visitas
                </a>

                <a href="reservas.php">
                    <i class="fas fa-calendar-check"></i> Reservas
                </a>

                <a href="encomendas.php">
                    <i class="fas fa-box"></i> Encomendas
                </a>

                <a href="avisos.php">
                    <i class="fas fa-bullhorn"></i> Avisos
                </a>

                <a href="ocorrencias.php">
                    <i class="fas fa-exclamation-triangle"></i> Ocorrências
                </a>

                <a href="../../logout.php?logout=1" class="logout">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </nav>
        </aside>

        <main class="content">
                <header class="dashboard-header">
                    <div class="header-left">
                        <h2>
                            <i class="fas fa-users"></i>
                            Minhas Visitas
                        </h2>
                        <div class="header-subtitle">
                            Consulte e agende visitas para sua residência
                        </div>
                    </div>
                        <div class="user-info">
                            <div class="user-avatar"><?= $iniciais ?></div>
                            <strong><?= htmlspecialchars($nomeMorador) ?></strong>
                        </div>

                </header>
                <div class="container">

<div class="page-actions">
        <button class="btn-primary" onclick="abrirModal()">
            <i class="fas fa-calendar-plus"></i>
            Agendar Visita
        </button>
    </div>
                <div class="visitas-grid">
                    <?php if (empty($visitas)): ?>
                        <p style="color:#777">Nenhuma visita agendada.</p>
                    <?php else: ?>
                        <?php foreach ($visitas as $v): ?>
                            <div class="visita-card">
                                <div class="visita-header">
                                    <h4><?= htmlspecialchars($v['visitante']) ?></h4>
                                </div>

                                <div class="visita-info">
                                    <div>
                                        <i class="fas fa-id-card"></i>
                                        <?= htmlspecialchars($v['documento']) ?>
                                    </div>
                                    <div>
                                        <i class="fas fa-calendar"></i>
                                        <?= date('d/m/Y', strtotime($v['data'])) ?>
                                    </div>
                                    <div>
                                        <i class="fas fa-clock"></i>
                                        <?= date('H:i', strtotime($v['hora'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>

        </main>
    </div>

    <!-- MODAL -->
    <div class="modal-overlay" id="modal">
        <div class="modal">
            <h3>
                <i class="fas fa-calendar-plus"></i>
                Agendar Visita
            </h3>

            <form action="../../controller/Morador/visitas.php" method="POST">
                <div class="form-group">
                    <label>Nome do Visitante</label>
                    <input type="text" name="nome_visitante" required>
                </div>

                <div class="form-group">
                    <label>Documento</label>
                    <input type="text" name="documento" required>
                </div>

                <div class="form-group">
                    <label>Data</label>
                    <input type="date" name="data" required min="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label>Hora</label>
                    <input type="time" name="hora" required>
                </div>

                <button type="submit">
                    <i class="fas fa-check"></i> Agendar Visita
                </button>

                <button type="button" class="close" onclick="fecharModal()">
                    Cancelar
                </button>
            </form>
        </div>
    </div>

    <script>
        function abrirModal() {
            document.getElementById('modal').style.display = 'flex';
        }

        function fecharModal() {
            document.getElementById('modal').style.display = 'none';
        }
    </script>

</body>

</html>