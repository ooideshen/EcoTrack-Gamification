<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_login(): void {
    if (!current_user()) {
        $target = '/ecotrack/controllers/login.php';
        // Optionally keep where the user intended to go:
        $returnTo = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        header("Location: {$target}?returnTo={$returnTo}");
        exit;
    }
}

function require_role(string ...$roles): void {
    require_login();
    $user = current_user();
    if (!$user || !in_array($user['role'] ?? '', $roles, true)) {
        http_response_code(403);
        echo "Forbidden";
        exit;
    }
}

function login_user(array $userRow): void {
    $_SESSION['user'] = [
        'id'   => (int)$userRow['user_id'],
        'name' => $userRow['name'],
        'role' => $userRow['role'],
    ];

    $_SESSION['user_id'] = (int)$userRow['user_id'];
    $_SESSION['role']    = $userRow['role'];
    $_SESSION['name']    = $userRow['name'];
}

function logout(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"],
                  $params["secure"], $params["httponly"]);
    }
    session_destroy();
}