<?php
require_once '../config/config.php';

/**
 * Vérifie les identifiants d'un utilisateur
 */
function authenticateUser($username, $password) {
    // Charger les utilisateurs du fichier JSON
    $users = json_decode(file_get_contents(UTILISATEURS_FILE), true);

    if (isset($users[$username]) && $users[$username]['password'] === $password) {
        return [
            'id' => $username,
            'role' => $users[$username]['role'],
            'name' => $users[$username]['name'] ?? $username
        ];
    }

    // Vérifier les comptes de démonstration
    $demoUsers = DEMO_USERS;
    if (isset($demoUsers[$username]) && $demoUsers[$username]['password'] === $password) {
        return [
            'id' => $username,
            'role' => $demoUsers[$username]['role'],
            'name' => $demoUsers[$username]['name']
        ];
    }

    return false;
}

/**
 * Ajoute un nouvel utilisateur
 */
function addUser($username, $password, $role, $name = null) {
    $users = json_decode(file_get_contents(UTILISATEURS_FILE), true);

    if (isset($users[$username])) {
        return false; // Utilisateur existe déjà
    }

    $users[$username] = [
        'password' => $password,
        'role' => $role,
        'name' => $name ?? $username,
        'created_at' => date('Y-m-d H:i:s')
    ];

    return file_put_contents(UTILISATEURS_FILE, json_encode($users, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Supprime un utilisateur
 */
function deleteUser($username) {
    $users = json_decode(file_get_contents(UTILISATEURS_FILE), true);

    if (!isset($users[$username])) {
        return false; // Utilisateur n'existe pas
    }

    unset($users[$username]);

    return file_put_contents(UTILISATEURS_FILE, json_encode($users, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Récupère la liste des utilisateurs
 */
function getUsers() {
    return json_decode(file_get_contents(UTILISATEURS_FILE), true);
}

/**
 * Vérifie si un rôle est valide
 */
function isValidRole($role) {
    return array_key_exists($role, ROLES);
}
?>