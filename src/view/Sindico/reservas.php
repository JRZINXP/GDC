<?php
require_once __DIR__ . '/../../data/conector.php';

session_start();


$conector = new Conector();
$conexao = $conector->getConexao();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {

    $id = $_POST['id_reserva'] ?? null;
    $acao = $_POST['acao'];

    if ($id) {

        if ($acao === 'aprovar') {
            $status = 'aprovada';
            registrarLog(
        $conexao,
        $_SESSION['id'],
        "Aprovou reserva ID $id");
        } else {
            $status = 'rejeitada';
            registrarLog(
    $conexao,
    $_SESSION['id'],
    "Cancelou reserva ID $id_reserva"
);

        }

        $stmtUp = $conexao->prepare("UPDATE Reserva SET status=? WHERE id_reserva=?");
        $stmtUp->bind_param("si", $status, $id);
        $stmtUp->execute();

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}


$stmt = $conexao->prepare("Select * from sindico Where id_usuario = ?");
$stmt->bind_param("s", $_SESSION['id']);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $row = $resultado->fetch_assoc();
    $userName = $row['nome'];
    $iniciais = strtoupper(substr($userName, 0, 1));
}
$reservasPorData = [];
$queryDatas = "
    SELECT 
        r.data, 
        r.area_comum, 
        r.hora_inicio, 
        r.hora_fim, 
        m.nome AS morador
    FROM Reserva r
    JOIN Morador m ON r.id_morador = m.id_morador
WHERE r.status = 'aprovada'


";


$resultDatas = $conexao->query($queryDatas);

if ($resultDatas) {
    while ($row = $resultDatas->fetch_assoc()) {
        $data = $row['data'];
        $reservasPorData[$data][] = [
            'area' => $row['area_comum'],
            'inicio' => substr($row['hora_inicio'], 0, 5),
            'fim' => substr($row['hora_fim'], 0, 5),
            'morador' => $row['morador']
        ];
    }
}
$reservas = [];

$query = "
SELECT r.id_reserva, r.area_comum, r.data, r.hora_inicio, r.hora_fim,
       r.status,
       m.nome AS nome_morador, m.telefone
FROM Reserva r
JOIN Morador m ON r.id_morador = m.id_morador
ORDER BY r.data DESC, r.hora_inicio ASC

";

$resultado = $conexao->query($query);

