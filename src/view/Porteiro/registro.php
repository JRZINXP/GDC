<?php
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'Porteiro') {
    header('Location: ../../login.php');
    exit();
}

require_once __DIR__ . '/../../data/conector.php';
$conexao = (new Conector())->getConexao();


$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
$base_url = $protocol . $_SERVER['HTTP_HOST'] . '/GDC';

$stmt = $conexao->prepare("
    SELECT 
        a.id_agendamento,
        a.motivo,
        DATE(a.data) AS data,
        a.hora AS hora_agendada,
        r.entrada,
        r.saida,
        v.nome AS visitante_nome,
        v.documento_imagem,
        u.numero AS casa_numero
    FROM Agendamento a
    JOIN Visitante v ON v.id_visitante = a.id_visitante
    JOIN Morador m ON m.id_morador = a.id_morador
    JOIN Unidade u ON u.id_unidade = m.id_unidade
    LEFT JOIN Registro r ON r.id_agendamento = a.id_agendamento
    WHERE r.saida IS NULL
    ORDER BY 
        r.entrada IS NULL DESC,
        a.data,
        a.hora
");
$stmt->execute();
$visitas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmtHist = $conexao->prepare("
    SELECT 
        v.nome AS visitante_nome,
        a.motivo,
        DATE(a.data) AS data,
        r.entrada,
        r.saida,
        u.numero AS casa_numero
    FROM Registro r
    JOIN Agendamento a ON a.id_agendamento = r.id_agendamento
    JOIN Visitante v ON v.id_visitante = a.id_visitante
    JOIN Morador m ON m.id_morador = a.id_morador
    JOIN Unidade u ON u.id_unidade = m.id_unidade
    WHERE r.saida IS NOT NULL
    ORDER BY r.saida DESC
");
$stmtHist->execute();
$historico = $stmtHist->get_result()->fetch_all(MYSQLI_ASSOC);


?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Portaria – Registro de Visitas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif
        }

        body {
            background: linear-gradient(180deg, #f4f6f9, #eef1f6);
            color: #1f2937
        }

        .header {
            background: #ffffff;
            padding: 22px 36px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 5px solid #4a148c;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .08)
        }

        .header h2 {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 22px;
            font-weight: 600
        }

        .header i {
            color: #4a148c
        }

        .container {
            max-width: 1350px;
            margin: 40px auto;
            padding: 0 24px
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 26px
        }

        .card {
            background: #ffffff;
            padding: 24px 26px;
            border-radius: 22px;
            box-shadow: 0 14px 35px rgba(0, 0, 0, .1);
            border-top: 6px solid #d1d5db;
            transition: .3s ease;
            position: relative;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 18px 45px rgba(0, 0, 0, .14);
        }

        .card.pendente {
            border-color: #f59e0b
        }

        .card.em {
            border-color: #7c3aed
        }

        .card.done {
            border-color: #10b981
        }

        .card h3 {
            font-size: 19px;
            font-weight: 600;
            margin-bottom: 4px
        }

        .house {
            display: inline-block;
            font-size: 13px;
            background: #f3f4f6;
            padding: 6px 14px;
            border-radius: 999px;
            margin-bottom: 14px;
            font-weight: 500
        }

        .info {
            background: #f9fafb;
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 14px
        }

        .info p {
            font-size: 14px;
            margin: 6px 0;
            color: #374151
        }

        .info strong {
            color: #111827
        }

        .doc-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #4a148c;
            font-weight: 600;
            text-decoration: none;
            margin-top: 4px
        }

        .doc-link i {
            background: #ede9fe;
            padding: 6px;
            border-radius: 50%;
        }

        .doc-link:hover {
            text-decoration: underline
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 16px
        }

        .badge.pendente {
            background: #fff7ed;
            color: #9a3412
        }

        .badge.em {
            background: #ede9fe;
            color: #4a148c
        }

        .badge.done {
            background: #ecfdf5;
            color: #065f46
        }

        button {
            width: 100%;
            padding: 15px;
            border-radius: 16px;
            border: none;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: .25s ease
        }

        .btn-in {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #ffffff
        }

        .btn-in:hover {
            filter: brightness(1.1)
        }

        .btn-out {
            background: linear-gradient(135deg, #7c3aed, #5b21b6);
            color: #ffffff
        }

        .btn-out:hover {
            filter: brightness(1.1)
        }

        .btn-off {
            background: #e5e7eb;
            color: #6b7280;
            cursor: not-allowed
        }

        .panel {
            background: #ffffff;
            padding: 22px 28px;
            border-radius: 20px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, .08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px
        }

        .panel-left h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 4px
        }

        .panel-left p {
            font-size: 14px;
            color: #6b7280
        }

        .panel-right {
            display: flex;
            gap: 10px
        }

        .legend {
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600
        }

        .legend.pendente {
            background: #fff7ed;
            color: #9a3412
        }

        .legend.em {
            background: #ede9fe;
            color: #4a148c
        }

        .legend.done {
            background: #ecfdf5;
            color: #065f46
        }

        .visit-date {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            background: #f3f4f6;
            padding: 6px 14px;
            border-radius: 999px;
            margin-bottom: 12px
        }

        .visit-date i {
            color: #4a148c;
        }

        .visit-date.today {
            background: #ede9fe;
            color: #4a148c;
        }
        .btn-historico{
    background:#4a148c;
    color:#fff;
    padding:10px 16px;
    border:none;
    border-radius:12px;
    cursor:pointer;
    font-size:13px;
    font-weight:600;
    display:flex;
    align-items:center;
    gap:8px;
    height:40px;
    width:auto;
    box-shadow:0 6px 16px rgba(0,0,0,.15);
    transition:.2s;
}

.btn-historico:hover{
    transform:translateY(-1px);
    filter:brightness(1.1);
}
.tabela-box{
    background:#fff;
    padding:24px;
    border-radius:22px;
    box-shadow:0 12px 30px rgba(0,0,0,.08);
    margin-top:40px;
}

.tabela-box h3{
    font-size:18px;
    margin-bottom:16px;
}

.tabela{
    width:100%;
    border-collapse:collapse;
}

.tabela thead{
    background:#4a148c;
    color:#fff;
}

.tabela th{
    text-align:left;
    padding:12px;
    font-weight:600;
    font-size:13px;
}

.tabela td{
    padding:12px;
    border-bottom:1px solid #eee;
    font-size:14px;
}

.icon{
    width:28px;
    height:28px;
    border-radius:8px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    margin-right:6px;
    font-size:12px;
}
        @media(max-width:600px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px
            }
        }
    </style>
