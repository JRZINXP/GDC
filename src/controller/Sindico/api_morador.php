<?php
require_once __DIR__ . '/../../data/conector.php';
session_start();

header("Content-Type: application/json");

if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] !== 'Sindico') {
    echo json_encode(["status"=>"erro","msg"=>"Sem permissão"]);
    exit;
}

$conexao = (new Conector())->getConexao();

$nome = $_POST["nome"] ?? "";
$email = $_POST["email"] ?? "";
$senha = $_POST["senha"] ?? "";
$telefone = $_POST["telefone"] ?? "";
$id_unidade = $_POST["unidade"] ?? null;

if($nome=="" || $email=="" || $senha==""){
    echo json_encode(["status"=>"erro","msg"=>"Campos obrigatórios"]);
    exit;
}

$conexao->begin_transaction();

try{


    $hash = password_hash($senha, PASSWORD_DEFAULT);

    $stmt = $conexao->prepare("INSERT INTO Usuario(email,senha_hash,tipo,activo) VALUES (?,?, 'Morador',1)");
    $stmt->bind_param("ss",$email,$hash);
    $stmt->execute();

    $id_usuario = $conexao->insert_id;

    $stmt2 = $conexao->prepare("
        INSERT INTO Morador(id_usuario,id_unidade,nome,telefone,activo)
        VALUES (?,?,?,?,1)
    ");
    $stmt2->bind_param("iiss",$id_usuario,$id_unidade,$nome,$telefone);
    $stmt2->execute();

    $conexao->commit();

    echo json_encode([
        "status"=>"ok",
        "msg"=>"Morador cadastrado"
    ]);

}catch(Exception $e){
    $conexao->rollback();
    echo json_encode([
        "status"=>"erro",
        "msg"=>"Erro ao cadastrar"
    ]);
}
