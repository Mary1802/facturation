<?php
require_once 'config/config.php';
require_once 'auth/session.php';

// Rediriger vers la connexion si non connecté
if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit;
}

// Rediriger vers la page de facturation par défaut
header('Location: modules/facturation/nouvelle-facture.php');
exit;
?>