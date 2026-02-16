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

if($id_unidade=="") $id_unidade = null;

$conexao->begin_transaction();

try{

    $check = $conexao->prepare("SELECT id_usuario FROM Usuario WHERE email=?");
    $check->bind_param("s",$email);
    $check->execute();

    if($check->get_result()->num_rows > 0){
        echo json_encode(["status"=>"erro","msg"=>"Email já existe"]);
        exit;
    }

    $hash = password_hash($senha, PASSWORD_DEFAULT);

    $stmt = $conexao->prepare("
        INSERT INTO Usuario(email,senha_hash,tipo)
        VALUES (?,?, 'Morador')
    ");
    $stmt->bind_param("ss",$email,$hash);
    $stmt->execute();

    $id_usuario = $conexao->insert_id;

    $stmt2 = $conexao->prepare("
        INSERT INTO Morador(id_usuario,id_unidade,nome,telefone,activo)
        VALUES (?,?,?,?,1)
    ");
    $stmt2->bind_param("iiss",$id_usuario,$id_unidade,$nome,$telefone);
    $stmt2->execute();

    $id_sindico = $_SESSION['id'] ?? 0;
$nome_sindico = $_SESSION['nome'] ?? "Desconhecido";

$acao = "CADASTRO_MORADOR";
$descricao = "Síndico $nome_sindico cadastrou o morador $nome";

$stmt3 = $conexao->prepare("
    INSERT INTO logs (id_usuario, acao, descricao)
    VALUES (?,?,?)
");

$stmt3->bind_param("iss", $id_sindico, $acao, $descricao);
$stmt3->execute();

    $conexao->commit();

    echo json_encode([
        "status"=>"ok",
        "msg"=>"Morador cadastrado"
    ]);

}catch(Exception $e){

    $conexao->rollback();

    echo json_encode([
        "status"=>"erro",
        "msg"=>$e->getMessage() 
    ]);
}
