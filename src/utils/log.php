<?php
function registrarLog($conexao, $id_usuario, $nome_usuario, $acao, $descricao){

    $stmt = $conexao->prepare("
        INSERT INTO logs (id_usuario, nome_usuario, acao, descricao)
        VALUES (?,?,?,?)
    ");

    $stmt->bind_param("isss", $id_usuario, $nome_usuario, $acao, $descricao);
    $stmt->execute();
}
