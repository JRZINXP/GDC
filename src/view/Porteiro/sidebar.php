<!-- src/view/Porteiro/sidebar_porteiro.php -->
<?php
// Verifica se estamos na página index (não mostra sidebar no index)
$current_page = basename($_SERVER['PHP_SELF']);
$is_index = ($current_page == 'index.php');
?>

<?php if (!$is_index): ?>
<div class="dashboard-with-sidebar">
    <aside class="sidebar porteiro">
        <div class="sidebar-header">
            <h3><i class="fas fa-door-closed"></i> Menu Porteiro</h3>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="acesso.php" class="<?php echo ($current_page == 'acesso.php') ? 'active' : ''; ?>">
                    <i class="fas fa-key"></i> Controle de Acesso
                </a>
            </li>
            <li>
                <a href="entrega.php" class="<?php echo ($current_page == 'entrega.php') ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i> Entregas
                </a>
            </li>
            <li>
                <a href="visita.php" class="<?php echo ($current_page == 'visita.php') ? 'active' : ''; ?>">
                    <i class="fas fa-user-friends"></i> Visitas
                </a>
            </li>
            <li>
                <a href="avisos.php" class="<?php echo ($current_page == 'avisos.php') ? 'active' : ''; ?>">
                    <i class="fas fa-bullhorn"></i> Avisos
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <a href="../../logout.php?logout=1">
                <i class="fas fa-sign-out-alt"></i> Sair do Sistema
            </a>
        </div>
    </aside>
    
    <main class="main-content-with-sidebar">
<?php endif; ?>