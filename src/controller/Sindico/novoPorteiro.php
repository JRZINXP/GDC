<?php
session_start();

require_once '../../data/conector.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../view/Sindico/novoPorteiro.php?erro=Método não permitido');
    exit;
}

$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';
$confirmar_senha = $_POST['confirmar_senha'] ?? '';
$nome = trim($_POST['nome'] ?? '');

$erros = [];

if (empty($email)) {
    $erros[] = 'Email é obrigatório';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erros[] = 'Email inválido';
}

if (empty($senha)) {
    $erros[] = 'Senha é obrigatória';
} elseif (strlen($senha) < 6) {
    $erros[] = 'Senha deve ter no mínimo 6 caracteres';
}

if (empty($confirmar_senha)) {
    $erros[] = 'Confirmação de senha é obrigatória';
}

if ($senha !== $confirmar_senha) {
    $erros[] = 'As senhas não correspondem';
}

if (empty($nome)) {
    $erros[] = 'Nome é obrigatório';
}

if (!empty($erros)) {
    $erro_msg = urlencode(implode(', ', $erros));
    header("Location: ../../view/Sindico/novoPorteiro.php?erro=$erro_msg");
    exit;
}

try {

    $conector = new Conector();
    $conexao = $conector->getConexao();

    $stmt = $conexao->prepare("SELECT id_usuario FROM Usuario WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        header('Location: ../../view/Sindico/novoPorteiro.php?erro=' . urlencode('Este email já está registrado'));
        $stmt->close();
        exit;
    }
    $stmt->close();

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    $conexao->begin_transaction();

    $stmt = $conexao->prepare("INSERT INTO Usuario (email, senha_hash, tipo) VALUES (?, ?, 'Porteiro')");
    $stmt->bind_param("ss", $email, $senha_hash);
    $stmt->execute();
    $id_usuario = $conexao->insert_id;
    $stmt->close();

    $stmt = $conexao->prepare("INSERT INTO Porteiro (id_usuario, nome) VALUES (?, ?)");
    $stmt->bind_param("is", $id_usuario, $nome);
    $stmt->execute();
    $stmt->close();


    $conexao->commit();

    $sucesso_msg = urlencode('Porteiro criado com sucesso!');
    header("Location: ../../view/Sindico/novoPorteiro.php?success=$sucesso_msg");
    exit;

} catch (mysqli_sql_exception $e) {
    if (isset($conexao)) {
        $conexao->rollback();
    }
    
    $erro_msg = urlencode('Erro ao criar porteiro: ' . $e->getMessage());
    header("Location: ../../view/Sindico/novoPorteiro.php?erro=$erro_msg");
    exit;
} catch (Exception $e) {
    if (isset($conexao)) {
        $conexao->rollback();
    }
    
    $erro_msg = urlencode('Erro inesperado: ' . $e->getMessage());
    header("Location: ../../view/Sindico/novoPorteiro.php?erro=$erro_msg");
    exit;
}
if ($_GET['action'] === 'toggle') {

}

if ($_GET['action'] === 'reset') {

}

?>
