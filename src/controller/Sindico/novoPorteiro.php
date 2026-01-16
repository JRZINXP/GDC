<?php
session_start();

// Incluir a classe de conexão
require_once '../../data/conector.php';

// Verificar se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../view/Sindico/novoPorteiro.php?erro=Método não permitido');
    exit;
}

// Obter dados do formulário
$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';
$confirmar_senha = $_POST['confirmar_senha'] ?? '';
$nome = trim($_POST['nome'] ?? '');

// Array de erros
$erros = [];

// Validações
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

// Se houver erros, redirecionar com mensagens
if (!empty($erros)) {
    $erro_msg = urlencode(implode(', ', $erros));
    header("Location: ../../view/Sindico/novoPorteiro.php?erro=$erro_msg");
    exit;
}

try {
    // Conectar ao banco de dados
    $conector = new Conector();
    $conexao = $conector->getConexao();

    // Verificar se o email já existe
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

    // Hash da senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // Iniciar transação
    $conexao->begin_transaction();

    // 1. Inserir dados na tabela Usuario com tipo 'Porteiro'
    $stmt = $conexao->prepare("INSERT INTO Usuario (email, senha_hash, tipo) VALUES (?, ?, 'Porteiro')");
    $stmt->bind_param("ss", $email, $senha_hash);
    $stmt->execute();
    $id_usuario = $conexao->insert_id;
    $stmt->close();

    // 2. Inserir dados na tabela Porteiro
    $stmt = $conexao->prepare("INSERT INTO Porteiro (id_usuario, nome) VALUES (?, ?)");
    $stmt->bind_param("is", $id_usuario, $nome);
    $stmt->execute();
    $stmt->close();

    // Confirmar transação
    $conexao->commit();

    // Redirecionar com mensagem de sucesso
    $sucesso_msg = urlencode('Porteiro criado com sucesso!');
    header("Location: ../../view/Sindico/novoPorteiro.php?success=$sucesso_msg");
    exit;

} catch (mysqli_sql_exception $e) {
    // Se houver erro, fazer rollback
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
?>
