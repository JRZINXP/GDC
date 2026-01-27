<?php
require_once __DIR__ . '/../../data/conector.php';

session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'Sindico') {
    header('Location: ../../login.php');
    exit();
}
$conector = new Conector();
$conexao = $conector->getConexao();

$stmt = $conexao->prepare("Select * from sindico Where id_usuario = ?");
$stmt->bind_param("s", $_SESSION['id']);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $row = $resultado->fetch_assoc();
    $userName = $row['nome'];
    $iniciais = strtoupper(substr($userName, 0, 1));
}
/* =========================
   DASHBOARD CONTADORES
========================= */

/* TOTAL MORADORES */
$totalMoradores = $conexao->query("
    SELECT COUNT(*) total FROM Morador
")->fetch_assoc()['total'];

/* TOTAL RESERVAS (SEM STATUS) */
$totalReservas = $conexao->query("
    SELECT COUNT(*) total FROM Reserva
")->fetch_assoc()['total'];

/* TOTAL OCORRÊNCIAS */
$totalOcorrencias = $conexao->query("
    SELECT COUNT(*) total FROM Ocorrencia
")->fetch_assoc()['total'];

/* TOTAL AVISOS */
$totalAvisos = $conexao->query("
    SELECT COUNT(*) total FROM Aviso
")->fetch_assoc()['total'];


?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Síndico</title>
    <link rel="stylesheet" href="../../../assets/css/sindico.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>

<body>
    <?php include_once 'sidebar.php'; ?>
    <header class="dashboard-header">
        <div>
            <h2><i class="fas fa-building"></i> Gestão Condominial</h2>
            <div class="header-subtitle">Dashboard do Síndico</div>
        </div>

        <div class="user-info">
            <div class="user-avatar">
                <?php echo $iniciais; ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo $userName; ?></div>
                <div class="user-role"><i class="fas fa-user-shield"></i> Síndico</div>
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
            <h1><i class="fas fa-user-shield"></i> Bem-vindo, <?php echo $userName; ?>!</h1>
            <p>Painel de administração para gerenciamento completo do condomínio.</p>
<div class="quick-actions">
    <a href="moradores.php" class="action-btn"><i class="fas fa-users"></i> Gerir Moradores</a>
    <a href="reservas.php" class="action-btn"><i class="fas fa-calendar-check"></i> Gerir Reservas</a>
    <a href="ocorrencias.php" class="action-btn"><i class="fas fa-exclamation-triangle"></i> Gerir Ocorrências</a>
    <a href="avisos.php" class="action-btn"><i class="fas fa-bullhorn"></i> Publicar Avisos</a>
    <a href="novoVeiculo.php" class="action-btn"><i class="fas fa-car"></i> Gerir Veículos</a>
    <a href="porteiros.php" class="action-btn"><i class="fas fa-user-tie"></i> Gerir Porteiros</a>
    <a href="unidade.php" class="action-btn"><i class="fas fa-home"></i> Gerir Unidades</a> <!-- NOVO CARD -->
</div>
        </section>

        <div class="dashboard-grid">

    <div class="dashboard-card">
        <div class="card-title">
            <i class="fas fa-users"></i> Total de Moradores
        </div>
        <div class="card-content">
            <strong><?= $totalMoradores ?></strong>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="card-title">
            <i class="fas fa-calendar-alt"></i> Total de Reservas
        </div>
        <div class="card-content">
            <strong><?= $totalReservas ?></strong>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="card-title">
            <i class="fas fa-exclamation-circle"></i> Ocorrências
        </div>
        <div class="card-content">
            <strong><?= $totalOcorrencias ?></strong>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="card-title">
            <i class="fas fa-bullhorn"></i> Avisos Publicados
        </div>
        <div class="card-content">
            <strong><?= $totalAvisos ?></strong>
        </div>
    </div>

</div>



        <section class="info-section">
            <h3><i class="fas fa-info-circle"></i> Informações da Sessão</h3>
            <table class="info-table">
                <tr>
                    <td><i class="fas fa-user-shield"></i> Perfil</td>
                    <td>Síndico Administrador</td>
                </tr>
                <tr>
                    <td><i class="fas fa-envelope"></i> Email</td>
                    <td><?php echo $_SESSION['email']; ?></td>
                </tr>
                <tr>
                    <td><i class="fas fa-calendar-alt"></i> Último Acesso</td>
                    <td><?php echo date('d/m/Y H:i:s'); ?></td>
                </tr>
                <tr>
                    <td><i class="fas fa-building"></i> Condomínio</td>
                    <td>Residencial das Flores</td>
                </tr>
                <tr>
                    <td><i class="fas fa-tasks"></i> Tarefas Pendentes</td>
                    <td>19 itens</td>
                </tr>
            </table>
        </section>
    </main>

    <footer class="dashboard-footer">
        <p>Sistema Condomínio Digital &copy; <?php echo date('Y'); ?></p>
        <p>Desenvolvido por Nelma Odair Bila</p>
    </footer>
    <script>
        function confirmarSaida() {
    return confirm("Tem a certeza que deseja sair?");
}
    </script>
</body>
</html>