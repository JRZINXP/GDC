<?php
require_once __DIR__ . '/../utils/log.php';
require_once "../data/conector.php";

session_start();

if (!isset($_SESSION['tentativas'])) {
    $_SESSION['tentativas'] = 0;
}

if ($_SESSION['tentativas'] >= 5) {
    header("Location: ../login.php?erro=" . urlencode(
        "Login bloqueado. Excedeu o número máximo de 5 tentativas."
    ));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit();
}

$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

if ($email == "" || $senha == "") {
    header("Location: ../login.php?erro=" . urlencode("Preencha email e senha."));
    exit();
}

$conexao = (new Conector())->getConexao();

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


        $nomeUsuario = "";

        if ($user['tipo'] == 'Sindico') {
            $stmtNome = $conexao->prepare("SELECT nome FROM Sindico WHERE id_usuario = ?");
        } 
        elseif ($user['tipo'] == 'Morador') {
            $stmtNome = $conexao->prepare("SELECT nome FROM Morador WHERE id_usuario = ?");
        } 
        elseif ($user['tipo'] == 'Porteiro') {
            $stmtNome = $conexao->prepare("SELECT nome FROM Porteiro WHERE id_usuario = ?");
        }

        $stmtNome->bind_param("i", $user['id_usuario']);
        $stmtNome->execute();
        $resNome = $stmtNome->get_result();

        if ($rowNome = $resNome->fetch_assoc()) {
            $nomeUsuario = $rowNome['nome'];
        }

        $_SESSION['nome'] = $nomeUsuario;


        registrarLog(
            $conexao,
            $_SESSION['id'],
            $_SESSION['nome'],
            "LOGIN",
            $_SESSION['tipo_usuario'] . " entrou no sistema"
        );

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
                header("Location: ../login.php?erro=Tipo inválido");
        }

        exit();
    }

    else {

        $_SESSION['tentativas']++;

        registrarLog(
            $conexao,
            $user['id_usuario'],
            $user['email'],
            "LOGIN_ERRO",
            "Senha incorreta"
        );

        $restantes = 5 - $_SESSION['tentativas'];

        header("Location: ../login.php?erro=" . urlencode(
            "Senha incorreta. Tentativas restantes: $restantes"
        ));
        exit();
    }
}


else {

    $_SESSION['tentativas']++;

    registrarLog(
        $conexao,
        null,
        $email,
        "LOGIN_ERRO",
        "Email não encontrado"
    );

    $restantes = 5 - $_SESSION['tentativas'];

    header("Location: ../login.php?erro=" . urlencode(
        "Usuário não encontrado. Tentativas restantes: $restantes"
    ));
    exit();
}