if ($resultado) {
    while ($row = $resultado->fetch_assoc()) {
        $reservas[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprovar Reservas - Síndico</title>
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

        .back-btn {
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
            background: #6c757d;
        }

        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
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

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .reservas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 380px));
            gap: 1.5rem;
            justify-content: start;
        }


        .reserva-card {
            background: linear-gradient(180deg, #ffffff, #f9fafb);
            border-radius: 14px;
            padding: 1.4rem 1.5rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .08);
            transition: transform .25s ease, box-shadow .25s ease;
            position: relative;

        }

        .reserva-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }

        .reserva-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 30px rgba(0, 0, 0, .15);
        }

        .area-badge {
            background: #ede9fe;
            color: #5b21b6;
            font-weight: 600;
            font-size: .85rem;
            padding: 6px 12px;
            border-radius: 999px;
            display: inline-block;
        }

        .reserva-header {
            margin-bottom: 1rem;
        }

        .reserva-info {
            display: flex;
            flex-direction: column;
            gap: .55rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: .6rem;
            font-size: .92rem;
            color: #374151;
        }

        .info-item i {
            width: 18px;
            color: #7e22ce;
        }

        .info-item i.fa-calendar {
            color: #6b7280;
        }

        .info-item i.fa-phone {
            color: #059669;
        }

        .reserva-actions {
            display: flex;
            gap: .5rem;
            margin-top: 1rem;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }

        .dashboard-footer {
            background: white;
            color: #6b7280;
            text-align: center;
            padding: 1.5rem;
            margin-top: 3rem;
            border-top: 1px solid #e5e7eb;
        }


        .calendar-header {
            display: grid;
            grid-template-columns: 40px 1fr 40px;
            align-items: center;
            margin-bottom: 15px;
        }

        .calendar-header button {
            width: 32px;
            height: 32px;
            background: #7e22ce;
            border: none;
            color: white;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
        }

        .calendar-header button:hover {
            background: #5b21b6;
        }


        .calendar-weekdays,
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px
        }

        .calendar-weekdays span {
            text-align: center;
            font-size: 13px;
            color: #6b7280
        }

        .calendar-layout {
            display: grid;
            grid-template-columns: 1fr 520px;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .calendar-container,
        .reserva-detalhes {
            background: white;
            border-radius: .75rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .day {
            height: 42px;
            border-radius: 8px;
            background: #f9fafb;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            position: relative;
        }

        .day.has-reserva::after {
            content: '';
            width: 6px;
            height: 6px;
            background: #7e22ce;
            border-radius: 50%;
            position: absolute;
            bottom: 6px;
        }

        .day:hover {
            background: #ede9fe
        }

        .day.has-reserva::after {
            content: '';
            width: 6px;
            height: 6px;
            background: #7e22ce;
            border-radius: 50%;
            position: absolute;
            bottom: 6px
        }

        #monthYear {
            text-align: center;
            font-weight: 600;
            font-size: 15px;
            color: #1f2937;
            text-transform: capitalize;
        }

        .reserva-actions form {
            display: inline;
        }

        .reserva-topo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .status-badge {
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 999px;
        }

        .reserva-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .reserva-actions form {
            display: inline;
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

            .reservas-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <header class="dashboard-header">
        <div>
            <h2><i class="fas fa-building"></i> Condominio Digital</h2>
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


        </div>
    </header>

    <main class="dashboard-container">

        <div class="page-header">
            <h1><i class="fas fa-calendar-check"></i> Reservas Marcadas</h1>
            <div class="page-actions">
                <a class="btn btn-success" onclick="document.getElementById('calendarDays').scrollIntoView({behavior:'smooth'})">
                    <i class="fas fa-calendar-alt"></i> Calendário
                </a>
            </div>
        </div>

        <div class="reservas-grid">

            <?php if (!empty($reservas)): ?>
                <?php foreach ($reservas as $reserva): ?>

                    <div class="reserva-card">

                        <div class="reserva-topo">
                            <span class="area-badge">
                                <?= htmlspecialchars($reserva['area_comum']) ?>
                            </span>

                            <?php
                            $status = $reserva['status'] ?? 'pendente';

                            if ($status == 'pendente') {
                                $cor = '#f59e0b';
                                $txt = 'Pendente';
                            } elseif ($status == 'aprovada') {
                                $cor = '#10b981';
                                $txt = 'Aprovada';
                            } else {
                                $cor = '#ef4444';
                                $txt = 'Rejeitada';
                            }
                            ?>

                            <span class="status-badge" style="background:<?= $cor ?>">
                                <?= $txt ?>
                            </span>
                        </div>

                        <div class="reserva-info">
                            <div class="info-item">
                                <i class="fas fa-user"></i>
                                <?= htmlspecialchars($reserva['nome_morador']) ?>
                            </div>

                            <div class="info-item">
                                <i class="fas fa-calendar"></i>
                                <?= date('d/m/Y', strtotime($reserva['data'])) ?>
                            </div>

                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <?= date('H:i', strtotime($reserva['hora_inicio'])) ?>
                                -
                                <?= date('H:i', strtotime($reserva['hora_fim'])) ?>
                            </div>

                            <div class="info-item">
                                <i class="fas fa-phone"></i>
                                <?= htmlspecialchars($reserva['telefone'] ?? 'N/A') ?>
                            </div>
                        </div>

                        <?php if ($status == 'pendente'): ?>
                            <div class="reserva-actions">

                                <form method="POST">
                                    <input type="hidden" name="id_reserva" value="<?= $reserva['id_reserva'] ?>">
                                    <input type="hidden" name="acao" value="aprovar">
                                    <button class="btn btn-success btn-sm">
                                        <i class="fas fa-check"></i> Aprovar
                                    </button>
                                </form>

                                <form method="POST">
                                    <input type="hidden" name="id_reserva" value="<?= $reserva['id_reserva'] ?>">
                                    <input type="hidden" name="acao" value="rejeitar">
                                    <button class="btn btn-danger btn-sm">
                                        <i class="fas fa-times"></i> Rejeitar
                                    </button>
                                </form>

                            </div>
                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <p style="grid-column:1/-1;color:#6b7280;text-align:center">
                    Nenhuma reserva cadastrada.
                </p>
            <?php endif; ?>

        </div>

        <div style="background: white; border-radius: .75rem; padding: 2rem; margin-bottom: 2rem; margin-top:2rem;box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h3><i class="fas fa-chart-bar"></i> Estatísticas de Reservas</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 1rem;">
                <?php
                $query_total = "
                    SELECT COUNT(*) as total 
                    FROM Reserva 
                    WHERE status='aprovada'";      

                $resultado_total = $conexao->query($query_total);
                $total = $resultado_total->fetch_assoc()['total'];

                ?>
                <div style="text-align: center; padding: 1.5rem; background: #f8fafc; border-radius: .5rem;">
                    <div style="font-size: 2rem; color: #7e22ce; font-weight: 700;"><?php echo $total; ?></div>
                    <div style="color: #6b7280;">Total de Reservas</div>
                </div>
            </div>
        </div>

        <div class="calendar-layout">

            <div id="infoReserva" class="reserva-detalhes">
                <p style="color:#6b7280">Selecione um dia no calendário</p>
            </div>

            <div class="calendar-container">
                <h3 style="text-align:center;color:#7e22ce">
                    <i class="fas fa-calendar-alt"></i> Calendário de Reservas
                </h3>

                <div class="calendar-header">
                    <button onclick="prevMonth()">‹</button>
                    <span id="monthYear"></span>
                    <button onclick="nextMonth()">›</button>
                </div>

                <div class="calendar-weekdays">
                    <span>Dom</span><span>Seg</span><span>Ter</span>
                    <span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span>
                </div>

                <div class="calendar-days" id="calendarDays"></div>
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
        }

        const reservasPorData = <?php echo json_encode($reservasPorData); ?>;

        let hoje = new Date();
        let mesAtual = hoje.getMonth();
        let anoAtual = hoje.getFullYear();

        const calendarDays = document.getElementById("calendarDays");
        const monthYear = document.getElementById("monthYear");
        const info = document.getElementById("infoReserva");

        function renderCalendar() {
            calendarDays.innerHTML = "";

            const primeiroDia = new Date(anoAtual, mesAtual, 1).getDay();
            const totalDias = new Date(anoAtual, mesAtual + 1, 0).getDate();

            monthYear.innerText = new Date(anoAtual, mesAtual)
                .toLocaleDateString("pt", {
                    month: "long",
                    year: "numeric"
                });

            for (let i = 0; i < primeiroDia; i++) {
                calendarDays.innerHTML += "<div></div>";
            }

            for (let dia = 1; dia <= totalDias; dia++) {
                const dataISO = `${anoAtual}-${String(mesAtual + 1).padStart(2,'0')}-${String(dia).padStart(2,'0')}`;
                const temReserva = reservasPorData[dataISO];

                const div = document.createElement("div");
                div.className = "day" + (temReserva ? " has-reserva" : "");
                div.innerText = dia;

                div.onclick = () => {
                    if (temReserva) {
                        let html = `<h4 style="color:#7e22ce">Reservas do dia ${dia}</h4>`;
                        reservasPorData[dataISO].forEach(r => {
                            html += `
                            <div style="
                            border:1px solid #e5e7eb;
                            border-radius:8px;
                            padding:12px;
                            margin-bottom:12px;
                            ackground:#fff
                            ">
                            <div><strong>Área:</strong> ${r.area}</div>
                            <div><strong>Morador:</strong> ${r.morador}</div>
                            <div><strong>Horário:</strong> ${r.inicio} - ${r.fim}</div>
                            </div>
                            `;
                        });

                        info.innerHTML = html;
                    } else {
                        info.innerHTML = "Nenhuma reserva neste dia";
                    }
                };

                calendarDays.appendChild(div);
            }
        }

        function prevMonth() {
            mesAtual--;
            if (mesAtual < 0) {
                mesAtual = 11;
                anoAtual--;
            }
            renderCalendar();
        }

        function nextMonth() {
            mesAtual++;
            if (mesAtual > 11) {
                mesAtual = 0;
                anoAtual++;
            }
            renderCalendar();
        }

        renderCalendar();
    </script>
</body>

</html>