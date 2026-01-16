<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Porteiro - Condomínio Digital</title>
    <link rel="stylesheet" href="../../../assets/css/colors.css">
    <link rel="stylesheet" href="../../../assets/css/sindico.css">
    <style>
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
        }
        .form-section {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-md);
            border-top: 4px solid var(--color-primary);
        }
        .form-section h2 {
            color: var(--color-primary-dark);
            margin-bottom: 20px;
            border-bottom: 2px solid var(--color-primary);
            padding-bottom: 10px;
            font-size: var(--font-size-xl);
        }
        fieldset {
            border: none;
            padding: 0;
        }
        legend {
            color: var(--color-primary-dark) !important;
            font-weight: 600 !important;
            margin-bottom: 15px !important;
            font-size: var(--font-size-base) !important;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--color-dark);
            font-weight: 500;
            font-size: var(--font-size-sm);
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: var(--font-size-base);
            box-sizing: border-box;
            font-family: var(--font-family);
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(152, 67, 215, 0.1);
        }
        .button-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: var(--font-size-base);
            font-weight: 600;
            transition: all 0.3s;
            font-family: var(--font-family);
        }
        .btn-submit {
            background-color: var(--color-primary);
            color: var(--color-white);
        }
        .btn-submit:hover {
            background-color: var(--color-primary-dark);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        .btn-cancel {
            background-color: var(--color-light-gray);
            color: var(--color-dark);
            border: 1px solid #d1d5db;
        }
        .btn-cancel:hover {
            background-color: #e5e7eb;
            box-shadow: var(--shadow-sm);
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            text-align: center;
            font-weight: 500;
        }
        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--color-success);
            border: 1px solid var(--color-success);
        }
        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--color-error);
            border: 1px solid var(--color-error);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-section">
            <h2>Adicionar Novo Porteiro</h2>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['erro'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_GET['erro']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="../../controller/Sindico/novoPorteiro.php">
                <!-- Dados de Login (Usuário) -->
                <fieldset>
                    <legend>Dados de Acesso</legend>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required placeholder="exemplo@email.com">
                    </div>

                    <div class="form-group">
                        <label for="senha">Senha</label>
                        <input type="password" id="senha" name="senha" required placeholder="Mínimo 6 caracteres" minlength="6">
                    </div>

                    <div class="form-group">
                        <label for="confirmar_senha">Confirmar Senha</label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" required placeholder="Repita a senha">
                    </div>
                </fieldset>

                <!-- Dados do Porteiro -->
                <fieldset style="margin-top: 20px;">
                    <legend>Dados Pessoais</legend>
                    
                    <div class="form-group">
                        <label for="nome">Nome Completo</label>
                        <input type="text" id="nome" name="nome" required placeholder="Nome do porteiro">
                    </div>
                </fieldset>

                <div class="button-group">
                    <button type="submit" class="btn btn-submit">Criar Porteiro</button>
                    <button type="button" class="btn btn-cancel" onclick="window.history.back()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Validação de senha
        document.querySelector('form').addEventListener('submit', function(e) {
            const senha = document.getElementById('senha').value;
            const confirmarSenha = document.getElementById('confirmar_senha').value;

            if (senha !== confirmarSenha) {
                e.preventDefault();
                alert('As senhas não correspondem!');
                return false;
            }

            if (senha.length < 6) {
                e.preventDefault();
                alert('A senha deve ter pelo menos 6 caracteres!');
                return false;
            }
        });
    </script>
</body>
</html>
