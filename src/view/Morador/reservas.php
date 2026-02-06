<?php
require_once __DIR__ . '/../../data/conector.php';

session_start();

$conector = new Conector();
$conexao = $conector->getConexao();

$stmt = $conexao->prepare("SELECT id_morador, nome FROM Morador WHERE id_usuario = ?");
$stmt->bind_param("s", $_SESSION['id']);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $morador = $resultado->fetch_assoc();
    $id_morador = $morador['id_morador'];
    $nome_morador = $morador['nome'];
    $iniciais = strtoupper(substr($nome_morador, 0, 1));
} else {
    header("Location: ../../login.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_reserva'])) {

    $id_reserva = $_POST['id_reserva'];

    $stmtCancel = $conexao->prepare("
        UPDATE Reserva 
        SET status='cancelada' 
        WHERE id_reserva=? AND id_morador=?
    ");

    $stmtCancel->bind_param("ii", $id_reserva, $id_morador);
    $stmtCancel->execute();

    $_SESSION['mensagem'] = "Reserva cancelada com sucesso!";
    $_SESSION['tipo_mensagem'] = "sucesso";

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

date_default_timezone_set('Africa/Maputo');

?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Reservas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/colors.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-size: cover;
            min-height: 100vh;
        }

        .dashboard-header {
            background: rgba(255, 255, 255, 0.5);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-bottom: 3px solid #9743d7;
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

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .card h3 {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
        }

        .card h3 i {
            color: #9743d7;
        }

        .form-group {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #9743d7;
            box-shadow: 0 0 0 3px rgba(155, 67, 215, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn-submit {
            background: #9743d7;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            font-size: 15px;
        }

        .btn-submit:hover {
            background: #7e22ce;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(155, 67, 215, 0.4);
        }

        .reservas-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .reserva-item {
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #9743d7;
        }

        .reserva-item h4 {
            color: #333;
            margin-bottom: 8px;
        }

        .reserva-info {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .reserva-info i {
            color: #9743d7;
            width: 20px;
            text-align: center;
        }

        .alert-success,
        .alert-error {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideDown 0.4s ease-out;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border-left: 4px solid #ef4444;
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
            color: #fff;
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

        .sidebar nav a i {
            color: #ffffff;
        }

        .sidebar nav a:hover:not(.active) {
            background: rgba(255, 255, 255, 0.18);
        }

        .sidebar nav a.active {
            background: #ffffff !important;
            color: #9743d7 !important;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .sidebar nav a.active i {
            color: #9743d7 !important;
        }

        .sidebar .logout {
            margin-top: auto;
            background: rgba(0, 0, 0, 0.25);
        }

        .content {
            flex: 1;
            padding: 40px;
            background: #f4f6f9;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .content-wrapper {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .dashboard-header {
                flex-direction: column;
                gap: 15px;
            }
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
                <a href="reservas.php" class="active"><i class="fas fa-calendar-check"></i> Reservas</a>
                <a href="encomendas.php"><i class="fas fa-box"></i> Encomendas</a>
                <a href="avisos.php"><i class="fas fa-bullhorn"></i> Avisos</a>
                <a href="ocorrencias.php"><i class="fas fa-exclamation-triangle"></i> Ocorrências</a>
                <a href="../../logout.php?logout=1"
                    class="logout"
                    onclick="return confirmarSaida();">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>

            </nav>
        </aside>

        <main class="content">

            <header class="dashboard-header">
                <div class="header-left">
                    <h2>
                        <i class="fas fa-calendar-check"></i> Reservas
                    </h2>
                    <div class="header-subtitle">
                        Gerenciar minhas reservas de áreas comuns
                    </div>
                </div>

                <div class="user-info">
                    <div class="user-avatar"><?= $iniciais ?></div>
                    <strong><?= htmlspecialchars($nome_morador) ?></strong>
                </div>
            </header>

            <div class="container">
                <div class="content-wrapper">

                    <div class="card">
                        <h3>
                            <i class="fas fa-plus-circle"></i>
                            Nova Reserva
                        </h3>

                        <?php
                        if (isset($_SESSION['mensagem'])) {
                            $tipo = $_SESSION['tipo_mensagem'] === 'sucesso'
                                ? 'alert-success'
                                : 'alert-error';

                            echo "<div class='$tipo'>{$_SESSION['mensagem']}</div>";
                            unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']);
                        }
                        ?>

                        <form action="../../controller/Morador/reservas.php" method="POST" id="form-reserva">

                            <div class="form-group">
                                <label><i class="fas fa-door-open"></i> Área Comum</label>
                                <select name="area_comum" required>
                                    <option value="">-- Selecione --</option>
                                    <option>Salão de Festas</option>
                                    <option>Piscina</option>
                                    <option>Churrasqueira</option>
                                    <option>Campo Polidesportivo</option>
                                    <option>Sala de Reunião</option>
                                    <option>Sauna</option>
                                    <option>Ginásio</option>
                                    <option>Outro</option>
                                </select>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-calendar"></i> Data</label>
                                    <input type="date" name="data" id="data" required>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-clock"></i> Hora Início</label>
                                    <input type="time" name="hora_inicio" id="hora_inicio">
                                </div>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-clock"></i> Hora Fim</label>
                                <input type="time" name="hora_fim" id="hora_fim" required>
                            </div>

                            <button type="submit" class="btn-submit">
                                <i class="fas fa-save"></i> Reservar Agora
                            </button>
                        </form>
                    </div>

                    <div class="card">
                        <h3>
                            <i class="fas fa-list"></i>
                            Minhas Reservas
                        </h3>

                        <div class="reservas-list">
                            <?php
                            $stmt = $conexao->prepare("
SELECT id_reserva, area_comum, data, hora_inicio, hora_fim, status
FROM Reserva
WHERE id_morador = ?
ORDER BY data DESC

");
                            $stmt->bind_param("i", $id_morador);
                            $stmt->execute();
                            $res = $stmt->get_result();

                            if ($res->num_rows > 0):
                                while ($r = $res->fetch_assoc()):

    $status = $r['status'] ?? 'pendente';

    if ($status == 'pendente') {
        $cor = '#f59e0b';
        $txt = 'Pendente de aprovação';
    } elseif ($status == 'aprovada') {
        $cor = '#10b981';
        $txt = 'Aprovada';
    } elseif ($status == 'cancelada') {
        $cor = '#6b7280';
        $txt = 'Cancelada';
    } else {
        $cor = '#ef4444';
        $txt = 'Rejeitada';
    }
?>

<div class="reserva-item">

    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h4><?= htmlspecialchars($r['area_comum']) ?></h4>

        <span style="
            font-size:12px;
            font-weight:600;
            color:#fff;
            background:<?= $cor ?>;
            padding:4px 10px;
            border-radius:999px">
            <?= $txt ?>
        </span>
    </div>

    <div class="reserva-info">
        <i class="fas fa-calendar-alt"></i>
        <?= date('d/m/Y', strtotime($r['data'])) ?>
    </div>

    <div class="reserva-info">
        <i class="fas fa-clock"></i>
        <?= date('H:i', strtotime($r['hora_inicio'])) ?>
        às
        <?= date('H:i', strtotime($r['hora_fim'])) ?>
    </div>

    <?php if ($status == 'aprovada'): ?>
        <form method="POST" onsubmit="return confirmarCancelamento();">
            <input type="hidden" name="id_reserva" value="<?= $r['id_reserva'] ?>">
            <button name="cancelar_reserva"
                style="
                margin-top:10px;
                background:#ef4444;
                color:white;
                border:none;
                padding:6px 12px;
                border-radius:6px;
                cursor:pointer;">
                <i class="fas fa-times"></i> Cancelar reserva
            </button>
        </form>
    <?php endif; ?>

</div>

<?php endwhile; 

                            else:
                                echo "<p style='color:#777'>Nenhuma reserva encontrada</p>";
                            endif;
                            ?>
                        </div>

                    </div>

                </div>
            </div>

        </main>
    </div>

    <script>
        document.getElementById('data').addEventListener('change', function() {
            const data = new Date(this.value);
            const hoje = new Date();
            hoje.setHours(0, 0, 0, 0);

            if (data < hoje) {
                alert('Não é possível reservar para datas passadas!');
                this.value = '';
            }
        });

        document.getElementById('form-reserva').addEventListener('submit', function(e) {
            const horaInicio = document.getElementById('hora_inicio').value;
            const horaFim = document.getElementById('hora_fim').value;

            if (horaFim <= horaInicio) {
                e.preventDefault();
                alert('A hora de término deve ser maior que a hora de início!');
            }
        });

        const alerta = document.querySelector('.alert-success');
        if (alerta) {
            setTimeout(function() {
                alerta.style.opacity = '0';
                alerta.style.transition = 'opacity 0.4s ease-out';
                setTimeout(function() {
                    alerta.style.display = 'none';
                }, 400);
            }, 3000);
        }

        function confirmarSaida() {
            return confirm("Tem a certeza que deseja sair?");
        }

        function confirmarCancelamento() {
            return confirm("Tens certeza que queres cancelar esta reserva?");
        }
    </script>
</body>

</html>