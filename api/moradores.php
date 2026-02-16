<?php
require_once "./src/data/conector.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = $_POST["nome"] ?? "";
    $telefone = $_POST["telefone"] ?? "";
    $email = $_POST["email"] ?? "";
    $senha = $_POST["senha"] ?? "";
    $id_unidade = $_POST["unidade"] ?? null;

    if($nome=="" || $email=="" || $senha==""){
        echo json_encode(["status"=>"erro","msg"=>"Campos obrigatórios"]);
        exit;
    }

    // verificar email
    $check = $conn->prepare("SELECT id_usuario FROM Usuario WHERE email=?");
    $check->bind_param("s",$email);
    $check->execute();

    if($check->get_result()->num_rows > 0){
        echo json_encode(["status"=>"erro","msg"=>"Email já existe"]);
        exit;
    }

    if($id_unidade=="") $id_unidade = null;

    $conn->begin_transaction();

    try{

        $hash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            INSERT INTO Usuario(email,senha_hash,tipo)
            VALUES (?,?, 'Morador')
        ");
        $stmt->bind_param("ss",$email,$hash);
        $stmt->execute();

        $id_usuario = $conn->insert_id;

        $stmt2 = $conn->prepare("
            INSERT INTO Morador(id_usuario,id_unidade,nome,telefone)
            VALUES (?,?,?,?)
        ");
        $stmt2->bind_param("iiss",$id_usuario,$id_unidade,$nome,$telefone);
        $stmt2->execute();

        $conn->commit();

        echo json_encode([
            "status"=>"ok",
            "msg"=>"Morador cadastrado"
        ]);

    }catch(Exception $e){

        $conn->rollback();

        echo json_encode([
            "status"=>"erro",
            "msg"=>$e->getMessage()
        ]);
    }
}
