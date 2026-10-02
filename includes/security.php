<?php

function configureWebSecurity(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');
        $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower((string)$_SERVER['HTTPS']) !== 'off';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    if (!headers_sent()) {
        header_remove('X-Powered-By');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
            header('Strict-Transport-Security: max-age=31536000');
        }
    }
}

function csrfToken(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('La sesión debe iniciarse antes de generar el token CSRF.');
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function passwordMeetsPolicy(mixed $password): bool
{
    return is_string($password)
        && strlen($password) >= 8
        && preg_match('/[a-z]/', $password) === 1
        && preg_match('/[A-Z]/', $password) === 1
        && preg_match('/[0-9]/', $password) === 1;
}

function requireValidCsrfToken(): void
{
    $submittedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (!is_string($submittedToken) || !is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
        http_response_code(403);
        exit('Solicitud no autorizada.');
    }
}

function requireUserRole(array $allowedRoles): void
{
    $currentRole = (int)($_SESSION['user_role'] ?? 0);
    $allowedRoles = array_map('intval', $allowedRoles);

    if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['username']) || !in_array($currentRole, $allowedRoles, true)) {
        http_response_code(403);
        exit('Acceso denegado.');
    }
}

function authenticatedHomePath(): string
{
    return match ((int)($_SESSION['user_role'] ?? 0)) {
        1 => 'usuarios.php',
        2 => 'carga-tienda-en-linea.php',
        3 => 'tienda-en-linea.php',
        default => 'login.php',
    };
}

function isLoginLocked(PDO $pdo, string $username): bool
{
    $statement = $pdo->prepare('SELECT 1 FROM login_attempts WHERE username = ? AND locked_until > UTC_TIMESTAMP() LIMIT 1');
    $statement->execute([$username]);
    return $statement->fetchColumn() !== false;
}

function recordFailedLogin(PDO $pdo, string $username): bool
{
    $pdo->beginTransaction();
    try {
        $insert = $pdo->prepare('INSERT IGNORE INTO login_attempts (username, failed_attempts) VALUES (?, 0)');
        $insert->execute([$username]);

        $select = $pdo->prepare('SELECT failed_attempts, locked_until FROM login_attempts WHERE username = ? FOR UPDATE');
        $select->execute([$username]);
        $attempt = $select->fetch(PDO::FETCH_ASSOC);
        $lockedUntil = $attempt['locked_until'] ?? null;

        if ($lockedUntil !== null && strtotime($lockedUntil . ' UTC') > time()) {
            $pdo->commit();
            return true;
        }

        $expiredLock = $lockedUntil !== null;
        $failedAttempts = $expiredLock ? 1 : (int)$attempt['failed_attempts'] + 1;
        $isLocked = $failedAttempts >= 3;
        $update = $pdo->prepare('UPDATE login_attempts SET failed_attempts = ?, locked_until = IF(?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 5 MINUTE), NULL) WHERE username = ?');
        $update->execute([$failedAttempts, $isLocked ? 1 : 0, $username]);
        $pdo->commit();
        return $isLocked;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function clearLoginAttempts(PDO $pdo, string $username): void
{
    $statement = $pdo->prepare('DELETE FROM login_attempts WHERE username = ?');
    $statement->execute([$username]);
}

configureWebSecurity();