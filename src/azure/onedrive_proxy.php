<?php

session_start();

$accessToken = $_SESSION['access_token'] ?? null;
if (!$accessToken) {
    http_response_code(401);
    exit('Sem token do OneDrive.');
}

$itemId = $_GET['id'] ?? '';
if (!$itemId) {
    http_response_code(400);
    exit('ID inválido.');
}

$metaUrl = "https://graph.microsoft.com/v1.0/me/drive/items/" . rawurlencode($itemId);

$ch = curl_init($metaUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $accessToken,
        'Accept: application/json',
    ],
]);

$response = curl_exec($ch);
if ($response === false) {
    http_response_code(500);
    exit('Erro ao obter metadata.');
}

$data = json_decode($response, true);
$downloadUrl = $data['@microsoft.graph.downloadUrl'] ?? null;

if (!$downloadUrl) {
    http_response_code(500);
    exit('Download URL inválida.');
}

header('Location: ' . $downloadUrl);
exit;