<?php
session_start();

require_once __DIR__ . '/../../utils/log.php';
require_once __DIR__ . '/../../data/conector.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../view/Sindico/novoPorteiro.php?erro=Método não permitido');
    exit;
}

$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';
$confirmar_senha = $_POST['confirmar_senha'] ?? '';
$nome = trim($_POST['nome'] ?? '');

$erros = [];

if (empty($email)) $erros[] = 'Email obrigatório';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido';
if (strlen($senha) < 6) $erros[] = 'Senha mínima 6 caracteres';
if ($senha !== $confirmar_senha) $erros[] = 'Senhas não correspondem';
if (empty($nome)) $erros[] = 'Nome obrigatório';

if ($erros) {
    header("Location: ../../view/Sindico/novoPorteiro.php?erro=" . urlencode(implode(', ', $erros)));
    exit;
}

try {

    $conexao = (new Conector())->getConexao();

    $stmt = $conexao->prepare("SELECT id_usuario FROM Usuario WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        header('Location: ../../view/Sindico/novoPorteiro.php?erro=Email já existe');
        exit;
    }

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    $conexao->begin_transaction();

    $stmt = $conexao->prepare("INSERT INTO Usuario (email, senha_hash, tipo) VALUES (?, ?, 'Porteiro')");
    $stmt->bind_param("ss", $email, $senha_hash);
    $stmt->execute();
    $id_usuario = $conexao->insert_id;

    $stmt = $conexao->prepare("INSERT INTO Porteiro (id_usuario, nome) VALUES (?, ?)");
    $stmt->bind_param("is", $id_usuario, $nome);
    $stmt->execute();

    $nomeSessao = $_SESSION['nome'] ?? $_SESSION['email'] ?? 'Sistema';
    $idSessao   = $_SESSION['id'] ?? null;

    registrarLog(
        $conexao,
        $idSessao,
        $nomeSessao,
        "CADASTRO_PORTEIRO",
        "Cadastrou o porteiro: $nome"
    );

    $conexao->commit();

    header("Location: ../../view/Sindico/novoPorteiro.php?success=Porteiro criado com sucesso");
    exit;

} catch (Exception $e) {

    if (isset($conexao)) $conexao->rollback();

    header("Location: ../../view/Sindico/novoPorteiro.php?erro=" . urlencode($e->getMessage()));
    exit;
}
