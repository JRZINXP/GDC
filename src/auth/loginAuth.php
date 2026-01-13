<?php
require_once "../data/conector.php";

session_start();

// Apenas aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit();
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$senha = isset($_POST['senha']) ? $_POST['senha'] : '';

if (empty($email) || empty($senha)) {
    header("Location: ../login.php?erro=" . urlencode("Preencha email e senha."));
    exit();
}

$conector = new Conector();
$conexao = $conector->getConexao();

$stmt = $conexao->prepare("SELECT * FROM Usuario WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado && $resultado->num_rows === 1) {
    $user = $resultado->fetch_assoc();

    if (password_verify($senha, $user['senha_hash'])) {
        // Armazena dados do usuário na sessão
        $_SESSION['id'] = $user['id_usuario'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['tipo'] = $user['tipo'];

        // Redireciona por tipo
        switch ($user['tipo']) {
            case 'Morador':
                header("Location: ../view/Morador/index.php");
                exit();
            case 'Sindico':
                header("Location: ../view/Sindico/index.php");
                exit();
            case 'Porteiro':
                header("Location: ../view/Porteiro/index.php");
                exit();
            default:
                header("Location: ../login.php?erro=" . urlencode("Tipo de usuário inválido."));
                exit();
        }
    } else {
        header("Location: ../login.php?erro=" . urlencode("Senha incorreta."));
        exit();
    }
} else {
    header("Location: ../login.php?erro=" . urlencode("Usuário não encontrado."));
    exit();
}

