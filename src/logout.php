<?php
session_start();

if (isset($_GET['logout'])) {

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    foreach ($_COOKIE as $name => $value) {
        if (strpos($name, 'gdc_session_') === 0) {
            setcookie($name, '', time() - 3600, '/');
        }
    }

    session_unset();
    session_destroy();

    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Location: login.php");
    exit;
}
