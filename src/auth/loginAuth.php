<?php
require_once "../data/conector.php";

session_start();

if (!isset($_SESSION['tentativas'])) {
    $_SESSION['tentativas'] = 0;
}
if ($_SESSION['tentativas'] >= 5) {
    header("Location: ../login.php?erro=" . urlencode(
        "Login bloqueado. Excedeu o número máximo de 5 tentativas. Tente mais tarde."
    ));
    exit();
}

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

        $_SESSION['tentativas'] = 0;

        $_SESSION['id'] = $user['id_usuario'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['tipo_usuario'] = $user['tipo'];
        $_SESSION['login_time'] = time();
        $_SESSION['session_timeout'] = 24 * 60 * 60;
        $id_usuario = $user['id_usuario'];

        $stmtLog = $conexao->prepare("
    INSERT INTO logs (id_usuario, acao) 
    VALUES (?, 'Login no sistema')
");
        $stmtLog->bind_param("i", $id_usuario);
        $stmtLog->execute();


        $cookie_name = 'gdc_session_' . md5($user['id_usuario']);
        $cookie_value = json_encode([
            'id_usuario' => $user['id_usuario'],
            'email' => $user['email'],
            'tipo' => $user['tipo'],
            'login_time' => time()
        ]);
        setcookie($cookie_name, $cookie_value, time() + (24 * 60 * 60), '/', '', false, true);


        switch ($user['tipo']) {
            case 'Morador':
                header("Location: ../view/Morador/index.php");
                break;

            case 'Sindico':
                header("Location: ../view/Sindico/index.php");
                break;

            case 'Porteiro':
                header("Location: ../view/Porteiro/index.php");
                break;

            default:
                header("Location: ../login.php?erro=" . urlencode("Tipo de usuário inválido."));
        }
        exit();
    } else {
        $_SESSION['tentativas']++;

        $stmtLog = $conexao->prepare("
    INSERT INTO logs (id_usuario, acao) 
    VALUES (?, 'Tentativa de login com senha errada')
");
        $stmtLog->bind_param("i", $user['id_usuario']);
        $stmtLog->execute();


        $restantes = 5 - $_SESSION['tentativas'];

        if ($restantes > 0) {
            header("Location: ../login.php?erro=" . urlencode(
                "Senha incorreta. Tentativas restantes: $restantes"
            ));
        } else {
            header("Location: ../login.php?erro=" . urlencode(
                "Login bloqueado após 5 tentativas inválidas."
            ));
        }
        exit();
    }
} else {
    $_SESSION['tentativas']++;

    $stmtLog = $conexao->prepare("
    INSERT INTO logs (id_usuario, acao) 
    VALUES (NULL, ?)
");

    $acao = "Tentativa de login com email inexistente: $email";
    $stmtLog->bind_param("s", $acao);
    $stmtLog->execute();

    $restantes = 5 - $_SESSION['tentativas'];

    header("Location: ../login.php?erro=" . urlencode(
        "Usuário não encontrado. Tentativas restantes: $restantes"
    ));
    exit();
}
