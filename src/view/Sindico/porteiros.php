<?php
session_start();
require_once __DIR__ . '/../../data/conector.php';

/* PROTEÇÃO */
if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] !== 'Sindico') {
    header('Location: ../../login.php');
    exit;
}

$conexao = (new Conector())->getConexao();

/* Buscar dados do síndico */
$stmt = $conexao->prepare("SELECT nome FROM Sindico WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$sindico = $stmt->get_result()->fetch_assoc();

$userName = $sindico['nome'];
$iniciais = strtoupper(substr($userName, 0, 1));

/* Buscar porteiros */
$stmt = $conexao->prepare("
    SELECT p.id_porteiro, p.nome, u.email
    FROM Porteiro p
    JOIN Usuario u ON u.id_usuario = p.id_usuario
    WHERE u.tipo = 'Porteiro'
    ORDER BY p.nome
");
$stmt->execute();
$porteiros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Gestão de Porteiros</title>
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
    background:linear-gradient(135deg,#f3f4f6 0%,#e5e7eb 100%);
    color:#1f2937;
    min-height:100vh;
}

/* HEADER */
.dashboard-header{
    background:#fff;
    padding:1.5rem 2rem;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-bottom:3px solid #7e22ce;
    box-shadow:0 4px 6px rgba(0,0,0,.1);
}

.dashboard-header h2{
    display:flex;
    align-items:center;
    gap:.75rem;
    font-size:1.5rem;
}

.dashboard-header i{color:#7e22ce}

.header-subtitle{
    font-size:.85rem;
    color:#6b7280;
    margin-top:.25rem;
}

.user-info{
    display:flex;
    align-items:center;
    gap:1rem;
}

.user-avatar{
    width:46px;
    height:46px;
    border-radius:50%;
    background:#7e22ce;
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:600;
}

.btn-top{
    padding:10px 18px;
    border-radius:8px;
    text-decoration:none;
    display:flex;
    align-items:center;
    gap:8px;
    font-weight:500;
    transition:.3s;
}

.btn-back{background:#6b7280;color:#fff}
.btn-logout{background:#ef4444;color:#fff}

.btn-back:hover{background:#4b5563}
.btn-logout:hover{background:#dc2626}

/* CONTAINER */
.dashboard-container{
    max-width:1400px;
    margin:2rem auto;
    padding:0 1.25rem;
}

/* PAGE HEADER */
.page-header{
    background:#fff;
    border-radius:.75rem;
    padding:1.5rem 2rem;
    margin-bottom:2rem;
    box-shadow:0 4px 6px rgba(0,0,0,.1);
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:1rem;
}

.page-header h1{
    display:flex;
    align-items:center;
    gap:.75rem;
    font-size:1.75rem;
}

.page-header i{color:#7e22ce}

.btn-primary{
    background:#7e22ce;
    color:#fff;
    padding:12px 20px;
    border-radius:8px;
    text-decoration:none;
    display:flex;
    align-items:center;
    gap:8px;
    transition:.3s;
}

.btn-primary:hover{
    background:#5b21b6;
    transform:translateY(-2px);
}

/* CARD */
.card{
    background:#fff;
    border-radius:.75rem;
    padding:2rem;
    box-shadow:0 4px 6px rgba(0,0,0,.1);
}

/* TABLE */
table{
    width:100%;
    border-collapse:collapse;
    margin-top:1rem;
}

th{
    background:#f8fafc;
    padding:1rem;
    text-align:left;
    font-size:.9rem;
}

td{
    padding:1rem;
    border-top:1px solid #e5e7eb;
}

.actions{
    display:flex;
    gap:.5rem;
}

/* BASE */
.btn-icon {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all .25s ease;
}

/* ===== RESET (CHAVE) ===== */
.btn-reset {
    background: #7e22ce; /* roxo do síndico */
}

.btn-reset i {
    color: #ffffff; /* ícone branco */
}

.btn-reset:hover {
    background: #5b21b6;
    transform: translateY(-2px);
}

/* ===== DELETE ===== */
.btn-delete {
    background: #fee2e2; /* vermelho claro */
}

.btn-delete i {
    color: #dc2626; /* vermelho forte */
}

.btn-delete:hover {
    background: #fecaca;
    transform: translateY(-2px);
}
/* =========================
   BOTÕES PRINCIPAIS (BRANCOS)
   Voltar | Sair | Novo
========================= */

/* botão voltar */
.btn-back,
.btn-back i {
    color: #ffffff !important;
}

/* botão sair */
.btn-logout,
.btn-logout i {
    color: #ffffff !important;
}

/* botão novo (síndico) */
.btn-primary,
.btn-primary i {
    color: #ffffff !important;
}

/* hover continua normal */
.btn-back:hover,
.btn-logout:hover,
.btn-primary:hover {
    color: #ffffff;
}

.empty{
    text-align:center;
    padding:3rem;
    color:#6b7280;
}

.empty i{
    font-size:40px;
    color:#7e22ce;
    margin-bottom:10px;
}


/* RESPONSIVO */
@media(max-width:768px){
    .dashboard-header{
        flex-direction:column;
        gap:1rem;
        text-align:center;
    }

    .page-header{
        flex-direction:column;
        align-items:flex-start;
    }
}
</style>
</head>

<body>

<header class="dashboard-header">
    <div>
        <h2><i class="fas fa-user-shield"></i> Síndico</h2>
        <div class="header-subtitle">Gestão de Porteiros</div>
    </div>

    <div class="user-info">
        <div class="user-avatar"><?= $iniciais ?></div>
        <strong><?= htmlspecialchars($userName) ?></strong>

        <a href="index.php" class="btn-top btn-back">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>

        <a href="../../controller/AuthController.php?action=logout" class="btn-top btn-logout">
            <i class="fas fa-sign-out-alt"></i> Sair
        </a>
    </div>
</header>

<main class="dashboard-container">

    <div class="page-header">
        <h1><i class="fas fa-user-tie"></i> Porteiros Cadastrados</h1>

        <a href="novoPorteiro.php" class="btn-primary">
            <i class="fas fa-user-plus"></i> Novo Porteiro
        </a>
    </div>

    <div class="card">
        <?php if (empty($porteiros)): ?>
            <div class="empty">
                <i class="fas fa-user-slash"></i>
                <p>Nenhum porteiro cadastrado</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($porteiros as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['nome']) ?></td>
                        <td><?= htmlspecialchars($p['email']) ?></td>
                        <td>
                            <div class="actions">
                                <a href="../../controller/Sindico/porteiro.php?action=reset&id=<?= $p['id_porteiro'] ?>"
                                   class="btn-icon btn-reset"
                                   title="Resetar senha">
                                    <i class="fas fa-key"></i>
                                </a>

                                <a href="../../controller/Sindico/porteiro.php?action=delete&id=<?= $p['id_porteiro'] ?>"
                                   class="btn-icon btn-delete"
                                   title="Excluir porteiro"
                                   onclick="return confirm('Excluir este porteiro?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</main>

</body>
</html>
