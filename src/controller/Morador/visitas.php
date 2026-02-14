<?php
session_start();
require_once __DIR__ . '/../../data/conector.php';

if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] !== 'Morador') {
    header('Location: ../../login.php');
    exit;
}

$conexao = (new Conector())->getConexao();

$stmt = $conexao->prepare("SELECT id_morador FROM Morador WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$morador = $stmt->get_result()->fetch_assoc();

if (!$morador) {
    header('Location: ../../view/morador/agendar_visita.php');
    exit;
}

$idMorador = $morador['id_morador'];
$acao = $_POST['acao'] ?? '';

date_default_timezone_set('Africa/Maputo');

if ($acao === 'cancelar') {

    $id = (int) $_POST['id_agendamento'];

    $stmt = $conexao->prepare("
        DELETE FROM Agendamento
        WHERE id_agendamento = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: ../../view/Morador/agendar_visita.php");
    exit;
}


if ($acao === 'editar') {

    $idAgendamento = (int)($_POST['id_agendamento'] ?? 0);
    $data = $_POST['data'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $motivo = trim($_POST['motivo'] ?? '');

    $dataAtual = date('Y-m-d');
    $horaAtual = date('H:i');

    $subMotivo = '';

    if ($motivo === 'visita_social') {
        $subMotivo = trim($_POST['social'] ?? '');
    } elseif ($motivo === 'prestacao_servico') {
        $subMotivo = trim($_POST['servico'] ?? '');
    } elseif ($motivo === 'administrativo') {
        $subMotivo = trim($_POST['administrativo'] ?? '');
    } elseif ($motivo === 'evento') {
        $subMotivo = trim($_POST['evento'] ?? '');
    }

    $motivoLabel = ucfirst(str_replace('_', ' ', $motivo));

    if ($subMotivo) {
        $motivo = $motivoLabel . ' – ' . ucfirst(str_replace('_', ' ', $subMotivo));
    } else {
        $motivo = $motivoLabel;
    }

    $stmt = $conexao->prepare("
        UPDATE Agendamento a
        LEFT JOIN Registro r ON r.id_agendamento = a.id_agendamento
        SET a.data = ?, a.hora = ?, a.motivo = ?
        WHERE a.id_agendamento = ?
        AND a.id_morador = ?
        AND r.entrada IS NULL
    ");
    $stmt->bind_param("sssii", $data, $hora, $motivo, $idAgendamento, $idMorador);
    $stmt->execute();

    header('Location: ../../view/morador/agendar_visita.php');
    exit;
}


if ($data < $dataAtual || ($data === $dataAtual && $hora < $horaAtual)) {
    $_SESSION['erro'] = "Não pode escolher data ou hora que já passou!";
    header('Location: ../../view/morador/agendar_visita.php');
    exit;
}


$nomeVisitante = trim($_POST['nome_visitante'] ?? '');
$data = $_POST['data'] ?? '';
$hora = $_POST['hora'] ?? '';
$motivo = trim($_POST['motivo'] ?? '');

if (!$nomeVisitante || !$data || !$hora || !$motivo) {
    header('Location: ../../view/morador/agendar_visita.php');
    exit;
}

if (!isset($_FILES['documento_imagem']) || $_FILES['documento_imagem']['error'] !== UPLOAD_ERR_OK) {
    header('Location: ../../view/morador/agendar_visita.php');
    exit;
}

$arquivo = $_FILES['documento_imagem'];
$extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
$permitidos = ['jpg', 'jpeg', 'png'];

if (!in_array($extensao, $permitidos)) {
    header('Location: ../../view/morador/agendar_visita.php');
    exit;
}

$pasta = __DIR__ . '/../../uploads/documents/';
if (!is_dir($pasta)) {
    mkdir($pasta, 0777, true);
}

$nomeArquivo = uniqid('doc_') . '.' . $extensao;
$caminhoFisico = $pasta . $nomeArquivo;
$caminhoBanco = 'uploads/documents/' . $nomeArquivo;

move_uploaded_file($arquivo['tmp_name'], $caminhoFisico);

$stmt = $conexao->prepare("INSERT INTO Visitante (nome, documento_imagem) VALUES (?, ?)");
$stmt->bind_param("ss", $nomeVisitante, $caminhoBanco);
$stmt->execute();

$idVisitante = $stmt->insert_id;

if ($data < $dataAtual || ($data === $dataAtual && $hora < $horaAtual)) {
    header('Location: ../../view/morador/agendar_visita.php');
    exit;
}

$subMotivo = '';

if ($motivo === 'visita_social') {
    $subMotivo = trim($_POST['social'] ?? '');
} elseif ($motivo === 'prestacao_servico') {
    $subMotivo = trim($_POST['servico'] ?? '');
} elseif ($motivo === 'administrativo') {
    $subMotivo = trim($_POST['administrativo'] ?? '');
} elseif ($motivo === 'evento') {
    $subMotivo = trim($_POST['evento'] ?? '');
}

$motivoLabel = ucfirst(str_replace('_', ' ', $motivo));

if ($subMotivo) {
    $motivo = $motivoLabel . ' – ' . ucfirst(str_replace('_', ' ', $subMotivo));
} else {
    $motivo = $motivoLabel;
}

$stmt = $conexao->prepare("
    INSERT INTO Agendamento (id_morador, id_visitante, data, hora, motivo)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param("iisss", $idMorador, $idVisitante, $data, $hora, $motivo);
$stmt->execute();

header('Location: ../../view/morador/agendar_visita.php');
exit;
