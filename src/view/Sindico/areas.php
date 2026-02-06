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
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Áreas Comuns - Síndico</title>
    <link rel="stylesheet" href="../../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: #1f2937;
            min-height: 100vh;
        }

        .dashboard-header {
            background: white;
            color: #1f2937;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-bottom: 3px solid #7e22ce;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .dashboard-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .dashboard-header h2 i {
            color: #7e22ce;
        }

        .header-subtitle {
            font-size: .875rem;
            color: #6b7280;
            margin-top: .25rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: #7e22ce;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .user-details {
            text-align: right;
        }

        .user-name {
            font-weight: 500;
            font-size: 1rem;
            color: #1f2937;
        }

        .user-role {
            font-size: .75rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: .25rem;
            margin-top: .125rem;
        }

        .logout-btn,
        .back-btn {
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

        .logout-btn:hover,
        .back-btn:hover {
            background: #ff3742;
            transform: translateY(-2px);
        }

        .back-btn {
            background: #6c757d;
        }

        .back-btn:hover {
            background: #5a6268;
        }


        .dashboard-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1.25rem;
        }

        .page-header {
            background: white;
            border-radius: .75rem;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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

        .page-header h1 i {
            color: #7e22ce;
        }

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

        .areas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .area-card {
            background: white;
            border-radius: .75rem;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }

        .area-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .area-image {
            height: 200px;
            background: #7e22ce;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }

        .area-content {
            padding: 1.5rem;
        }

        .area-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: .5rem;
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .area-status {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .status-disponivel {
            background: #d1fae5;
            color: #065f46;
        }

        .status-manutencao {
            background: #fef3c7;
            color: #92400e;
        }

        .status-indisponivel {
            background: #fee2e2;
            color: #991b1b;
        }

        .area-info {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: .5rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .area-info i {
            color: #7e22ce;
            width: 20px;
        }

        .area-actions {
            display: flex;
            gap: .5rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
            flex: 1;
        }

        .calendar-card {
            background: white;
            border-radius: .75rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
        }

        .calendar-day {
            text-align: center;
            font-weight: 500;
            color: #6b7280;
            padding: .5rem;
        }

        .calendar-cell {
            height: 60px;
            border: 1px solid #e5e7eb;
            padding: .25rem;
            font-size: 0.875rem;
        }

        .calendar-cell.today {
            background: #7e22ce;
            color: white;
            font-weight: 600;
        }

        .calendar-cell.reserved {
            background: #fef3c7;
            border-color: #f59e0b;
        }

        .dashboard-footer {
            background: white;
            color: #6b7280;
            text-align: center;
            padding: 1.5rem;
            margin-top: 3rem;
            border-top: 1px solid #e5e7eb;
        }

        @media (max-width:768px) {
            .dashboard-header {
                flex-direction: column;
                padding: 1.25rem;
                text-align: center;
                gap: 1rem;
            }

            .user-info {
                flex-direction: column;
                gap: 1rem;
            }

            .user-details {
                text-align: center;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .page-actions {
                width: 100%;
                justify-content: center;
            }

            .areas-grid {
                grid-template-columns: 1fr;
            }

            .calendar-grid {
                font-size: 0.75rem;
            }
        }
    </style>
</head>

<body>

    <header class="dashboard-header">
        <div>
            <h2><i class="fas fa-building"></i> Gestão Condominial</h2>
            <div class="header-subtitle">Áreas Comuns</div>
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
            <a href="../../logout.php?logout=1"
                class="logout-btn"
                onclick="return confirmarSaida();">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>

        </div>
    </header>


    <main class="dashboard-container">
        <div class="page-header">
            <h1><i class="fas fa-swimming-pool"></i> Áreas Comuns</h1>
            <div class="page-actions">
                <a href="reservas.php" class="btn btn-primary">
                    <i class="fas fa-calendar-check"></i> Ver Reservas
                </a>
                <a href="#" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i> Nova Área
                </a>
            </div>
        </div>

        <div class="areas-grid">
            <div class="area-card">
                <div class="area-image" style="background: linear-gradient(135deg, #7e22ce 0%, #a855f7 100%);">
                    <i class="fas fa-glass-cheers"></i>
                </div>
                <div class="area-content">
                    <div class="area-title">
                        <i class="fas fa-glass-cheers"></i> Salão de Festas
                    </div>
                    <span class="area-status status-disponivel">Disponível</span>
                    <div class="area-info">
                        <i class="fas fa-users"></i>
                        <span>Capacidade: 80 pessoas</span>
                    </div>
                    <div class="area-info">
                        <i class="fas fa-clock"></i>
                        <span>Horário: 8h às 23h</span>
                    </div>
                    <div class="area-info">
                        <i class="fas fa-calendar"></i>
                        <span>Reservas hoje: 2</span>
                    </div>
                    <div class="area-actions">
                        <button class="btn btn-primary btn-sm" onclick="verReservas('salao')">
                            <i class="fas fa-calendar-alt"></i> Reservas
                        </button>
                        <button class="btn btn-success btn-sm" onclick="editarArea('salao')">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                    </div>
                </div>
            </div>

            <div class="area-card">
                <div class="area-image" style="background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);">
                    <i class="fas fa-fire"></i>
                </div>
                <div class="area-content">
                    <div class="area-title">
                        <i class="fas fa-fire"></i> Churrasqueira
                    </div>
                    <span class="area-status status-disponivel">Disponível</span>
                    <div class="area-info">
                        <i class="fas fa-users"></i>
                        <span>Capacidade: 20 pessoas</span>
                    </div>
                    <div class="area-info">
                        <i class="fas fa-clock"></i>
                        <span>Horário: 10h às 22h</span>
                    </div>
                    <div class="area-info">
                        <i class="fas fa-calendar"></i>
                        <span>Reservas hoje: 1</span>
                    </div>
                    <div class="area-actions">
                        <button class="btn btn-primary btn-sm" onclick="verReservas('churrasqueira')">
                            <i class="fas fa-calendar-alt"></i> Reservas
                        </button>
                        <button class="btn btn-success btn-sm" onclick="editarArea('churrasqueira')">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                    </div>
                </div>
            </div>

            <div class="area-card">
                <div class="area-image" style="background: linear-gradient(135deg, #06b6d4 0%, #22d3ee 100%);">
                    <i class="fas fa-swimming-pool"></i>
                </div>
                <div class="area-content">
                    <div class="area-title">
                        <i class="fas fa-swimming-pool"></i> Piscina
                    </div>
                    <span class="area-status status-manutencao">Em Manutenção</span>
                    <div class="area-info">
                        <i class="fas fa-users"></i>
                        <span>Capacidade: 30 pessoas</span>
                    </div>
                    <div class="area-info">
                        <i class="fas fa-clock"></i>
                        <span>Horário: 6h às 20h</span>
                    </div>
                    <div class="area-info">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Manutenção até: 20/03</span>
                    </div>
                    <div class="area-actions">
                        <button class="btn btn-warning btn-sm" onclick="verManutencao('piscina')">
                            <i class="fas fa-tools"></i> Manutenção
                        </button>
                        <button class="btn btn-success btn-sm" onclick="editarArea('piscina')">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                    </div>
                </div>
            </div>

            <div class="area-card">
                <div class="area-image" style="background: linear-gradient(135deg, #10b981 0%, #34d399 100%);">
                    <i class="fas fa-basketball-ball"></i>
                </div>
                <div class="area-content">
                    <div class="area-title">
                        <i class="fas fa-basketball-ball"></i> Quadra Poliesportiva
                    </div>
                    <span class="area-status status-disponivel">Disponível</span>
                    <div class="area-info">
                        <i class="fas fa-users"></i>
                        <span>Capacidade: 16 pessoas</span>
                    </div>
                    <div class="area-info">
                        <i class="fas fa-clock"></i>
                        <span>Horário: 6h às 22h</span>
                    </div>
                    <div class="area-info">
                        <i class="fas fa-calendar"></i>
                        <span>Reservas hoje: 3</span>
                    </div>
                    <div class="area-actions">
                        <button class="btn btn-primary btn-sm" onclick="verReservas('quadra')">
                            <i class="fas fa-calendar-alt"></i> Reservas
                        </button>
                        <button class="btn btn-success btn-sm" onclick="editarArea('quadra')">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                    </div>
                </div>
            </div>


            <div class="area-card">
                <div class="area-image" style="background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);">
                    <i class="fas fa-dumbbell"></i>
                </div>
                <div class="area-content">
                    <div class="area-title">
                        <i class="fas fa-dumbbell"></i> Ginásio
                    </div>
                    <span class="area-status status-disponivel">Disponível</span>
                    <div class="area-info">
                        <i class="fas fa-users"></i>
                        <span>Capacidade: 10 pessoas</span>
                    </div>
                    <div class="area-info">
                        <i class="fas fa-clock"></i>
                        <span>Horário: 5h às 23h</span>
                    </div>
                    <div class="area-info">
                        <i class="fas fa-calendar"></i>
                        <span>Acesso: Livre</span>
                    </div>
                    <div class="area-actions">
                        <button class="btn btn-primary btn-sm" onclick="verUso('academia')">
                            <i class="fas fa-chart-line"></i> Estatísticas
                        </button>
                        <button class="btn btn-success btn-sm" onclick="editarArea('academia')">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                    </div>
                </div>
            </div>

            <div class="area-card">
                <div class="area-image" style="background: linear-gradient(135deg, #8b5cf6 0%, #c4b5fd 100%);">
                    <i class="fas fa-child"></i>
                </div>
                <div class="area-content">
                    <div class="area-title">
                        <i class="fas fa-child"></i> Espaço Kids
                    </div>
                    <span class="area-status status-indisponivel">Indisponível</span>
                    <div class="area-info">
                        <i class="fas fa-users"></i>
                        <span>Capacidade: 15 crianças</span>
                    </div>
                    <div class="area-info">
                        <i class="fas fa-clock"></i>
                        <span>Horário: 9h às 19h</span>
                    </div>
                    <div class="area-info">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Reforma até: 25/03</span>
                    </div>
                    <div class="area-actions">
                        <button class="btn btn-danger btn-sm" onclick="verReforma('kids')">
                            <i class="fas fa-hard-hat"></i> Reforma
                        </button>
                        <button class="btn btn-success btn-sm" onclick="editarArea('kids')">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="calendar-card">
            <h3><i class="fas fa-calendar-alt"></i> Calendário de Reservas - Março 2026</h3>
            <div class="calendar-header">
                <button class="btn btn-sm"><i class="fas fa-chevron-left"></i></button>
                <span style="font-weight: 600;">Março 2024</span>
                <button class="btn btn-sm"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div class="calendar-grid">
                <div class="calendar-day">Dom</div>
                <div class="calendar-day">Seg</div>
                <div class="calendar-day">Ter</div>
                <div class="calendar-day">Qua</div>
                <div class="calendar-day">Qui</div>
                <div class="calendar-day">Sex</div>
                <div class="calendar-day">Sáb</div>

                <?php
                for ($i = 1; $i <= 31; $i++) {
                    $classes = 'calendar-cell';
                    if ($i == 15) $classes .= ' today';
                    if (in_array($i, [10, 15, 20, 25])) $classes .= ' reserved';
                    echo "<div class='$classes'>$i</div>";
                }
                ?>
            </div>
        </div>

        <div style="background: white; border-radius: .75rem; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h3><i class="fas fa-chart-pie"></i> Estatísticas das Áreas</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 1rem;">
                <div style="text-align: center; padding: 1.5rem; background: #f8fafc; border-radius: .5rem;">
                    <div style="font-size: 2rem; color: #7e22ce; font-weight: 700;">8</div>
                    <div style="color: #6b7280;">Áreas Comuns</div>
                </div>
                <div style="text-align: center; padding: 1.5rem; background: #f8fafc; border-radius: .5rem;">
                    <div style="font-size: 2rem; color: #10b981; font-weight: 700;">6</div>
                    <div style="color: #6b7280;">Disponíveis</div>
                </div>
                <div style="text-align: center; padding: 1.5rem; background: #f8fafc; border-radius: .5rem;">
                    <div style="font-size: 2rem; color: #f59e0b; font-weight: 700;">1</div>
                    <div style="color: #6b7280;">Em Manutenção</div>
                </div>
                <div style="text-align: center; padding: 1.5rem; background: #f8fafc; border-radius: .5rem;">
                    <div style="font-size: 2rem; color: #ef4444; font-weight: 700;">1</div>
                    <div style="color: #6b7280;">Indisponíveis</div>
                </div>
            </div>
        </div>
    </main>

    <footer class="dashboard-footer">
        <p>Sistema Condomínio Digital &copy; <?php echo date('Y'); ?></p>
        <p>Desenvolvido por Nelma Odair Bila</p>
    </footer>

    <script>
        function verReservas(area) {
            alert('Abrindo reservas da área: ' + area);

        }

        function editarArea(area) {
            alert('Editando área: ' + area);
        }

        function verManutencao(area) {
            alert('Ver detalhes da manutenção da: ' + area);
        }

        function verUso(area) {
            alert('Ver estatísticas de uso da: ' + area);
        }

        function verReforma(area) {
            alert('Ver detalhes da reforma do: ' + area);
        }

        document.querySelectorAll('.calendar-cell.reserved').forEach(cell => {
            cell.addEventListener('click', function() {
                alert('Dia ' + this.textContent + ' tem reservas agendadas!');
            });
        });

        function confirmarSaida() {
            return confirm("Tem a certeza que deseja sair?");
        }
    </script>
</body>
</html>