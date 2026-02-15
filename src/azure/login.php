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
$tenant = $env['Tenant'] ?? 'consumers';
$redirectUri = $env['RedirectURI'] ?? '';

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$scope = urlencode('offline_access Files.Read');

$authUrl =
    "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/authorize" .
    "?client_id={$clientId}" .
    "&response_type=code" .
    "&redirect_uri=" . urlencode($redirectUri) .
    "&response_mode=query" .
    "&scope={$scope}" .
    "&state={$state}";

header("Location: $authUrl");
exit;