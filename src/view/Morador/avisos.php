<?php
session_start();
require_once __DIR__ . '/../../data/conector.php';

if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] !== 'Morador') {
    header('Location: ../../login.php');
    exit();
}

$conexao = (new Conector())->getConexao();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_aviso'])) {
    $stmt = $conexao->prepare("
        INSERT IGNORE INTO Leitura_Aviso (id_aviso, id_usuario)
        VALUES (?, ?)
    ");
    $stmt->bind_param("ii", $_POST['id_aviso'], $_SESSION['id']);
    $stmt->execute();
    header("Location: avisos.php");
    exit();
}

$stmt = $conexao->prepare("SELECT nome FROM Morador WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$morador = $stmt->get_result()->fetch_assoc();

$nomeMorador = $morador['nome'];
$iniciais = strtoupper(substr($nomeMorador, 0, 1));

$stmtAvisos = $conexao->prepare("
    SELECT 
        a.id_aviso,
        a.titulo,
        a.conteudo,
        a.prioridade,
        la.id_usuario AS lido
    FROM Aviso a
    LEFT JOIN Leitura_Aviso la
        ON la.id_aviso = a.id_aviso
        AND la.id_usuario = ?
    ORDER BY a.id_aviso DESC
");
$stmtAvisos->bind_param("i", $_SESSION['id']);
$stmtAvisos->execute();
$avisos = $stmtAvisos->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Avisos</title>

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
            background: #f4f6f9
        }


        .layout {
            display: flex;
            min-height: 100vh
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 240px;
            background: #9743d7;
            color: #ffffff;
            padding: 25px 20px;
            display: flex;
            flex-direction: column;
        }

        .sidebar h2 {
            font-size: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .sidebar nav {
            display: flex;
            flex-direction: column;
        }

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
            padding: 40px
        }

        /* HEADER */
        .dashboard-header {
            background: rgba(255, 255, 255, 0.5);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-bottom: 3px solid #9743d7;
            margin-bottom: 75px;
        }

        .header-left h2 {
            color: #333;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-left h2 i {
            color: #9743d7;
        }

        .header-subtitle {
            color: #222;
            font-size: 14px;
            margin-top: 5px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: #9743d7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 20px;
        }

        .back-btn {
            background: #6c757d;
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
        }

        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* CARD */
        .section-card {
            background: transparent;
            padding: 0;
            border-radius: 0;
            box-shadow: none;
        }


        /* AVISOS */
        .aviso-item {
            background: #f9fafb;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, .06);
        }

        .prioridade-Baixa {
            border-left: 5px solid #3b82f6
        }

        .prioridade-Média {
            border-left: 5px solid #f59e0b
        }

        .prioridade-Alta {
            border-left: 5px solid #ef4444
        }

        .aviso-item h3 {
            color: #333;
            margin-bottom: 8px;
        }

        .aviso-item p {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
        }

        .aviso-meta {
            margin-top: 10px;
            font-size: 12px;
            color: #777;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-lido {
            background: #9743d7;
            color: #fff;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px
        }

        .lido-ok {
            color: #16a34a;
            font-size: 13px;
            font-weight: 500;
            margin-top: 10px;
            display: inline-block
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
                <a href="avisos.php" class="active"><i class="fas fa-bullhorn"></i> Avisos</a>
                <a href="ocorrencias.php"><i class="fas fa-exclamation-triangle"></i> Ocorrências</a>
                <a href="../../logout.php?logout=1" class="logout">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </nav>
        </aside>

        <main class="content">

            <header class="dashboard-header">
                <div class="header-left">
                    <h2><i class="fas fa-bullhorn"></i> Avisos</h2>
                    <div class="header-subtitle">Comunicados do condomínio</div>
                </div>
                <div class="user-info">
                    <div class="user-avatar"><?= $iniciais ?></div>
                    <strong><?= htmlspecialchars($nomeMorador) ?></strong>
                </div>
            </header>

            <section class="section-card">

                <?php if (empty($avisos)): ?>
                    <p style="color:#777">Nenhum aviso publicado.</p>
                <?php else: ?>
                    <?php foreach ($avisos as $a): ?>
                        <div class="aviso-item prioridade-<?= $a['prioridade'] ?>">
                            <h3><?= htmlspecialchars($a['titulo']) ?></h3>
                            <p><?= nl2br(htmlspecialchars($a['conteudo'])) ?></p>

                            <div class="aviso-meta">
                                <i class="fas fa-flag"></i>
                                Prioridade: <?= $a['prioridade'] ?>
                            </div>

                            <?php if ($a['lido'] === null): ?>
                                <form method="POST">
                                    <input type="hidden" name="id_aviso" value="<?= $a['id_aviso'] ?>">
                                    <button type="submit" class="btn-lido">✔ Marcar como lido</button>
                                </form>
                            <?php else: ?>
                                <span class="lido-ok">✔ Lido</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </section>

        </main>
    </div>

</body>

</html>