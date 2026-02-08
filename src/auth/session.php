<?php


session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['id'])) {

    $cookie_restaurada = false;
    foreach ($_COOKIE as $name => $value) {
        if (strpos($name, 'gdc_session_') === 0) {
            $data = json_decode($value, true);
            if (is_array($data) && isset($data['id_usuario'], $data['email'], $data['tipo'], $data['login_time'])) {
                $tempo_decorrido = time() - $data['login_time'];

                if ($tempo_decorrido < (24 * 60 * 60)) {

                    $_SESSION['id'] = $data['id_usuario'];
                    $_SESSION['email'] = $data['email'];
                    $_SESSION['tipo_usuario'] = $data['tipo'];
                    $_SESSION['login_time'] = $data['login_time'];
                    $_SESSION['session_timeout'] = 24 * 60 * 60;
                    $cookie_restaurada = true;
                    break;
                } else {

                    setcookie($name, '', time() - 3600, '/');
                }
            }
        }
    }
    
    if (!$cookie_restaurada) {
        header("Location: ../../login.php");
        exit();
    }
}
