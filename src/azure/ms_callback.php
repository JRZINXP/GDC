<?php
session_start();

function loadEnv($path) {
    $vars = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $vars[$k] = $v;
    }
    return $vars;
}

$env = loadEnv(__DIR__ . '/../../.env');

$clientId = $env['ClientId'] ?? '';
$clientSecret = $env['ClientSecret'] ?? '';
$tenant = $env['Tenant'] ?? 'consumers';
$redirectUri = $env['RedirectURI'] ?? '';

if (!isset($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? null)) {
    http_response_code(400);
    exit('Estado inválido.');
}

$code = $_GET['code'] ?? null;
if (!$code) {
    http_response_code(400);
    exit('Código não encontrado.');
}

$tokenUrl = "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token";

$postData = http_build_query([
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $redirectUri,
    'scope' => 'offline_access Files.Read',
]);

$ch = curl_init($tokenUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
]);

$response = curl_exec($ch);
if ($response === false) {
    http_response_code(500);
    exit('Erro ao obter token.');
}

$data = json_decode($response, true);
if (!isset($data['access_token'])) {
    http_response_code(500);
    exit('Token inválido.');
}

$_SESSION['access_token'] = $data['access_token'];

header('Location: /GDC/src/View/Morador/agendar_visita.php');
exit;