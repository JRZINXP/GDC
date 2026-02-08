<?php
session_start();
require_once __DIR__ . '/../../data/conector.php';

if (!isset($_SESSION['id']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'Porteiro') {
    header('Location: ../../login.php');
    exit();
}

$conexao = (new Conector())->getConexao();

$avisos = $conexao->query("
    SELECT 
        id_aviso,
        titulo,
        conteudo,
        prioridade
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

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    background:#f4f6f9;
    color:#1f2937;
    min-height:100vh;
}

.dashboard-header{
    background:#ffffff;
    padding:22px 36px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-bottom:4px solid #4a148c;
    box-shadow:0 6px 18px rgba(0,0,0,.08);
    position:sticky;
    top:0;
    z-index:100;
}

.header-left h2{
    display:flex;
    align-items:center;
    gap:12px;
    font-size:24px;
    font-weight:600;
    color:#1f2937;
}

.header-left h2 i{
    color:#4a148c;
    background:#ede9fe;
    padding:10px;
    border-radius:12px;
}

.header-subtitle{
    font-size:14px;
    color:#6b7280;
    margin-top:6px;
}

.back-btn{
    background:#f3f4f6;
    color:#374151;
    padding:12px 18px;
    border-radius:10px;
    text-decoration:none;
    font-size:14px;
    font-weight:500;
    display:flex;
    align-items:center;
    gap:8px;
    transition:.25s;
}

.back-btn i{
    color:#4a148c;
}

.back-btn:hover{
    background:#e5e7eb;
    transform:translateX(-3px);
}

.dashboard-container{
    max-width:1200px;
    margin:40px auto;
    padding:0 24px;
}

.section-card{
    background:transparent;
    padding:0;
    border-radius:0;
    box-shadow:none;
}

.section-card h1{
    display:flex;
    align-items:center;
    gap:12px;
    font-size:22px;
    font-weight:600;
    margin-bottom:30px;
    color:#1f2937;
}

.section-card h1 i{
    color:#4a148c;
}

.aviso-item{
    position:relative;
    background:#ffffff;
    padding:22px 22px 22px 28px;
    border-radius:14px;
    margin-bottom:20px;
    border-left:6px solid #d1d5db;
    transition:.25s;
}

.aviso-item:hover{
    background:#fafafa;
}

.prioridade-Baixa{
    border-color:#3b82f6;
}

.prioridade-Média{
    border-color:#f59e0b;
}

.prioridade-Alta{
    border-color:#ef4444;
}

.aviso-item h3{
    font-size:18px;
    font-weight:600;
    color:#111827;
    margin-bottom:6px;
}

.aviso-item p{
    font-size:15px;
    color:#374151;
    line-height:1.7;
}
.aviso-meta{
    margin-top:14px;
    font-size:13px;
    color:#6b7280;
    display:flex;
    align-items:center;
    gap:10px;
}

.aviso-meta i{
    color:#4a148c;
}


@media(max-width:768px){

    .dashboard-header{
        padding:18px 24px;
        flex-direction:column;
        align-items:flex-start;
        gap:14px;
    }

    .header-left h2{
        font-size:20px;
    }

    .dashboard-container{
        padding:0 18px;
    }

    .section-card{
        padding:26px;
    }

    .aviso-item{
        padding:20px 20px 20px 26px;
    }
}

</style>
</head>

<body>
    <header class="dashboard-header">
    <div class="header-left">
        <h2>
            <i class="fas fa-bullhorn"></i>
            Avisos do Condomínio
        </h2>
        <div class="header-subtitle">
            Comunicados oficiais
        </div>
    </div>

    <a href="index.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</header>


<main class="dashboard-container">

    <section class="section-card">
        

        <?php if (empty($avisos)): ?>
            <p style="color:#6b7280">Nenhum aviso publicado.</p>
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

<script src="../../../assets/js/auto-logout.js"></script>

</body>
</html>
