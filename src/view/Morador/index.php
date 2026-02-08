<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../data/conector.php';

if (!isset($_SESSION['id']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'Morador') {
    header('Location: ../../login.php');
    exit();
}
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
$conexao = (new Conector())->getConexao();

$stmt = $conexao->prepare("
    SELECT id_morador, id_unidade, nome
    FROM Morador
    WHERE id_usuario = ?
");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$morador = $stmt->get_result()->fetch_assoc();

if (!$morador) {
    session_destroy();
    header("Location: ../../login.php");
    exit;
}

$idMorador = $morador['id_morador'];
$idUnidade = $morador['id_unidade'];
$userName  = $morador['nome'];
$iniciais  = strtoupper(substr($userName, 0, 1));

$hoje = date('Y-m-d');

$stmt = $conexao->prepare("
    SELECT COUNT(*) AS total
    FROM Agendamento
    WHERE id_morador = ?
      AND data = ?
");
$stmt->bind_param("is", $idMorador, $hoje);
$stmt->execute();
$visitasAgendadas = $stmt->get_result()->fetch_assoc()['total'];


$stmt = $conexao->prepare("
    SELECT COUNT(*) AS total
    FROM Reserva
    WHERE id_morador = ?
      AND data = ?
");
$stmt->bind_param("is", $idMorador, $hoje);
$stmt->execute();
$reservasAtivas = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conexao->prepare("
    SELECT COUNT(*) AS total
    FROM Entrega
    WHERE id_morador = ?
      AND status = 0
");
$stmt->bind_param("i", $idMorador);
$stmt->execute();
$entregasPendentes = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conexao->prepare("
    SELECT COUNT(*) AS total
    FROM Aviso a
    WHERE NOT EXISTS (
        SELECT 1
        FROM Leitura_Aviso l
        WHERE l.id_aviso = a.id_aviso
          AND l.id_usuario = ?
    )
");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$avisosNovos = $stmt->get_result()->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Morador</title>
    <link rel="stylesheet" href="../../../assets/css/morador.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

    <header class="dashboard-header">
        <div class="header-left">
            <h2><i class="fas fa-building"></i> Condomínio Digital</h2>
            <div class="header-subtitle">Dashboard do Morador</div>
        </div>

        <div class="user-info">
            <div class="user-avatar">
                <?php echo $iniciais; ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo $userName; ?></div>
                <div class="user-role">
                    <i class="fas fa-home"></i> Morador
                </div>
            </div>
            <a href="../../logout.php?logout=1"
                class="logout-btn"
                onclick="return confirmarSaida();">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>

        </div>
    </header>

    <main class="dashboard-container">

        <section class="welcome-section">
            <h1><i class="fas fa-home"></i> Bem-vindo, <?php echo $userName; ?>!</h1>
            <p>Seu painel de controle para gerenciar todas as atividades do condomínio.
                Aqui você pode agendar visitas, reservar áreas comuns, verificar encomendas
                e acompanhar todas as informações importantes do seu condomínio.</p>

            <div class="quick-actions">
                <a href="agendar_visita.php" class="action-btn">
                    <i class="fas fa-calendar-plus"></i> Agendar Visita
                </a>
                <a href="reservas.php" class="action-btn">
                    <i class="fas fa-glass-cheers"></i> Reservar Área
                </a>
                <a href="encomendas.php" class="action-btn">
                    <i class="fas fa-box"></i> Ver Encomendas
                </a>
                <a href="avisos.php" class="action-btn">
                    <i class="fas fa-bullhorn"></i> Ver Avisos
                </a>
                <a href="ocorrencias.php" class="action-btn">
                    <i class="fas fa-exclamation-triangle"></i> Reportar Problema
                </a>

            </div>
        </section>
        <div class="dashboard-grid">

            <div class="dashboard-card">
                <div class="card-title">
                    <i class="fas fa-users"></i> Visitas Agendadas
                </div>
                <div class="card-content">
                    <p><?= $visitasAgendadas ?></p>
                    <p>Visitas agendadas para hoje</p>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-title">
                    <i class="fas fa-calendar-check"></i> Reservas Ativas
                </div>
                <div class="card-content">
                    <p><?= $reservasAtivas ?></p>

                    <p>Reservas ativas </p>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-title">
                    <i class="fas fa-box-open"></i> Encomendas Pendentes
                    <a href="encomendas.php">
                        <?php if ($entregasPendentes > 0): ?>
                            <span class="notif-bell">
                                <i class="fas fa-bell"></i>
                                <span class="notif-count"><?= $entregasPendentes ?></span>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="card-content">
                    <p><?= $entregasPendentes ?></p>
                    <p>Encomendas para hoje</p>
                </div>
            </div>
            <div class="dashboard-card">
                <div class="card-title">
                    <i class="fas fa-bullhorn"></i> Avisos Novos
                    <a href="avisos.php">
                        <?php if ($avisosNovos > 0): ?>
                            <span class="notif-bell">
                                <i class="fas fa-bell"></i>
                                <span class="notif-count"><?= $avisosNovos ?></span>

                            </span>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="card-content">
                    <p><?= $avisosNovos ?></p>

                    <p>Avisos novos</p>
                </div>
            </div>
        </div>

        <section class="info-section">
            <h3><i class="fas fa-info-circle"></i> Informações da Sessão</h3>
            <table class="info-table">
                <tr>
                    <td><i class="fas fa-user"></i> Nome Completo</td>
                    <td><?php echo $userName; ?></td>
                </tr>
                <tr>
                    <td><i class="fas fa-envelope"></i> Email</td>
                    <td><?php echo $_SESSION['email']; ?></td>
                </tr>

                <tr>
                    <td><i class="fas fa-id-card"></i> ID do Morador</td>
                    <td><?php echo $idMorador; ?></td>
                </tr>
                <tr>
                    <td><i class="fas fa-building"></i> ID da Unidade</td>
                    <td><?php echo $idUnidade; ?></td>
                </tr>
            </table>
        </section>
    </main>

    <footer class="dashboard-footer">
        <p>Sistema Condomínio Digital &copy; <?php echo date('Y'); ?></p>
        <p>Desenvolvido por Nelma Odair Bila</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.dashboard-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('fade-in');
            });
        });

        function confirmarSaida() {
            return confirm("Tem a certeza que deseja sair?");
        }
    </script>
    <script src="../../../assets/js/auto-logout.js"></script>
</body>
</html>