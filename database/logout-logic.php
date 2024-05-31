<?php
session_start(); // Start de sessie

// Alle sessievariabelen wissen
$_SESSION = array();

// Als je een sessie-cookie wilt vernietigen, moet je de sessie-cookie verwijderen
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Ten slotte vernietig je de sessie
session_destroy();

// Doorverwijzen naar de loginpagina of homepagina na logout
header('Location: ../index.php?message=logged_out');
exit;
?>
