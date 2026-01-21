<?php
// VERIFICA SE A SESSÃO JÁ ESTÁ INICIADA ANTES DE CHAMAR session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se a página atual é o index
$current_page = basename($_SERVER['PHP_SELF']);
$is_index = ($current_page == 'index.php');

// Se não for o index, mostra o sidebar
if (!$is_index):
    // Buscar informações completas do síndico
    require_once __DIR__ . '/../../data/conector.php';
    $conector = new Conector();
    $conexao = $conector->getConexao();
    
    $query = "SELECT s.*, u.email FROM sindico s 
              INNER JOIN usuario u ON s.id_usuario = u.id_usuario 
              WHERE s.id_usuario = ?";
    $stmt = $conexao->prepare($query);
    $stmt->bind_param("s", $_SESSION['id']);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        $sindico = $resultado->fetch_assoc();
        $userName = $sindico['nome'];
        $userEmail = $sindico['email'];
        $iniciais = strtoupper(substr($userName, 0, 1));
    } else {
        // Se não encontrou, define valores padrão
        $userName = 'Administrador';
        $userEmail = $_SESSION['email'] ?? '';
        $iniciais = 'A';
    }
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Síndico</title>
    <link rel="stylesheet" href="../../../assets/css/sidebarS.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="has-sidebar">
    <div class="dashboard-with-sidebar">
        <aside class="sidebar">
            <!-- Cabeçalho do Sidebar -->
            <div class="sidebar-header">
                <h2><i class="fas fa-building"></i> Gestão Condominial</h2>
                <div class="sidebar-subtitle">Sistema do Síndico</div>
                
                <!-- Informações do usuário -->
                <div class="sidebar-user-info">
                    <div class="sidebar-user-avatar">
                        <?php echo $iniciais; ?>
                    </div>
                    <div class="sidebar-user-details">
                        <div class="sidebar-user-name"><?php echo htmlspecialchars($userName); ?></div>
                        <div class="sidebar-user-role">
                            <i class="fas fa-user-shield"></i> Síndico
                        </div>
                    </div>
                </div>
            </div>

            <!-- Menu de navegação -->
            <ul class="sidebar-menu">
                <li>
                    <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="moradores.php" class="<?php echo ($current_page == 'moradores.php') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Moradores
                    </a>
                </li>
                <li>
                    <a href="funcionarios.php" class="<?php echo ($current_page == 'funcionarios.php') ? 'active' : ''; ?>">
                        <i class="fas fa-user-tie"></i> Funcionários
                    </a>
                </li>
                <li>
                    <a href="novoPorteiro.php" class="<?php echo ($current_page == 'novoPorteiro.php') ? 'active' : ''; ?>">
                        <i class="fas fa-user-plus"></i> Novo Porteiro
                    </a>
                </li>
                <li>
                    <a href="novoUser.php" class="<?php echo ($current_page == 'novoUser.php') ? 'active' : ''; ?>">
                        <i class="fas fa-user-plus"></i> Novo Usuário
                    </a>
                </li>
                <li>
                    <a href="novoVeiculo.php" class="<?php echo ($current_page == 'novoVeiculo.php') ? 'active' : ''; ?>">
                        <i class="fas fa-car"></i> Novo Veículo
                    </a>
                </li>
                <li>
                    <a href="areas.php" class="<?php echo ($current_page == 'areas.php') ? 'active' : ''; ?>">
                        <i class="fas fa-swimming-pool"></i> Áreas Comuns
                    </a>
                </li>
                <li>
                    <a href="reservas.php" class="<?php echo ($current_page == 'reservas.php') ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i> Reservas
                    </a>
                </li>
                <li>
                    <a href="avisos.php" class="<?php echo ($current_page == 'avisos.php') ? 'active' : ''; ?>">
                        <i class="fas fa-bullhorn"></i> Avisos
                    </a>
                </li>
                <li>
                    <a href="ocorrencias.php" class="<?php echo ($current_page == 'ocorrencias.php') ? 'active' : ''; ?>">
                        <i class="fas fa-exclamation-triangle"></i> Ocorrências
                    </a>
                </li>
            </ul>

            <!-- Botões de ação rápida -->
            <div class="sidebar-actions">
                <a href="index.php" class="sidebar-btn secondary">
                    <i class="fas fa-arrow-left"></i> Voltar ao Início
                </a>
            </div>

            <!-- Footer do sidebar -->
            <div class="sidebar-footer">
                <a href="../../logout.php?logout=1" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Sair do Sistema
                </a>
            </div>
        </aside>

        <!-- Área para conteúdo principal -->
        <main class="main-content-with-sidebar">
<?php endif; ?>