<?php
session_start();
require_once __DIR__ . '/../../data/conector.php';

if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] !== 'Porteiro') {
    header('Location: ../../login.php');
    exit();
}

$conexao = (new Conector())->getConexao();
$msg = '';

/* REGISTRAR ENCOMENDA */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar'])) {

    $id_morador = $_POST['id_morador'] ?? null;
    $descricao  = trim($_POST['descricao'] ?? '');

    if ($id_morador && $descricao) {

        $stmt = $conexao->prepare("
            INSERT INTO Entrega (id_morador, descricao)
            VALUES (?, ?)
        ");
        $stmt->bind_param("is", $id_morador, $descricao);
        $stmt->execute();

        $msg = "Encomenda registrada com sucesso!";
    } else {
        $msg = "Preencha todos os campos.";
    }
}

/* MARCAR COMO ENTREGUE */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entregar'])) {

    $id_entrega = $_POST['id_entrega'];

    $stmt = $conexao->prepare("
        UPDATE Entrega
        SET status = 1,
            data_entrega = NOW()
        WHERE id_entrega = ?
    ");
    $stmt->bind_param("i", $id_entrega);
    $stmt->execute();

    $msg = "Encomenda entregue ao morador.";
}

/* MORADORES */
$moradores = $conexao->query("
    SELECT m.id_morador, m.nome, u.numero
    FROM Morador m
    JOIN Unidade u ON u.id_unidade = m.id_unidade
    ORDER BY m.nome
")->fetch_all(MYSQLI_ASSOC);

/* ENCOMENDAS */
$entregas = $conexao->query("
    SELECT e.id_entrega, e.descricao, e.data_recepcao, e.status,
           m.nome AS morador_nome, u.numero AS casa
    FROM Entrega e
    JOIN Morador m ON m.id_morador = e.id_morador
    JOIN Unidade u ON u.id_unidade = m.id_unidade
    ORDER BY e.data_recepcao DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Registrar Encomendas</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
/* RESET */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

/* BODY */
body {
    background: #f3f4f6;
    min-height: 100vh;
}

/* CONTAINER */
.dashboard-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

/* BOTÃO VOLTAR */
.btn-voltar {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #e5e7eb;
    color: #374151;
    padding: 10px 16px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 20px;
    transition: 0.3s;
}

.btn-voltar i {
    color: #4a148c;
}

.btn-voltar:hover {
    background: #d1d5db;
    transform: translateX(-3px);
}

/* SECTION */
.section-card {
    background: #ffffff;
    padding: 30px;
    border-radius: 14px;
    margin-bottom: 30px;
    box-shadow: 0 10px 25px rgba(0,0,0,.08);
}

/* TITLES */
.section-card h1,
.section-card h2 {
    color: #1f2937;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-card h1 i,
.section-card h2 i {
    color: #4a148c;
}

/* FORM */
form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

select,
input {
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    font-size: 14px;
}

select:focus,
input:focus {
    outline: none;
    border-color: #4a148c;
}

/* BUTTON */
.action-btn {
    background: #4a148c;
    color: #fff;
    padding: 14px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
    transition: 0.3s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.action-btn:hover {
    background: #311b92;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(74, 20, 140, 0.45);
}

/* LISTA */
.encomenda-item {
    background: #f9fafb;
    padding: 18px;
    border-radius: 12px;
    margin-bottom: 15px;
    border-left: 5px solid #4a148c;
}

/* STATUS */
.status-pendente {
    color: #f59e0b;
    font-weight: 600;
}

.status-entregue {
    color: #10b981;
    font-weight: 600;
}

/* RESPONSIVO */
@media (max-width: 768px) {
    .dashboard-container {
        padding: 0 15px;
    }
}
</style>
</head>

<body>

<main class="dashboard-container">

    <a href="index.php" class="btn-voltar">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>

    <!-- REGISTRO -->
    <section class="section-card">
        <h1><i class="fas fa-box"></i> Registrar Encomenda</h1>

        <?php if ($msg): ?>
            <p><strong><?= htmlspecialchars($msg) ?></strong></p>
        <?php endif; ?>

        <form method="POST">
            <select name="id_morador" required>
                <option value="">Selecione o morador</option>
                <?php foreach ($moradores as $m): ?>
                    <option value="<?= $m['id_morador'] ?>">
                        <?= htmlspecialchars($m['nome']) ?> – Casa <?= $m['numero'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="descricao" placeholder="Descrição da encomenda" required>

            <button type="submit" name="registrar" class="action-btn">
                <i class="fas fa-save"></i> Registrar Encomenda
            </button>
        </form>
    </section>

    <!-- LISTA -->
    <section class="section-card">
        <h2><i class="fas fa-list"></i> Encomendas Registradas</h2>

        <?php foreach ($entregas as $e): ?>
            <div class="encomenda-item">
                <strong><?= htmlspecialchars($e['descricao']) ?></strong><br>
                <i class="fas fa-user"></i> <?= htmlspecialchars($e['morador_nome']) ?> – Casa <?= $e['casa'] ?><br>
                <i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($e['data_recepcao'])) ?><br><br>

                <?php if ($e['status'] == 0): ?>
                    <form method="POST">
                        <input type="hidden" name="id_entrega" value="<?= $e['id_entrega'] ?>">
                        <button type="submit" name="entregar" class="action-btn">
                            <i class="fas fa-check-circle"></i> Marcar como entregue
                        </button>
                    </form>
                <?php else: ?>
                    <span class="status-entregue">
                        <i class="fas fa-check"></i> Entregue
                    </span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </section>

</main>

</body>
</html>
