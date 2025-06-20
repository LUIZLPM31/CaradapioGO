<?php
require_once '../includes/config.php';

// Destruir sessão
session_destroy();

// Redirecionar para login
redirect('login.php');
?>

