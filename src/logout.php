<?php
session_start();

require_once __DIR__ . '/data/conector.php';

require_once __DIR__ . '/utils/log.php';

if (isset($_GET['logout'])) {

    $id   = $_SESSION['id'] ?? null;
    $nome = $_SESSION['nome'] ?? $_SESSION['email'] ?? "Utilizador";

    if ($id) {
        $conexao = (new Conector())->getConexao();

        registrarLog(
            $conexao,
            $id,
            $nome,
            "LOGOUT",
            "Saiu do sistema"
        );
    }

    session_destroy();
    header("Location: login.php");
    exit();
}

