<?php

require_once __DIR__ . '/../../data/conector.php';

$email = $_POST['email'];
$nome = $_POST['nome'];
$senha = $_POST['senha'];
$telefone = isset($_POST['telefone']) ? $_POST['telefone'] : null;
$id_unidade = isset($_POST['unidade']) ? $_POST['unidade'] : null;

$conector = new Conector();
$conexao = $conector->getConexao();

try {

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $tipo_usuario = 'Morador';
    
    $query_usuario = "INSERT INTO Usuario (email, senha_hash, tipo) VALUES (?, ?, ?)";
    $stmt_usuario = $conexao->prepare($query_usuario);
    $stmt_usuario->bind_param("sss", $email, $senha_hash, $tipo_usuario);
    
    if (!$stmt_usuario->execute()) {
        throw new Exception("Erro ao criar usuário: " . $stmt_usuario->error);
    }
    
    $id_usuario = $stmt_usuario->insert_id;

    $query_morador = "INSERT INTO Morador (id_usuario, id_unidade, nome, telefone) VALUES (?, ?, ?, ?)";
    $stmt_morador = $conexao->prepare($query_morador);
    $stmt_morador->bind_param("iiss", $id_usuario, $id_unidade, $nome, $telefone);
    
    if (!$stmt_morador->execute()) {
        throw new Exception("Erro ao criar morador: " . $stmt_morador->error);
    }
    
    $id_morador = $stmt_morador->insert_id;
    
    $_SESSION['mensagem'] = "Morador criado com sucesso!";
    $_SESSION['tipo_mensagem'] = "sucesso";
    header("Location: ../../view/Sindico/moradores.php");
    exit();
    
} catch (Exception $e) {
    $_SESSION['mensagem'] = $e->getMessage();
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: ../../view/Sindico/novoUser.php");
    exit();
}