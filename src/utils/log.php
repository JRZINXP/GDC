<?php

function registrarLog($conexao, $id_usuario, $acao){
    $stmt = $conexao->prepare("
        INSERT INTO logs (id_usuario, acao)
        VALUES (?, ?)
    ");
    $stmt->bind_param("is", $id_usuario, $acao);
    $stmt->execute();
}
?>