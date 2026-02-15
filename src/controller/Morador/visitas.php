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

$nomeArquivo = uniqid('doc_') . '.' . $extensao;
$remotePath = "/Projetos/Escola/uploads/{$nomeArquivo}";

function encodeGraphPath($path) {
    $parts = array_map('rawurlencode', explode('/', trim($path, '/')));
    return '/' . implode('/', $parts);
}

function uploadToOneDrive($accessToken, $tmpPath, $remotePath) {
    $encodedPath = encodeGraphPath($remotePath);
    $url = "https://graph.microsoft.com/v1.0/me/drive/root:" . $encodedPath . ":/content";

    $fileContents = file_get_contents($tmpPath);
    if ($fileContents === false) {
        return null;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => $fileContents,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/octet-stream',
        ],
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);
    return $data['id'] ?? null;
}

if (!isset($_SESSION['access_token'])) {
    $_SESSION['erro'] = 'Conecte o OneDrive antes de enviar documentos.';
    header('Location: ../../view/morador/agendar_visita.php');
    exit;
}

$accessToken = $_SESSION['access_token'];

$itemId = uploadToOneDrive($accessToken, $arquivo['tmp_name'], $remotePath);

if (!$itemId) {
    $_SESSION['erro'] = 'Falha ao enviar o documento ao OneDrive.';
    header('Location: ../../view/morador/agendar_visita.php');
    exit;
}

$caminhoBanco = 'onedrive:' . $itemId;

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
