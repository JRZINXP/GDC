<?php
session_start();
require_once __DIR__ . '/../../data/conector.php';

if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] !== 'Sindico') {
    header('Location: ../../login.php');
    exit;
}

$conexao = (new Conector())->getConexao();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome  = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!$nome || !$email || !$senha) {
        header('Location: ../../view/Sindico/porteiros.php?erro=Preencha todos os campos');
        exit;
    }

    try {
        $conexao->begin_transaction();

        $stmt = $conexao->prepare("SELECT id_usuario FROM Usuario WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Email já existe');
        }

        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $conexao->prepare("
            INSERT INTO Usuario (email, senha_hash, tipo)
            VALUES (?, ?, 'Porteiro')
        ");
        $stmt->bind_param("ss", $email, $hash);
        $stmt->execute();
        $idUsuario = $conexao->insert_id;


        $stmt = $conexao->prepare("
            INSERT INTO Porteiro (id_usuario, nome)
            VALUES (?, ?)
        ");
        $stmt->bind_param("is", $idUsuario, $nome);
        $stmt->execute();

        $conexao->commit();

        header('Location: ../../view/Sindico/porteiros.php?sucesso=Porteiro criado');
        exit;

    } catch (Exception $e) {
        $conexao->rollback();
        header('Location: ../../view/Sindico/porteiros.php?erro=' . urlencode($e->getMessage()));
        exit;
    }
}

if (!isset($_GET['action'], $_GET['id'])) {
    header('Location: ../../view/Sindico/porteiros.php');
    exit;
}

$action = $_GET['action'];
$idPorteiro = (int) $_GET['id'];

try {

    $stmt = $conexao->prepare("
        SELECT p.id_porteiro, p.id_usuario
        FROM Porteiro p
        WHERE p.id_porteiro = ?
    ");
    $stmt->bind_param("i", $idPorteiro);
    $stmt->execute();
    $porteiro = $stmt->get_result()->fetch_assoc();

    if (!$porteiro) {
        throw new Exception('Porteiro não encontrado');
    }

    if ($action === 'reset') {
        $hash = password_hash('porteiro123', PASSWORD_DEFAULT);
        $stmt = $conexao->prepare("UPDATE Usuario SET senha_hash=? WHERE id_usuario=?");
        $stmt->bind_param("si", $hash, $porteiro['id_usuario']);
        $stmt->execute();
    }

    if ($action === 'delete') {
        $conexao->begin_transaction();
        $stmt = $conexao->prepare("DELETE FROM Porteiro WHERE id_porteiro=?");
        $stmt->bind_param("i", $idPorteiro);
        $stmt->execute();

        $stmt = $conexao->prepare("DELETE FROM Usuario WHERE id_usuario=?");
        $stmt->bind_param("i", $porteiro['id_usuario']);
        $stmt->execute();
        $conexao->commit();
    }

    header('Location: ../../view/Sindico/porteiros.php');
    exit;

} catch (Exception $e) {
    $conexao->rollback();
    header('Location: ../../view/Sindico/porteiros.php?erro=' . urlencode($e->getMessage()));
    exit;
}
