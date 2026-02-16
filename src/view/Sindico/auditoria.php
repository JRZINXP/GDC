<?php
require_once __DIR__ . '/../../data/conector.php';
session_start();

if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] !== 'Sindico') {
    header('Location: ../../login.php');
    exit;
}

$conexao = (new Conector())->getConexao();

$logs = $conexao->query("
SELECT nome_usuario, acao, descricao, data_log 
FROM logs 
ORDER BY data_log DESC
");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">

<title>Auditoria do Sistema</title>
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
}

/* HEADER */
.dashboard-header{
    background:white;
    padding:1.5rem 2rem;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 4px 6px rgba(0,0,0,0.1);
    border-bottom:3px solid #7e22ce;
}

.dashboard-header h2{
    font-size:1.4rem;
    display:flex;
    align-items:center;
    gap:10px;
}

.dashboard-header i{
    color:#7e22ce;
}

/* CARD */
.card{
    max-width:1100px;
    margin:40px auto;
    background:#fff;
    padding:25px;
    border-radius:14px;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

.card h3{
    font-size:1.3rem;
    margin-bottom:20px;
    display:flex;
    align-items:center;
    gap:10px;
}

.card h3 i{
    color:#7e22ce;
}

/* TABELA */
.audit-table{
    width:100%;
    border-collapse:collapse;
    font-size:14px;
}

.audit-table th{
    background:#f3f4f6;
    padding:15px;
    text-align:left;
    font-weight:600;
}

.audit-table td{
    padding:15px;
    border-top:1px solid #e5e7eb;
}

.audit-table tr:hover{
    background:#f9fafb;
}

/* BADGES */
.badge{
    padding:6px 10px;
    border-radius:8px;
    font-size:12px;
    font-weight:600;
}

.badge-login{
    background:#dcfce7;
    color:#166534;
}

.badge-logout{
    background:#fee2e2;
    color:#991b1b;
}

.badge-add{
    background:#e0e7ff;
    color:#3730a3;
}

.badge-edit{
    background:#fef3c7;
    color:#92400e;
}

.badge-del{
    background:#fecaca;
    color:#7f1d1d;
}

/* DATA */
.audit-date{
    font-size:12px;
    color:#6b7280;
}

/* BOTÃO VOLTAR */
.back-btn{
    background:#6c757d;
    color:#fff;
    padding:10px 18px;
    border-radius:8px;
    text-decoration:none;
    font-size:14px;
}

.back-btn:hover{
    background:#5a6268;
}


</style>

</head>

<body>
    <header class="dashboard-header">
    <h2><i class="fas fa-shield-alt"></i> Auditoria do Sistema</h2>
    <a href="index.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</header>

<div class="card">
    <h3 style="margin-bottom:15px;">
        <i class="fas fa-clipboard-list"></i> Auditoria do Sistema
    </h3>

    <?php
    $logs = $conexao->query("
        SELECT nome_usuario, acao, descricao, data_log 
        FROM logs
        ORDER BY data_log DESC
        LIMIT 30
    ");
    ?>

    <table class="audit-table">
    <thead>
        <tr>
            <th>Usuario</th>
            <th>Ação</th>
            <th>Descrição</th>
            <th>Data</th>
        </tr>
    </thead>

    <tbody>
    <?php while($l = $logs->fetch_assoc()): 

        $badge = "badge-add";
        if($l['acao']=="LOGIN") $badge="badge-login";
        if($l['acao']=="LOGOUT") $badge="badge-logout";
        if($l['acao']=="EDITAR_MORADOR") $badge="badge-edit";
        if($l['acao']=="DESATIVAR_MORADOR") $badge="badge-del";
    ?>
        <tr>
            <td><?= htmlspecialchars($l['nome_usuario']) ?></td>

            <td>
                <span class="badge <?= $badge ?>">
                    <?= htmlspecialchars($l['acao']) ?>
                </span>
            </td>

            <td><?= htmlspecialchars($l['descricao']) ?></td>

            <td class="audit-date">
                <?= date('d/m/Y H:i', strtotime($l['data_log'])) ?>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

</div>

</body>
</html>
