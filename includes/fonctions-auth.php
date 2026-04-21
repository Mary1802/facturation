<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Vérifie les identifiants d'un utilisateur
 */
function authenticateUser($username, $password) {
    $users = json_decode(file_get_contents(UTILISATEURS_FILE), true);

    if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
        return ['id' => $username, 'role' => $users[$username]['role'], 'name' => $users[$username]['name'] ?? $username];
    }

    $demoUsers = DEMO_USERS;
    if (isset($demoUsers[$username]) && $demoUsers[$username]['password'] === $password) {
        return ['id' => $username, 'role' => $demoUsers[$username]['role'], 'name' => $demoUsers[$username]['name']];
    }

    return false;
}

/**
 * Ajoute un nouvel utilisateur
 */
function addUser($username, $password, $role, $name = null) {
    $users = json_decode(file_get_contents(UTILISATEURS_FILE), true);

    if (isset($users[$username])) return false;

    $users[$username] = [
        'password' => password_hash($password, PASSWORD_BCRYPT),
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