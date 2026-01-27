<?php
session_start();
require_once __DIR__ . '/../../data/conector.php';

if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] !== 'Morador') {
    header('Location: ../../login.php');
    exit();
}

$conexao = (new Conector())->getConexao();

/* BUSCAR MORADOR */
$stmt = $conexao->prepare("SELECT nome FROM Morador WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$morador = $stmt->get_result()->fetch_assoc();

$nomeMorador = $morador['nome'];
$iniciais = strtoupper(substr($nomeMorador, 0, 1));

/* BUSCAR AVISOS */
$avisos = $conexao->query("
    SELECT id_aviso, titulo, conteudo, prioridade
    FROM Aviso
    ORDER BY id_aviso DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Avisos</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif}
body{background:#f4f6f9}

/* ===== LAYOUT ===== */
.layout{display:flex;min-height:100vh}

/* ===== SIDEBAR ===== */
.sidebar{
    width:240px;
    background:#9743d7;
    color:#fff;
    padding:25px 20px;
    display:flex;
    flex-direction:column;
}
.sidebar h2{
    font-size:20px;
    margin-bottom:30px;
    display:flex;
    gap:10px;
    align-items:center;
}
.sidebar nav a{
    display:flex;
    gap:12px;
    padding:14px 16px;
    color:#fff;
    text-decoration:none;
    border-radius:10px;
    margin-bottom:10px;
}
.sidebar nav a:hover{background:rgba(255,255,255,.18)}
.sidebar nav a.active{
    background:#fff;
    color:#9743d7;
    font-weight:600;
}
.sidebar nav a.active i{color:#9743d7}
.sidebar nav a i{color:#fff}
.sidebar .logout{
    margin-top:auto;
    background:rgba(0,0,0,.25);
}

/* ===== CONTEÚDO ===== */
.content{flex:1;padding:40px}

/* HEADER */
.dashboard-header{
    background:#fff;
    padding:20px 30px;
    border-radius:14px;
    box-shadow:0 6px 15px rgba(0,0,0,.08);
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
    border-bottom:3px solid #9743d7;
}
.header-left h2{
    display:flex;
    gap:10px;
    align-items:center;
    color:#333;
}
.header-left h2 i{color:#9743d7}
.header-subtitle{color:#555;font-size:14px;margin-top:5px}
.user-info{display:flex;align-items:center;gap:10px}
.user-avatar{
    width:42px;height:42px;border-radius:50%;
    background:#9743d7;color:#fff;
    display:flex;align-items:center;justify-content:center;
    font-weight:600;
}

/* CARD */
.section-card{
    background:#fff;
    padding:30px;
    border-radius:14px;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

/* AVISOS */
.aviso-item{
    background:#f9fafb;
    padding:20px;
    border-radius:12px;
    margin-bottom:15px;
    box-shadow:0 6px 15px rgba(0,0,0,.06);
}
.prioridade-Baixa{border-left:5px solid #3b82f6}
.prioridade-Média{border-left:5px solid #f59e0b}
.prioridade-Alta{border-left:5px solid #ef4444}

.aviso-item h3{
    color:#333;
    margin-bottom:8px;
}
.aviso-item p{
    color:#555;
    font-size:14px;
    line-height:1.6;
}
.aviso-meta{
    margin-top:10px;
    font-size:12px;
    color:#777;
    display:flex;
    gap:8px;
    align-items:center;
}
</style>
</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <h2><i class="fas fa-home"></i> Morador</h2>
        <nav>
            <a href="index.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="agendar_visita.php"><i class="fas fa-users"></i> Visitas</a>
            <a href="reservas.php"><i class="fas fa-calendar-check"></i> Reservas</a>
            <a href="encomendas.php"><i class="fas fa-box"></i> Encomendas</a>
            <a href="avisos.php" class="active"><i class="fas fa-bullhorn"></i> Avisos</a>
             <a href="ocorrencias.php"><i class="fas fa-exclamation-triangle"></i> Ocorrências</a>
            <a href="../../logout.php?logout=1" class="logout">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </nav>
    </aside>

    <!-- CONTEÚDO -->
    <main class="content">

        <header class="dashboard-header">
            <div class="header-left">
                <h2><i class="fas fa-bullhorn"></i> Avisos</h2>
                <div class="header-subtitle">Comunicados do condomínio</div>
            </div>
            <div class="user-info">
                <div class="user-avatar"><?= $iniciais ?></div>
                <strong><?= htmlspecialchars($nomeMorador) ?></strong>
            </div>
        </header>

        <section class="section-card">

            <?php if (empty($avisos)): ?>
                <p style="color:#777">Nenhum aviso publicado.</p>
            <?php else: ?>

                <?php foreach ($avisos as $a): ?>
                    <div class="aviso-item prioridade-<?= $a['prioridade'] ?>">
                        <h3><?= htmlspecialchars($a['titulo']) ?></h3>
                        <p><?= nl2br(htmlspecialchars($a['conteudo'])) ?></p>

                        <div class="aviso-meta">
                            <i class="fas fa-flag"></i>
                            Prioridade: <?= $a['prioridade'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>

        </section>

    </main>
</div>

</body>
</html>
