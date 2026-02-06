<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../data/conector.php';

if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'Morador') {
    header('Location: ../../login.php');
    exit;
}

$conexao = (new Conector())->getConexao();

$stmt = $conexao->prepare(
    "SELECT id_morador, nome FROM Morador WHERE id_usuario = ?"
);
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$morador = $stmt->get_result()->fetch_assoc();

if (!$morador) {
    session_destroy();
    header("Location: ../../login.php");
    exit;
}

$idMorador   = $morador['id_morador'];
$nomeMorador = $morador['nome'];
$iniciais    = strtoupper(substr($nomeMorador, 0, 1));

$stmt = $conexao->prepare("
   SELECT 
    a.id_agendamento,
    a.data,
    a.hora,
    a.motivo,
    v.nome AS visitante,
    v.documento_imagem,
    r.entrada,
    r.saida
    FROM Agendamento a
    JOIN Visitante v ON v.id_visitante = a.id_visitante
    LEFT JOIN Registro r ON r.id_agendamento = a.id_agendamento
    WHERE a.id_morador = ?
    ORDER BY a.data DESC, a.hora DESC
");

$stmt->bind_param("i", $idMorador);
$stmt->execute();
$visitas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Minhas Visitas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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
            background: #f4f6f9;
            color: #1f2937;
        }

        /* ===== LAYOUT ===== */
        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 240px;
            background: #9743d7;
            color: #ffffff;
            padding: 25px 20px;
            display: flex;
            flex-direction: column;
        }

        /* TÍTULO */
        .sidebar h2 {
            font-size: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #fff;
        }

        /* NAV */
        .sidebar nav {
            display: flex;
            flex-direction: column;
        }

        /* LINKS */
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
            /* IMPORTANTE */
        }

        /* ÍCONES */
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

        /* ÍCONE DO ATIVO */
        .sidebar nav a.active i {
            color: #9743d7 !important;
        }

        /* LOGOUT */
        .sidebar .logout {
            margin-top: auto;
            background: rgba(0, 0, 0, 0.25);
        }

        /* ===== CONTEÚDO ===== */
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
            background: #fff;
            padding: 30px;
            border-radius: 16px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #a855f7, #9333ea);
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 2rem;
            transition: all .25s ease;
            box-shadow: 0 4px 12px rgba(147, 51, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(147, 51, 234, 0.45);
        }

        .btn-primary:active {
            transform: scale(.97);
        }


        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        th,
        td {
            padding: 14px;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #f3f4f6;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .55);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal {
            background: #fff;
            width: 520px;
            max-width: 92%;
            max-height: 90vh;
            padding: 24px;
            border-radius: 20px;
        }

        .modal h3 {
            grid-column: span 2;
            margin-bottom: 12px;
        }

        .modal form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-actions {
            grid-column: span 2;
            display: flex;
            justify-content: flex-end;
            gap: 14px;
            margin-top: 10px;
        }

        input,
        select {
            padding: 10px;
            border-radius: 10px;
            border: 1px solid #d1d5db;
            font-size: 13px;
            background: #f9fafb;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #9743d7;
            background: #fff;
        }

        .form-group[style*="display:none"] {
            display: none;
        }

        .form-actions button[type="button"] {
            background: #f1f5f9;
            color: #374151;
            border: 1px solid #d1d5db;
            padding: 10px 18px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 13px;
            transition: .2s;
        }

        .form-actions button[type="button"]:hover {
            background: #e5e7eb;
            border-color: #9ca3af;
        }

        .form-actions button[type="button"]:active {
            transform: scale(.97);
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .card {
            background: #ffffff;
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .08);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .card h4 {
            font-size: 16px;
            font-weight: 600;
        }

        .card .meta {
            font-size: 13px;
            color: #6b7280;
        }

        .card .motivo {
            font-size: 14px;
            font-weight: 500;
        }

        .card .acoes {
            margin-top: auto;
            display: flex;
            gap: 10px;
        }

        .btn-cancelar {
            background: #fee2e2;
            color: #991b1b;
            border: none;
            padding: 8px 10px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
        }

        .btn-editar {
            background: #ede9fe;
            color: #4c1d95;
            border: none;
            padding: 8px 10px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
        }

        .status {
            align-self: flex-start;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-agendada {
            background: #fff3cd;
            color: #856404;
        }

        .status-em {
            background: #ede9fe;
            color: #4a148c;
        }

        .status-done {
            background: #dcfce7;
            color: #166534;
        }

        .status-cancelada {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-cancelar i,
        .btn-editar i {
            margin-right: 6px;
        }


        @media (max-width: 600px) {
            .modal form {
                grid-template-columns: 1fr;
            }

            .modal h3,
            .form-actions {
                grid-column: span 1;
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
                <a href="agendar_visita.php" class="active"><i class="fas fa-users"></i> Visitas</a>
                <a href="reservas.php"><i class="fas fa-calendar-check"></i> Reservas</a>
                <a href="encomendas.php"><i class="fas fa-box"></i> Encomendas</a>
                <a href="avisos.php"><i class="fas fa-bullhorn"></i> Avisos</a>
                <a href="ocorrencias.php"><i class="fas fa-exclamation-triangle"></i> Ocorrências</a>
                <a href="../../logout.php?logout=1" class="logout"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </nav>
        </aside>

        <main class="content">

            <header class="dashboard-header">
                <h2><i class="fas fa-users"></i> Minhas Visitas</h2>
                <div class="user-info">
                    <div class="user-avatar"><?= $iniciais ?></div>

                    <strong><?= htmlspecialchars($nomeMorador) ?></strong>
                </div>
            </header>
            <button class="btn-primary" onclick="abrirModal()">
                <i class="fas fa-plus"></i> Agendar visita
            </button>


            <?php if (empty($visitas)): ?>
                <p style="margin-top:25px;color:#6b7280">Nenhuma visita agendada.</p>
            <?php else: ?>
                <?php if (isset($_SESSION['erro'])): ?>
                    <div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:10px;margin-bottom:20px">
                        <?= $_SESSION['erro'];
                        unset($_SESSION['erro']); ?>
                    </div>
                <?php endif; ?>



                <div class="cards">

                    <?php foreach ($visitas as $v):

                        if (!$v['entrada']) {
                            $statusTxt = 'Agendada';
                            $statusCls = 'status-agendada';
                            $podeAlterar = true;
                        } elseif (!$v['saida']) {
                            $statusTxt = 'Em visita';
                            $statusCls = 'status-em';
                            $podeAlterar = false;
                        } else {
                            $statusTxt = 'Concluída';
                            $statusCls = 'status-done';
                            $podeAlterar = false;
                        }

                    ?>

                        <div class="card">
                            <span class="status <?= $statusCls ?>"><?= $statusTxt ?></span>

                            <h4><?= htmlspecialchars($v['visitante']) ?></h4>

                            <div class="meta">
                                <?= date('d/m/Y', strtotime($v['data'])) ?> às <?= date('H:i', strtotime($v['hora'])) ?>
                            </div>

                            <div class="motivo">
                                <?= htmlspecialchars($v['motivo']) ?>
                            </div>

                            <a href="../../<?= $v['documento_imagem'] ?>" target="_blank">📄 Ver documento</a>

                            <?php if ($podeAlterar): ?>
                                <div class="acoes">
                                    <form method="post" action="../../controller/Morador/visitas.php">
                                        <input type="hidden" name="acao" value="cancelar">
                                        <input type="hidden" name="id_agendamento" value="<?= $v['id_agendamento'] ?>">
                                        <button class="btn-cancelar" type="submit">
                                            <i class="fas fa-times-circle"></i> Cancelar
                                        </button>

                                    </form>
                                    <button class="btn-editar"
                                        onclick="editarVisita(
<?= $v['id_agendamento'] ?>,
'<?= htmlspecialchars($v['visitante'], ENT_QUOTES) ?>',
'<?= $v['data'] ?>',
'<?= $v['hora'] ?>',
'<?= htmlspecialchars($v['motivo'], ENT_QUOTES) ?>'
)">
                                        <i class="fas fa-pen"></i> Editar
                                    </button>

                                </div>
                            <?php endif; ?>
                        </div>

                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

        </main>
    </div>

    <div class="modal-overlay" id="modal">
        <div class="modal">
            <h3>Agendar Visita</h3>
            <form action="../../controller/Morador/visitas.php" method="POST" enctype="multipart/form-data">

                <div class="form-group">
                    <label>Nome do Visitante</label>
                    <input type="text" name="nome_visitante" required>
                </div>
                <div class="form-group">
                    <label>Documento (imagem)</label>
                    <input type="file" name="documento_imagem" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label>Motivo da visita</label>
                    <select id="motivo" name="motivo" required>
                        <option value="">Selecione o motivo</option>
                        <option value="visita_social">Visita social</option>
                        <option value="prestacao_servico">Prestação de serviços</option>
                        <option value="administrativo">Assuntos administrativos</option>
                        <option value="evento">Evento autorizado</option>
                    </select>
                </div>
                <div class="form-group" id="social" style="display:none;">
                    <label>Tipo de visita</label>
                    <select name="social">
                        <option value="">Tipo de visita</option>
                        <option value="familiar">Familiar</option>
                        <option value="amigo">Amigo</option>
                    </select>
                </div>

                <div class="form-group" id="servico" style="display:none;">
                    <label>Tipo de serviço</label>
                    <select name="servico">
                        <option value="">Tipo de serviço</option>
                        <option value="eletrica">Manutenção elétrica</option>
                        <option value="hidraulica">Manutenção hidráulica</option>
                        <option value="gas">Gás</option>
                        <option value="ar_condicionado">Ar-condicionado</option>
                        <option value="internet">Internet / TV</option>
                        <option value="portao_eletronico">Portão eletrónico</option>
                        <option value="interfone">Interfone</option>
                        <option value="limpeza">Limpeza</option>
                        <option value="jardinagem">Jardinagem</option>
                        <option value="pintura">Pintura</option>
                        <option value="mudanca">Mudança</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>

                <div class="form-group" id="administrativo" style="display:none;">
                    <label>Tipo administrativo</label>
                    <select name="administrativo">
                        <option value="">Tipo administrativo</option>
                        <option value="reuniao">Reunião</option>
                        <option value="vistoria">Vistoria</option>
                        <option value="atendimento">Atendimento</option>
                    </select>
                </div>

                <div class="form-group" id="evento" style="display:none;">
                    <label>Tipo de evento</label>
                    <select name="evento">
                        <option value="">Tipo de evento</option>
                        <option value="festa">Festa</option>
                        <option value="reuniao">Reunião</option>
                        <option value="celebracao">Celebração</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Data</label>
                    <input type="date" name="data" required min="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Hora</label>
                    <input type="time" name="hora" required>
                </div>
                <div class="form-actions">
                    <button type="button" onclick="fecharModal()">Cancelar</button>
                    <button class="btn-primary" type="submit">Agendar</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function abrirModal() {
            document.getElementById('modal').style.display = 'flex';
        }

        function fecharModal() {
            document.getElementById('modal').style.display = 'none';
        }
        const motivoSelect = document.getElementById('motivo');

        const grupos = {
            visita_social: document.getElementById('social'),
            prestacao_servico: document.getElementById('servico'),
            administrativo: document.getElementById('administrativo'),
            evento: document.getElementById('evento')
        };

        motivoSelect.addEventListener('change', function() {
            Object.values(grupos).forEach(div => div.style.display = 'none');
            if (grupos[this.value]) {
                grupos[this.value].style.display = 'block';
            }
        });

        function editarVisita(id, data, hora, motivo) {

            abrirModal();

            document.querySelector(".modal h3").innerText = "Editar Visita";

            // criar campo hidden id
            let idInput = document.getElementById("id_edit");
            if (!idInput) {
                idInput = document.createElement("input");
                idInput.type = "hidden";
                idInput.name = "id_agendamento";
                idInput.id = "id_edit";
                document.querySelector(".modal form").appendChild(idInput);
            }
            idInput.value = id;

            // criar campo ação
            let acaoInput = document.getElementById("acao_edit");
            if (!acaoInput) {
                acaoInput = document.createElement("input");
                acaoInput.type = "hidden";
                acaoInput.name = "acao";
                acaoInput.id = "acao_edit";
                document.querySelector(".modal form").appendChild(acaoInput);
            }
            acaoInput.value = "editar";

            // preencher campos
            document.querySelector("input[name=data]").value = data;
            document.querySelector("input[name=hora]").value = hora;
            document.querySelector("select[name=motivo]").value = "";
        }
    </script>


<script src="../../../assets/js/auto-logout.js"></script>

</body>

</html>