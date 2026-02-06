<?php
require_once __DIR__ . '/../../data/conector.php';
session_start();

if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] !== 'Sindico') {
    header('Location: ../../login.php');
    exit;
}

$conexao = (new Conector())->getConexao();


$stmt = $conexao->prepare("SELECT nome FROM Sindico WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

$userName = $row['nome'];
$iniciais = strtoupper(substr($userName, 0, 1));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'adicionar') {

    $titulo = trim($_POST['titulo']);
    $conteudo = trim($_POST['conteudo']);
    $prioridade = $_POST['prioridade'];

    if ($titulo && $conteudo && $prioridade) {
        $stmt = $conexao->prepare("
            INSERT INTO Aviso (titulo, conteudo, prioridade, criado_por)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("sssi", $titulo, $conteudo, $prioridade, $_SESSION['id']);
        $stmt->execute();
    }

    header("Location: avisos.php");
    exit;
}

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
    <title>Gerir Avisos</title>
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

        body {
            background: #f4f6f9;
            color: #1f2937
        }

        .dashboard-header {
            background: white;
            color: #1f2937;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-bottom: 3px solid #7e22ce;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .dashboard-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .dashboard-header h2 i {
            color: #7e22ce;
        }

        .header-subtitle {
            font-size: .875rem;
            color: #6b7280;
            margin-top: .25rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: #7e22ce;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .user-details {
            text-align: right;
        }

        .user-name {
            font-weight: 500;
            font-size: 1rem;
            color: #1f2937;
        }

        .user-role {
            font-size: .75rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: .25rem;
            margin-top: .125rem;
        }

        .back-btn {
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
            background: #6c757d;
        }

        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .back-btn i {
            color: #fff
        }


        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px
        }

        .card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, .08);
            margin-bottom: 25px
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .btn {
            padding: 14px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: center;
            font-weight: 500
        }

        .btn-success {
            background: #7e22ce;
            color: #fff
        }

        .btn-success:hover {
            background: #5b21b6
        }

        .btn-danger {
            background: #e5e7eb;
            color: #374151;
            margin-top: 10px
        }

        .btn-danger:hover {
            background: #d1d5db
        }


        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px
        }

        .aviso {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, .08);
        }

        .prioridade-Baixa {
            border-left: 5px solid #3b82f6
        }

        .prioridade-Média {
            border-left: 5px solid #f59e0b
        }

        .prioridade-Alta {
            border-left: 5px solid #ef4444
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .6);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
            z-index: 999
        }

        .modal.active {
            display: flex
        }

        .modal-box {
            background: #fff;
            border-radius: 16px;
            width: 100%;
            max-width: 480px;
            padding: 28px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, .25)
        }

        .modal-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px
        }

        .modal-header i {
            color: #7e22ce
        }


        .form-group {
            margin-bottom: 16px
        }

        label {
            font-weight: 500;
            margin-bottom: 6px;
            display: block
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            border: 1px solid #d1d5db;
            font-size: 14px
        }

        textarea {
            resize: vertical
        }
    </style>
</head>

<body>

    <header class="dashboard-header">
        <div>
            <h2><i class="fas fa-building"></i> Condominio Digital</h2>
            <div class="header-subtitle">Avisos</div>
        </div>

        <div class="user-info">
            <div class="user-avatar"><?php echo $iniciais; ?></div>
            <div class="user-details">
                <div class="user-name"><?php echo $userName; ?></div>
                <div class="user-role"><i class="fas fa-user-shield"></i> Síndico</div>
            </div>
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>


        </div>
    </header>

    <div class="container">

        <div class="card top">
            <strong>Avisos publicados</strong>
            <button class="btn btn-success" onclick="abrirModal()">
                <i class="fas fa-plus"></i> Novo Aviso
            </button>
        </div>

        <div class="grid">
            <?php foreach ($avisos as $a): ?>
                <div class="aviso prioridade-<?= $a['prioridade'] ?>">
                    <h3><?= htmlspecialchars($a['titulo']) ?></h3>
                    <p><?= nl2br(htmlspecialchars($a['conteudo'])) ?></p>
                    <small>Prioridade: <?= $a['prioridade'] ?></small>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

    <div class="modal" id="modal">
        <div class="modal-box">

            <div class="modal-header">
                <h3><i class="fas fa-bullhorn"></i> Novo Aviso</h3>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="adicionar">

                <div class="form-group">
                    <label>Título</label>
                    <input type="text" name="titulo" required>
                </div>

                <div class="form-group">
                    <label>Conteúdo</label>
                    <textarea name="conteudo" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label>Prioridade</label>
                    <select name="prioridade" required>
                        <option value="">Selecione</option>
                        <option value="Baixa">Baixa</option>
                        <option value="Média">Média</option>
                        <option value="Alta">Alta</option>
                    </select>
                </div>

                <button class="btn btn-success" type="submit">
                    <i class="fas fa-save"></i> Publicar
                </button>

                <button type="button" class="btn btn-danger" onclick="fecharModal()">
                    Cancelar
                </button>
            </form>

        </div>
    </div>

    <script>
        const modal = document.getElementById('modal');

        function abrirModal() {
            modal.classList.add('active');
        }

        function fecharModal() {
            modal.classList.remove('active');
        }
    </script>

<script src="../../../assets/js/auto-logout.js"></script>

</body>

</html>