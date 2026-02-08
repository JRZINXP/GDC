<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../data/conector.php';

if ($_SESSION['tipo_usuario'] !== 'Morador') {
    header('Location: ../../login.php');
    exit;
}

$conexao = (new Conector())->getConexao();

$tipo = $_POST['tipo'];
$titulo = trim($_POST['titulo']);
$descricao = trim($_POST['descricao']);

if (!$tipo || !$titulo || !$descricao) {
    header('Location: ../../view/morador/ocorrencias.php');
    exit;
}


$stmt = $conexao->prepare("
    SELECT id_morador FROM Morador WHERE id_usuario = ?
");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$idMorador = $stmt->get_result()->fetch_assoc()['id_morador'];

$stmt = $conexao->prepare("
    INSERT INTO Ocorrencia (id_morador, tipo, titulo, descricao)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("isss", $idMorador, $tipo, $titulo, $descricao);
$stmt->execute();

header('Location: ../../view/morador/ocorrencias.php');
exit;
