<?php
require_once '../config/config.php';
require_once 'session.php';

// Détruire la session
session_destroy();

// Rediriger vers la page de connexion
header('Location: login.php?logout=1');
exit;
?>