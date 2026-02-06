<?php
session_start();
require_once __DIR__ . '/../../data/conector.php';

if (!isset($_SESSION['id']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'Morador') {
    header('Location: ../../login.php');
    exit();
}


$conexao = (new Conector())->getConexao();

/* Buscar morador */
$stmt = $conexao->prepare("SELECT id_morador, nome FROM Morador WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$morador = $stmt->get_result()->fetch_assoc();

$idMorador = $morador['id_morador'];
$userName  = $morador['nome'];
$iniciais  = strtoupper(substr($userName, 0, 1));

$stmt = $conexao->prepare("
    SELECT 
        e.id_entrega,
        e.descricao,
        e.data_recepcao,
        e.data_entrega,
        e.status
    FROM Entrega e
    WHERE e.id_morador = ?
    ORDER BY e.data_recepcao DESC
");

$stmt->bind_param("i", $idMorador);
$stmt->execute();
$encomendas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Minhas Encomendas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif
        }

        :root {
            --primary: #9743d7;
            --primary-dark: #7e22ce;
            --bg: #f4f6fb;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #e5e7eb;
            --shadow: 0 10px 25px rgba(0, 0, 0, .08);
            --success: #10b981;
            --warning: #f59e0b;
        }

        body {
            background: var(--bg);
            color: var(--text);
        }

        /* HEADER */
        .header {
            background: var(--card);
            padding: 20px 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 4px solid var(--primary);
            box-shadow: var(--shadow);
        }

        .header h2 {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header i {
            color: var(--primary)
        }

        .user {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        /* CONTAINER */
        .container {
            max-width: 1200px;
            margin: 35px auto;
            padding: 0 20px;
        }

        /* INFO */
        .info-box {
            background: #eef2ff;
            color: #3730a3;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        /* GRID */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        /* CARD */
        .card {
            background: var(--card);
            border-radius: 16px;
            padding: 22px;
            box-shadow: var(--shadow);
            border-left: 6px solid var(--primary);
            transition: .3s;
        }

        .card:hover {
            transform: translateY(-6px);
        }

        .card h4 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .card i {
            color: var(--primary)
        }

        .meta {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            margin-top: 10px;
        }

        .status.pendente {
            background: #fff7ed;
            color: #9a3412;
        }

        .status.entregue {
            background: #ecfdf5;
            color: #065f46;
        }

        /* EMPTY */
        .empty {
            grid-column: 1/-1;
            text-align: center;
            background: var(--card);
            padding: 50px;
            border-radius: 16px;
            box-shadow: var(--shadow);
            color: var(--muted);
        }

        .empty i {
            font-size: 48px;
            margin-bottom: 15px;
            color: var(--primary);
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

        /* HOVER (somente quando NÃO ativo) */
        .sidebar nav a:hover:not(.active) {
            background: rgba(255, 255, 255, 0.18);
        }

        /* ===== ITEM ATIVO — BRANCO REAL ===== */
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
        /* ===== HEADER ===== */
.dashboard-header {
    background: #ffffff;
    padding: 20px 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 3px solid #9743d7;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    margin-bottom: 30px;
}

/* ESQUERDA */
.header-left h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 22px;
    font-weight: 600;
    color: #1f2937;
}

.header-left h2 i {
    color: #9743d7;
    font-size: 22px;
}

.header-subtitle {
    font-size: 14px;
    color: #6b7280;
    margin-top: 4px;
}

/* DIREITA */
.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #1f2937;
    font-weight: 500;
}

/* AVATAR */
.user-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #9743d7;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
}

/* RESPONSIVO */
@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .user-info {
        align-self: flex-end;
    }
}


        /* RESPONSIVO */
        @media(max-width:768px) {
            .header {
                flex-direction: column;
                gap: 15px
            }
        }
    </style>
</head>

<body>

    <div class="layout">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <h2>
                <i class="fas fa-home"></i>
                Morador
            </h2>

            <nav>
                <a href="index.php">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>

                <a href="agendar_visita.php">
                    <i class="fas fa-users"></i> Visitas
                </a>

                <a href="reservas.php">
                    <i class="fas fa-calendar-check"></i> Reservas
                </a>

                <a href="encomendas.php" class="active" style="background: rgba(255,255,255,0.2);">
                    <i class="fas fa-box"></i> Encomendas
                </a>

                <a href="avisos.php">
                    <i class="fas fa-bullhorn"></i> Avisos
                </a>

                <a href="ocorrencias.php">
                    <i class="fas fa-exclamation-triangle"></i> Ocorrências
                </a>

<a href="../../logout.php?logout=1" 
   class="logout" 
   onclick="return confirmarSaida();">
    <i class="fas fa-sign-out-alt"></i> Sair
</a>

            </nav>
        </aside>

        <!-- CONTEÚDO -->
        <main class="content">

            <!-- HEADER -->
            <header class="dashboard-header">
                <div class="header-left">
                    <h2>
                        <i class="fas fa-box-open"></i>
                        </i> Encomendas
                    </h2>
                    <div class="header-subtitle">
                        Minhas encomendas
                    </div>
                </div>

                <div class="user-info">
                    <div class="user-avatar"><?= $iniciais ?></div>
                    <strong><?= htmlspecialchars($userName) ?></strong>
                </div>
            </header>

            <div class="container">
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <span>
                        As encomendas são registradas pelo porteiro quando chegam à portaria.
                    </span>
                </div>
                <?php if (empty($encomendas)): ?>

    <div class="empty">
        <i class="fas fa-box-open"></i>
        <h3>Nenhuma encomenda registrada</h3>
        <p>Quando uma encomenda chegar, ela aparecerá aqui.</p>
    </div>

<?php else: ?>

<table style="width:100%;border-collapse:collapse;background:#fff">
    <thead>
        <tr style="background:#f3f4f6">
            <th style="padding:14px;text-align:left">Descrição</th>
            <th>Recebida em</th>
            <th>Entregue em</th>
            <th>Status</th>
        </tr>
    </thead>

    <tbody>
    <?php foreach ($encomendas as $e):
        $status = $e['status'] == 1 ? 'entregue' : 'pendente';
    ?>
        <tr style="border-bottom:1px solid #e5e7eb">
            <td style="padding:14px">
                <?= htmlspecialchars($e['descricao']) ?>
            </td>

            <td>
                <?= date('d/m/Y H:i', strtotime($e['data_recepcao'])) ?>
            </td>

            <td>
                <?= !empty($e['data_entrega'])
                    ? date('d/m/Y H:i', strtotime($e['data_entrega']))
                    : '--'
                ?>
            </td>

            <td>
                <span class="status <?= $status ?>">
                    <i class="fas <?= $status === 'entregue' ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                    <?= $status === 'entregue'
                        ? 'Entregue ao morador'
                        : 'Aguardando retirada'
                    ?>
                </span>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php endif; ?>

            </div>

        </main>
    </div>
<script>
    function confirmarSaida() {
    return confirm("Tem a certeza que deseja sair?");
}
</script>
<script src="../../../assets/js/auto-logout.js"></script>

</body>

</html>