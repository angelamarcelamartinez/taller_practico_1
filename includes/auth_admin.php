<?php
// Asegurar que la sesión esté inicializada antes de validar 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    
    session_unset();
    session_destroy();
    
    header('Location: login.php');
    exit;   
}
?>