<?php
session_start();
require_once __DIR__ . '/../../data/conector.php';

if (
    !isset($_SESSION['id']) ||
    !isset($_SESSION['tipo_usuario']) ||
    $_SESSION['tipo_usuario'] !== 'Morador'
) {
    header('Location: ../../login.php');
    exit;
}

$conexao = (new Conector())->getConexao();

// Buscar morador
$stmt = $conexao->prepare("SELECT id_morador FROM Morador WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$morador = $stmt->get_result()->fetch_assoc();

if (!$morador) {
    $_SESSION['mensagem'] = 'Morador não encontrado.';
    $_SESSION['tipo_mensagem'] = 'erro';
    header('Location: ../../view/morador/agendar_visita.php');
    exit;
}

$idMorador = $morador['id_morador'];

// Dados do formulário
$nomeVisitante = trim($_POST['nome_visitante']);
$documento     = trim($_POST['documento']);
$data          = $_POST['data'];
$hora          = $_POST['hora'];

if (!$nomeVisitante || !$documento || !$data || !$hora) {
    $_SESSION['mensagem'] = 'Preencha todos os campos.';
    $_SESSION['tipo_mensagem'] = 'erro';
    header('Location: ../../view/morador/agendar_visita.php');
    exit;
}

// Inserir visitante
$stmt = $conexao->prepare(
    "INSERT INTO Visitante (nome, documento) VALUES (?, ?)"
);
$stmt->bind_param("ss", $nomeVisitante, $documento);
$stmt->execute();

$idVisitante = $stmt->insert_id;

// Inserir agendamento
$stmt = $conexao->prepare(
    "INSERT INTO Agendamento (id_morador, id_visitante, data, hora)
     VALUES (?, ?, ?, ?)"
);
$stmt->bind_param("iiss", $idMorador, $idVisitante, $data, $hora);
$stmt->execute();

$_SESSION['mensagem'] = 'Visita agendada com sucesso!';
$_SESSION['tipo_mensagem'] = 'sucesso';

header('Location: ../../view/morador/agendar_visita.php');
exit;
