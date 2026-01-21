<?php
session_start();
require_once '../../data/conector.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Morador</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/colors.css">

    <style>
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
                        url('../../../assets/img/coco.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Poppins', sans-serif;
        }

        .container {
            max-width: 500px;
            width: 100%;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,.25);
            overflow: hidden;
            animation: fadeIn .4s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-header {
            background: var(--color-primary);
            color: #fff;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 25px;
        }

        .back-btn-header {
            background-color: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .back-btn-header:hover {
            background-color: rgba(255,255,255,0.35);
            transform: translateX(-3px);
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            font-weight: 500;
            margin-bottom: 6px;
            display: block;
        }

        input, select {
            width: 100%;
            padding: 10px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 0.95rem;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--color-primary);
        }

        .btn-primary {
            background: var(--color-primary);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: 0.3s;
            width: 100%;
        }

        .btn-primary:hover {
            background: var(--color-primary-dark);
            transform: translateY(-2px);
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid #10b981;
            animation: slideDown .4s ease;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid #ef4444;
            animation: slideDown .4s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>
<div class="container">
    <div class="card">
        <div class="card-header">
            <div class="card-header-title">
                <i class="fas fa-user-plus"></i>
                <h3>Criar Novo Morador</h3>
            </div>
            <a href="moradores.php" class="back-btn-header">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <div class="card-body">

            <?php
            $mostrou_mensagem = false;
            if (isset($_SESSION['mensagem'])) {
                $tipo = $_SESSION['tipo_mensagem'] === 'sucesso'
                        ? 'alert-success'
                        : 'alert-error';

                echo "<div class='$tipo' id='alerta'>";
                echo $_SESSION['mensagem'];
                echo "</div>";

                $mostrou_mensagem = true;
                unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']);
            }
            ?>

            <form action="../../controller/Sindico/user.php" method="post" id="form-novo-morador">

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
                    <label>Unidade</label>
                    <select name="unidade" required>
                        <option value="">-- Selecione uma unidade --</option>
                        <?php
                        $conector = new Conector();
                        $conexao = $conector->getConexao();
                        $resultado = $conexao->query("SELECT id_unidade, numero FROM Unidade ORDER BY numero");
                        while ($u = $resultado->fetch_assoc()) {
                            echo "<option value='{$u['id_unidade']}'>{$u['numero']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Telefone</label>
                    <input type="tel" name="telefone">
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Criar Morador
                </button>
            </form>

        </div>
    </div>
</div>

<script>
<?php if ($mostrou_mensagem): ?>
setTimeout(() => {
    const alerta = document.getElementById('alerta');
    if (alerta) {
        alerta.style.opacity = '0';
        setTimeout(() => alerta.remove(), 400);
        document.getElementById('form-novo-morador').reset();
    }
}, 3000);
<?php endif; ?>
</script>

</body>
</html>
