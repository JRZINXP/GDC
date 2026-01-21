<?php
require_once __DIR__ . '/../../data/conector.php';

session_start();

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
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprovar Reservas - Síndico</title>
    <link rel="stylesheet" href="../../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Estilos gerais (iguais ao moradores.php) */
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins', sans-serif; }
        body { background: linear-gradient(135deg,#f3f4f6 0%,#e5e7eb 100%); color:#1f2937; min-height:100vh; }

        .dashboard-header {
            background:white;
            color:#1f2937;
            padding:1.5rem 2rem;
            display:flex;
            justify-content:space-between;
            align-items:center;
            box-shadow:0 4px 6px rgba(0,0,0,0.1);
            border-bottom:3px solid #7e22ce;
            position:sticky;
            top:0;
            z-index:100;
        }
        .dashboard-header h2 { font-size:1.5rem; font-weight:600; display:flex; align-items:center; gap:.75rem; }
        .dashboard-header h2 i { color:#7e22ce; }
        .header-subtitle { font-size:.875rem; color:#6b7280; margin-top:.25rem; }

        .user-info { display:flex; align-items:center; gap:1.25rem; }
        .user-avatar {
            width:50px; height:50px;
            background:#7e22ce;
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
            color:white;
            font-weight:600;
            font-size:1rem;
            box-shadow:0 1px 3px rgba(0,0,0,0.1);
        }
        .user-details { text-align:right; }
        .user-name { font-weight:500; font-size:1rem; color:#1f2937; }
        .user-role { font-size:.75rem; color:#6b7280; display:flex; align-items:center; gap:.25rem; margin-top:.125rem; }
        
        .logout-btn, .back-btn {
            background: #ff4757;
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
        .logout-btn:hover, .back-btn:hover {
            background: #ff3742;
            transform: translateY(-2px);
        }
        .back-btn {
            background: #6c757d;
        }
        .back-btn:hover {
            background: #5a6268;
        }

        /* Main container */
        .dashboard-container { max-width:1400px; margin:2rem auto; padding:0 1.25rem; }

        /* Page header */
        .page-header {
            background: white;
            border-radius: .75rem;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .page-header h1 {
            color: #1f2937;
            font-size: 1.75rem;
            display: flex;
            align-items: center;
            gap: .75rem;
        }
        .page-header h1 i { color: #7e22ce; }
        .page-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: #7e22ce;
            color: white;
        }
        .btn-primary:hover {
            background: #5b21b6;
            transform: translateY(-2px);
        }
        .btn-success {
            background: #10b981;
            color: white;
        }
        .btn-success:hover {
            background: #059669;
        }
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        .btn-danger:hover {
            background: #dc2626;
        }

        /* Reservas específicas */
        .reservas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .reserva-card {
            background: white;
            border-radius: .75rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        .reserva-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }
        .reserva-card.aprovada {
            border-left: 4px solid #10b981;
        }
        .reserva-card.recusada {
            border-left: 4px solid #ef4444;
        }
        .reserva-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .reserva-title {
            font-weight: 600;
            color: #1f2937;
            font-size: 1.1rem;
        }
        .reserva-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .status-aprovada { background: #d1fae5; color: #065f46; }
        .status-recusada { background: #fee2e2; color: #991b1b; }
        .reserva-info {
            margin-bottom: 1rem;
        }
        .info-item {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: .5rem;
            color: #6b7280;
        }
        .info-item i { color: #7e22ce; width: 20px; }
        .reserva-actions {
            display: flex;
            gap: .5rem;
            margin-top: 1rem;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }

        /* Footer */
        .dashboard-footer { 
            background:white; 
            color:#6b7280; 
            text-align:center; 
            padding:1.5rem; 
            margin-top:3rem; 
            border-top:1px solid #e5e7eb; 
        }

        @media (max-width:768px) {
            .dashboard-header { flex-direction:column; padding:1.25rem; text-align:center; gap:1rem; }
            .user-info { flex-direction:column; gap:1rem; }
            .user-details { text-align:center; }
            .page-header { flex-direction: column; align-items: flex-start; }
            .page-actions { width: 100%; justify-content: center; }
            .reservas-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <!-- Cabeçalho -->
    <header class="dashboard-header">
        <div>
            <h2><i class="fas fa-building"></i> Gestão Condominial</h2>
            <div class="header-subtitle">Aprovação de Reservas</div>
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
            <a href="../../controller/AuthController.php?action=logout" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="dashboard-container">
        <!-- Cabeçalho da Página -->
        <div class="page-header">
            <h1><i class="fas fa-calendar-check"></i> Reservas Marcadas</h1>
            <div class="page-actions">
                <a href="#" class="btn btn-primary">
                    <i class="fas fa-sync-alt"></i> Atualizar
                </a>
                <a href="calendario.php" class="btn btn-success">
                    <i class="fas fa-calendar-alt"></i> Calendário
                </a>
            </div>
        </div>

        <!-- Cards de Reservas -->
        <div class="reservas-grid">
            <?php
            // Buscar todas as reservas reais do banco de dados
            $query = "SELECT r.id_reserva, r.area_comum, r.data, r.hora_inicio, r.hora_fim, 
                             m.nome as nome_morador, m.telefone
                      FROM Reserva r
                      INNER JOIN Morador m ON r.id_morador = m.id_morador
                      ORDER BY r.data DESC, r.hora_inicio DESC";
            
            $resultado = $conexao->query($query);

            if ($resultado && $resultado->num_rows > 0) {
                while ($reserva = $resultado->fetch_assoc()) {
                    $data_formatada = date('d/m/Y', strtotime($reserva['data']));
                    $hora_inicio = date('H:i', strtotime($reserva['hora_inicio']));
                    $hora_fim = date('H:i', strtotime($reserva['hora_fim']));
                    
                    echo "<div class='reserva-card'>";
                    echo "<div class='reserva-header'>";
                    echo "<div class='reserva-title'>" . htmlspecialchars($reserva['area_comum']) . "</div>";
                    echo "</div>";
                    echo "<div class='reserva-info'>";
                    echo "<div class='info-item'>";
                    echo "<i class='fas fa-user'></i>";
                    echo "<span><strong>Morador:</strong> " . htmlspecialchars($reserva['nome_morador']) . "</span>";
                    echo "</div>";
                    echo "<div class='info-item'>";
                    echo "<i class='fas fa-calendar'></i>";
                    echo "<span><strong>Data:</strong> " . $data_formatada . "</span>";
                    echo "</div>";
                    echo "<div class='info-item'>";
                    echo "<i class='fas fa-clock'></i>";
                    echo "<span><strong>Horário:</strong> " . $hora_inicio . " - " . $hora_fim . "</span>";
                    echo "</div>";
                    echo "<div class='info-item'>";
                    echo "<i class='fas fa-phone'></i>";
                    echo "<span><strong>Telefone:</strong> " . htmlspecialchars($reserva['telefone'] ?? 'N/A') . "</span>";
                    echo "</div>";
                    echo "</div>";
                    echo "<div class='reserva-actions'>";
                    echo "<button class='btn btn-primary btn-sm' onclick=\"verDetalhes(" . $reserva['id_reserva'] . ")\">";
                    echo "<i class='fas fa-eye'></i> Detalhes";
                    echo "</button>";
                    echo "</div>";
                    echo "</div>";
                }
            } else {
                echo "<div style='grid-column: 1/-1; text-align: center; padding: 40px; background: white; border-radius: .75rem;'>";
                echo "<i class='fas fa-inbox' style='font-size: 48px; color: #d1d5db; margin-bottom: 20px; display: block;'></i>";
                echo "<p style='color: #6b7280; font-size: 1.1rem;'>Nenhuma reserva cadastrada no sistema</p>";
                echo "</div>";
            }
            ?>
        </div>

        <!-- Estatísticas -->
        <div style="background: white; border-radius: .75rem; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h3><i class="fas fa-chart-bar"></i> Estatísticas de Reservas</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 1rem;">
                <?php
                // Calcular estatísticas
                $query_total = "SELECT COUNT(*) as total FROM Reserva";
                $resultado_total = $conexao->query($query_total);
                $total = $resultado_total->fetch_assoc()['total'];
                ?>
                <div style="text-align: center; padding: 1.5rem; background: #f8fafc; border-radius: .5rem;">
                    <div style="font-size: 2rem; color: #7e22ce; font-weight: 700;"><?php echo $total; ?></div>
                    <div style="color: #6b7280;">Total de Reservas</div>
                </div>
            </div>
        </div>
    </main>

    <footer class="dashboard-footer">
        <p>Sistema Condomínio Digital &copy; <?php echo date('Y'); ?></p>
        <p>Desenvolvido por Nelma Odair Bila</p>
    </footer>

    <script>
        function verDetalhes(id) {
            alert('Abrindo detalhes da reserva ' + id);
            // Aqui você redirecionaria para uma página de detalhes
        }
    </script>
</body>
</html>