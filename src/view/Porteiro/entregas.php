<?php
session_start();
require_once __DIR__ . '/../../data/conector.php';

if (!isset($_SESSION['id']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'Porteiro') {
    header('Location: ../../login.php');
    exit();
}

$conexao = (new Conector())->getConexao();
$msg = '';

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
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif
}

/* BODY */
body{
    background:linear-gradient(180deg,#f4f6f9,#eef1f6);
    min-height:100vh;
    color:#1f2937
}
/* ===== HEADER (IGUAL AO REGISTRO DE VISITAS) ===== */
.header{
    background:#ffffff;
    padding:22px 36px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-bottom:5px solid #4a148c;
    box-shadow:0 6px 18px rgba(0,0,0,.08);
    margin-bottom:35px
}

.header h2{
    display:flex;
    align-items:center;
    gap:12px;
    font-size:22px;
    font-weight:600;
    color:#1f2937
}

.header i{
    color:#4a148c
}

.header-back{
    display:inline-flex;
    align-items:center;
    gap:8px;
    text-decoration:none;
    font-weight:600;
    color:#4a148c;
    background:#ede9fe;
    padding:10px 16px;
    border-radius:12px;
    transition:.3s
}

.header-back:hover{
    background:#ddd6fe;
    transform:translateX(-3px)
}

/* CONTAINER */
.dashboard-container{
    max-width:1300px;
    margin:40px auto;
    padding:0 24px
}

/* BOTÃO VOLTAR */
.btn-voltar{
    display:inline-flex;
    align-items:center;
    gap:10px;
    background:#ffffff;
    color:#374151;
    padding:12px 18px;
    border-radius:12px;
    text-decoration:none;
    font-size:14px;
    font-weight:600;
    margin-bottom:30px;
    box-shadow:0 6px 18px rgba(0,0,0,.08);
    transition:.3s
}

.btn-voltar i{color:#4a148c}

.btn-voltar:hover{
    transform:translateX(-4px);
    background:#f3f4f6
}

/* ===== SECTION CARD ===== */
.section-card{
    background:#ffffff;
    padding:30px;
    border-radius:22px;
    margin-bottom:35px;
    box-shadow:0 14px 35px rgba(0,0,0,.1)
}

/* TÍTULOS */
.section-card h1,
.section-card h2{
    font-size:22px;
    font-weight:600;
    margin-bottom:22px;
    display:flex;
    align-items:center;
    gap:12px
}

.section-card h1 i,
.section-card h2 i{
    color:#4a148c
}

/* ===== FORM ===== */
form{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px
}

form input,
form select{
    padding:14px;
    border-radius:12px;
    border:1px solid #d1d5db;
    font-size:14px
}

form input:focus,
form select:focus{
    outline:none;
    border-color:#4a148c;
    box-shadow:0 0 0 3px rgba(74,20,140,.15)
}

/* BOTÃO */
.action-btn{
    grid-column:1/-1;
    background:linear-gradient(135deg,#4a148c,#311b92);
    color:#fff;
    padding:15px;
    border-radius:16px;
    border:none;
    cursor:pointer;
    font-size:15px;
    font-weight:600;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    transition:.3s
}

.action-btn:hover{
    filter:brightness(1.1);
    transform:translateY(-3px);
    box-shadow:0 10px 25px rgba(74,20,140,.45)
}

/* ===== LISTA GRID ===== */
.encomendas-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(320px,1fr));
    gap:22px
}

/* ===== ENCOMENDA CARD ===== */
.encomenda-item{
    background:#ffffff;
    padding:22px;
    border-radius:20px;
    box-shadow:0 12px 30px rgba(0,0,0,.1);
    border-top:6px solid #d1d5db;
    transition:.3s
}

.encomenda-item:hover{
    transform:translateY(-6px);
    box-shadow:0 18px 40px rgba(0,0,0,.14)
}

/* STATUS */
.encomenda-item.pendente{border-color:#f59e0b}
.encomenda-item.entregue{border-color:#10b981}

.encomenda-item strong{
    font-size:16px;
    display:block;
    margin-bottom:6px
}

.encomenda-item i{
    color:#4a148c;
    margin-right:6px
}

/* BADGE */
.status-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:6px 14px;
    border-radius:999px;
    font-size:12px;
    font-weight:600;
    margin-top:12px
}

.status-pendente{
    background:#fff7ed;
    color:#9a3412
}

.status-entregue{
    background:#ecfdf5;
    color:#065f46
}

/* RESPONSIVO */
@media(max-width:768px){
    form{
        grid-template-columns:1fr
    }
}

</style>
</head>

<body>

<header class="header">
    <h2>
        <i class="fas fa-box"></i>
        Registro de Encomendas
    </h2>

    <a href="index.php" class="header-back">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</header>

<main class="dashboard-container">

  

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

        <div class="encomendas-grid">

<?php foreach ($entregas as $e): ?>
    <div class="encomenda-item <?= $e['status'] == 0 ? 'pendente' : 'entregue' ?>">

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
        </div>
    </section>

</main>

</body>
</html>
