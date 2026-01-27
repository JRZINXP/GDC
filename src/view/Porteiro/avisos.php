<?php
session_start();
require_once __DIR__ . '/../../data/conector.php';

if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] !== 'Porteiro') {
    header('Location: ../../login.php');
    exit();
}

$conexao = (new Conector())->getConexao();

/* BUSCAR AVISOS (SEM data_criacao) */
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
/* RESET */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    background:#f3f4f6;
    min-height:100vh;
}

/* CONTAINER */
.dashboard-container{
    max-width:1200px;
    margin:40px auto;
    padding:0 20px;
}

/* VOLTAR */
.btn-voltar{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:#e5e7eb;
    color:#374151;
    padding:10px 16px;
    border-radius:8px;
    text-decoration:none;
    font-size:14px;
    font-weight:500;
    margin-bottom:20px;
    transition:.3s;
}

.btn-voltar i{color:#4a148c}

.btn-voltar:hover{
    background:#d1d5db;
    transform:translateX(-3px);
}

/* CARD */
.section-card{
    background:#ffffff;
    padding:30px;
    border-radius:14px;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

/* TÍTULO */
.section-card h1{
    display:flex;
    align-items:center;
    gap:10px;
    color:#1f2937;
    margin-bottom:25px;
}

.section-card h1 i{
    color:#4a148c;
}

/* AVISO */
.aviso-item{
    background:#f9fafb;
    padding:20px;
    border-radius:12px;
    margin-bottom:15px;
    box-shadow:0 6px 15px rgba(0,0,0,.06);
}

/* PRIORIDADES */
.prioridade-Baixa{
    border-left:5px solid #3b82f6;
}

.prioridade-Média{
    border-left:5px solid #f59e0b;
}

.prioridade-Alta{
    border-left:5px solid #ef4444;
}

/* TEXTO */
.aviso-item h3{
    margin-bottom:8px;
    color:#1f2937;
}

.aviso-item p{
    color:#4b5563;
    font-size:14px;
    line-height:1.6;
}

/* META */
.aviso-meta{
    margin-top:10px;
    font-size:12px;
    color:#6b7280;
    display:flex;
    align-items:center;
    gap:10px;
}

/* RESPONSIVO */
@media(max-width:768px){
    .dashboard-container{
        padding:0 15px;
    }
}
</style>
</head>

<body>

<main class="dashboard-container">

    <a href="index.php" class="btn-voltar">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>

    <section class="section-card">
        <h1><i class="fas fa-bullhorn"></i> Avisos do Condomínio</h1>

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

</body>
</html>
