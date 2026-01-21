<?php
session_start();
require_once __DIR__ . '/../../data/conector.php';

$conector = new Conector();
$conexao = $conector->getConexao();

$stmt = $conexao->prepare("Select * from porteiro Where id_usuario = ?");
$stmt->bind_param("s", $_SESSION['id']);
$stmt->execute();
$resultado = $stmt->get_result();

if($resultado->num_rows > 0) {
    $row = $resultado->fetch_assoc();
    $userName = $row['nome'];
    $iniciais = strtoupper(substr($userName, 0, 1));
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Porteiro</title>

    <!-- CSS DO PORTEIRO -->
    <link rel="stylesheet" href="../../../assets/css/porteiro.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<header class="dashboard-header">
    <div class="header-left">
        <h2><i class="fas fa-door-closed"></i> Condomínio Digital</h2>
        <div class="header-subtitle">Dashboard do Porteiro</div>
    </div>

    <div class="user-info">
        <div class="user-avatar"><?php echo $iniciais; ?></div>
        <div class="user-details">
            <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
            <div class="user-role">
                <i class="fas fa-user-shield"></i> Porteiro
            </div>
        </div>
        <a href="../../logout.php?logout=1" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Sair
        </a>
    </div>
</header>

<!-- ===== CONTEÚDO ===== -->
<main class="dashboard-container">

    <!-- WELCOME -->
    <section class="welcome-section">
        <h1><i class="fas fa-user-shield"></i> Bem-vindo, <?php echo htmlspecialchars($userName); ?>!</h1>
        <p>
            Painel de controlo da portaria.  
            Aqui você pode registrar <strong>entrada e saída de visitantes</strong>,
            controlar encomendas e garantir a segurança do condomínio.
        </p>

        <div class="quick-actions">
            <a href="registro.php" class="action-btn">
                <i class="fas fa-user-check"></i> Registrar Visita
            </a>

            <a href="entregas.php" class="action-btn">
                <i class="fas fa-box"></i> Registrar Entrega
            </a>

            <a href="avisos.php" class="action-btn">
                <i class="fas fa-bullhorn"></i> Avisos
            </a>

            <a href="relatorio_diario.php" class="action-btn">
                <i class="fas fa-file-alt"></i> Relatório Diário
            </a>

            <a href="emergencia.php" class="action-btn">
                <i class="fas fa-phone-alt"></i> Emergência
            </a>
        </div>
    </section>

    <!-- CARDS -->
    <div class="dashboard-grid">

        <div class="dashboard-card">
            <div class="card-title">
                <i class="fas fa-users"></i> Visitas Hoje
            </div>
            <div class="card-content">
                <p>—</p>
                <p>Controle diário de visitas</p>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-title">
                <i class="fas fa-sign-in-alt"></i> Entradas
            </div>
            <div class="card-content">
                <p>—</p>
                <p>Visitantes em circulação</p>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-title">
                <i class="fas fa-sign-out-alt"></i> Saídas
            </div>
            <div class="card-content">
                <p>—</p>
                <p>Visitas concluídas</p>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-title">
                <i class="fas fa-box-open"></i> Encomendas
            </div>
            <div class="card-content">
                <p>—</p>
                <p>Itens pendentes</p>
            </div>
        </div>

    </div>

</main>

<!-- FOOTER -->
<footer class="dashboard-footer">
    <p>Sistema Condomínio Digital &copy; <?php echo date('Y'); ?></p>
    <p>Portaria</p>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.dashboard-card').forEach((card, i) => {
        card.style.animationDelay = `${i * 0.1}s`;
        card.classList.add('fade-in');
    });
});
</script>

</body>
</html>
