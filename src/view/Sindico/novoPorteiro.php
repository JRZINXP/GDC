<?php
session_start();
require_once __DIR__ . '/../../data/conector.php';

/* PROTEÇÃO */
if (!isset($_SESSION['id']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] != 'Sindico') {
    header('Location: ../../login.php');
    exit();
}

$conexao = (new Conector())->getConexao();
$stmt = $conexao->prepare("SELECT nome FROM Sindico WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$sindico = $stmt->get_result()->fetch_assoc();

$nomeSindico = $sindico['nome'] ?? 'Síndico';
$iniciais = strtoupper(substr($nomeSindico, 0, 1));

$erro = $_GET['erro'] ?? '';
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Novo Porteiro</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

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
    background:#ffffff;
    padding:20px 32px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-bottom:3px solid #7e22ce;
    box-shadow:0 4px 12px rgba(0,0,0,.06);
}

.header-left h2{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:22px;
}

.header-left i{
    color:#7e22ce;
}

.header-subtitle{
    font-size:14px;
    color:#6b7280;
    margin-top:4px;
}

.user-info{
    display:flex;
    align-items:center;
    gap:12px;
}

.user-avatar{
    width:42px;
    height:42px;
    border-radius:50%;
    background:#7e22ce;
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:600;
}

.back-btn{
    background:#6b7280;
    color:#fff;
    padding:8px 14px;
    border-radius:6px;
    text-decoration:none;
    font-size:14px;
}

/* CONTAINER */
.container{
    max-width:600px;
    margin:40px auto;
    background:#fff;
    padding:30px;
    border-radius:14px;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

.container h3{
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:20px;
}

/* ALERTAS */
.alert{
    padding:12px;
    border-radius:8px;
    margin-bottom:15px;
    font-size:14px;
}

.alert-error{
    background:#fee2e2;
    color:#991b1b;
}

.alert-success{
    background:#dcfce7;
    color:#065f46;
}

/* FORM */
.form-group{
    margin-bottom:15px;
}

label{
    font-size:14px;
    font-weight:500;
}

input{
    width:100%;
    padding:12px;
    margin-top:6px;
    border-radius:6px;
    border:1px solid #d1d5db;
}

input:focus{
    outline:none;
    border-color:#7e22ce;
    box-shadow:0 0 0 2px rgba(126,34,206,.15);
}

.btn-submit{
    width:100%;
    margin-top:10px;
    padding:14px;
    background:#7e22ce;
    color:#fff;
    border:none;
    border-radius:8px;
    font-size:15px;
    cursor:pointer;
    transition:.3s;
}

.btn-submit:hover{
    background:#5b21b6;
}
</style>
</head>

<body>

<header class="dashboard-header">
    <div class="header-left">
        <h2><i class="fas fa-user-shield"></i> Novo Porteiro</h2>
        <div class="header-subtitle">Cadastro de funcionário da portaria</div>
    </div>

    <div class="user-info">
        <div class="user-avatar"><?= $iniciais ?></div>
        <strong><?= htmlspecialchars($nomeSindico) ?></strong>
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</header>

<div class="container">

    <h3><i class="fas fa-user-plus"></i> Registrar Porteiro</h3>

    <?php if ($erro): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form action="../../controller/Sindico/porteiro.php" method="POST">

        <div class="form-group">
            <label>Nome Completo</label>
            <input type="text" name="nome" required>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Senha</label>
            <input type="password" name="senha" required>
        </div>

        <div class="form-group">
            <label>Confirmar Senha</label>
            <input type="password" name="confirmar_senha" required>
        </div>

        <button type="submit" class="btn-submit">
            <i class="fas fa-save"></i> Criar Porteiro
        </button>
    </form>
</div>

</body>
</html>