</head>

<body>

    <header class="header">
        <h2><i class="fas fa-user-check"></i> Registro de Visitas</h2>
        <a href="index.php" style="text-decoration:none;color:#4a148c">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </header>

    <div class="container">

        <div class="panel">
            <div class="panel-left">
                <h3>Visitas Agendadas</h3>
                <p><?= date('d/m/Y') ?> • <?= strftime('%A') ?></p>
            </div>


            <div class="panel-right">
    <span class="legend pendente">Pendente</span>
    <span class="legend em">Em visita</span>
    <span class="legend done">Concluída</span>

    <button class="btn-historico" onclick="toggleTabela()">
        <i class="fas fa-table"></i> Ver visitas
    </button>
</div>



        </div>


        <div class="cards">
            <?php foreach ($visitas as $v):
                if (!$v['entrada']) {
                    $status = 'Pendente';
                    $cls = 'pendente';
                } elseif (!$v['saida']) {
                    $status = 'Em visita';
                    $cls = 'em';
                } else {
                    $status = 'Concluída';
                    $cls = 'done';
                }
            ?>
                <div class="card <?= $cls ?>">
                    <h3><?= htmlspecialchars($v['visitante_nome']) ?></h3>
                    <span class="house">Casa <?= $v['casa_numero'] ?></span>
                    <div class="visit-date <?= $v['data'] === date('Y-m-d') ? 'today' : '' ?>">
                        <i class="fas fa-calendar-day"></i>
                        <?= date('d/m/Y', strtotime($v['data'])) ?>
                    </div>

                    <div class="info">
                        <p><strong>Motivo:</strong> <?= htmlspecialchars($v['motivo']) ?></p>
                        <p>
                            <strong>Documento:</strong>
                            <a class="doc-link" href="../../<?= $v['documento_imagem'] ?>" target="_blank">
                                <i class="fas fa-file-alt"></i> Ver documento
                            </a>
                        </p>

                        <?php if ($v['entrada']): ?>
                            <p><strong>Entrada:</strong> <?= date('H:i:s', strtotime($v['entrada'])) ?></p>
                        <?php endif; ?>

                        <?php if ($v['saida']): ?>
                            <p><strong>Saída:</strong> <?= date('H:i:s', strtotime($v['saida'])) ?></p>
                        <?php endif; ?>
                    </div>
                    <span class="badge <?= $cls ?>"><?= $status ?></span>
                    <?php if (!$v['entrada']): ?>
                        <button class="btn-in"
                            onclick="acao('entrada', <?= $v['id_agendamento'] ?>)">
                            <i class="fas fa-sign-in-alt"></i> Registrar Entrada
                        </button>
                    <?php elseif (!$v['saida']): ?>
                        <button class="btn-out"
                            onclick="acao('saida', <?= $v['id_agendamento'] ?>)">
                            <i class="fas fa-sign-out-alt"></i> Registrar Saída
                        </button>
                    <?php else: ?>
                        <button class="btn-off" disabled>
                            <i class="fas fa-check"></i> Finalizado
                        </button>
                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        </div>
        <div id="tabelaHistorico" style="display:none;">

    <div class="tabela-box">
        <h3 style="margin-bottom:18px;display:flex;align-items:center;gap:10px">
<i class="fas fa-clock" style="color:#4a148c"></i>
Histórico de visitas
</h3>

        <table class="tabela">
            <thead>
                <tr>
                    <th style="padding:12px;text-align:left">Visitante</th>
                    <th>Casa</th>
                    <th>Motivo</th>
                    <th>Data</th>
                    <th>Entrada</th>
                    <th>Saída</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach($historico as $h): ?>
                <tr style="border-bottom:1px solid #eee">
                    <td style="padding:10px"><?= htmlspecialchars($h['visitante_nome']) ?></td>
                    <td><?= $h['casa_numero'] ?></td>
                    <td><?= htmlspecialchars($h['motivo']) ?></td>
                    <td><?= date('d/m/Y', strtotime($h['data'])) ?></td>
                    <td><?= date('H:i', strtotime($h['entrada'])) ?></td>
                    <td><?= date('H:i', strtotime($h['saida'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>

    </div>

    <script>

function toggleTabela(){
    const tabela = document.getElementById("tabelaHistorico");
    tabela.style.display = tabela.style.display === "none" ? "block" : "none";
}



        function acao(tipo, id) {
            fetch('<?= $base_url ?>/src/controller/Porteiro/registro.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'acao=' + tipo + '&id_agendamento=' + id
                })
                .then(r => r.json())
                .then(d => d.success ? location.reload() : alert(d.message));
        }
    </script>

<script src="../../../assets/js/auto-logout.js"></script>

</body>

</html>