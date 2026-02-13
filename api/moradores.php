<?php
require_once "../config/conexao.php"; // usa o teu ficheiro real

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = $_POST["nome"] ?? "";
    $telefone = $_POST["telefone"] ?? "";
    $email = $_POST["email"] ?? "";
    $senha = $_POST["senha"] ?? "";
    $id_unidade = $_POST["id_unidade"] ?? "";

    if($nome=="" || $email=="" || $senha==""){
        echo json_encode(["status"=>"erro","msg"=>"Campos obrigatórios"]);
        exit;
    }

    $conn->begin_transaction();

    try{

        // criar usuario
        $hash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO Usuario(email,senha_hash,tipo) VALUES (?,?, 'Morador')");
        $stmt->bind_param("ss",$email,$hash);
        $stmt->execute();

        $id_usuario = $conn->insert_id;

        // criar morador
        $stmt2 = $conn->prepare("INSERT INTO Morador(id_usuario,id_unidade,nome,telefone) VALUES (?,?,?,?)");
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
            "msg"=>"Erro ao cadastrar"
        ]);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $sql = "SELECT m.nome, m.telefone, u.numero 
            FROM Morador m
            LEFT JOIN Unidade u ON m.id_unidade = u.id_unidade
            ORDER BY m.id_morador DESC";

    $res = $conn->query($sql);

    $dados = [];

    while($row = $res->fetch_assoc()){
        $dados[] = $row;
    }

    echo json_encode($dados);
}
