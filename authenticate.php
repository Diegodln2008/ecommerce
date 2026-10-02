<?php
require_once __DIR__ . '/includes/security.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/dbcon.php';
require_once __DIR__ . '/includes/data-protection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$action = $_GET['action'] ?? null;

function get_env(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value !== false && $value !== null) {
        return $value;
    }
    return $_ENV[$key] ?? $default;
}

function send_system_email(string $to, string $toName, string $subject, string $htmlBody, string $altBody = ''): bool
{
    $smtpHost = get_env('SMTP_HOST');
    $smtpPort = (int)get_env('SMTP_PORT', '587');
    $fromEmail = get_env('EMAIL_SMTP');
    $fromName = get_env('EMAIL_FROM_NAME', 'Mi Empresa');
    $smtpPassword = get_env('PASSWORD_SMTP');

    if ($smtpHost === '' || $fromEmail === '' || $smtpPassword === '') {
        error_log('SMTP configuration missing: host=' . ($smtpHost ?: 'null') . ', from=' . ($fromEmail ?: 'null') . ', pass=' . ($smtpPassword !== '' ? 'set' : 'empty'));
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->Port = $smtpPort;
        $mail->SMTPAuth = true;
        $mail->Username = $fromEmail;
        $mail->Password = $smtpPassword;
        $mail->SMTPAutoTLS = true;
        $mail->SMTPSecure = $smtpPort === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to, $toName);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $htmlBody;
        $mail->AltBody = $altBody ?: strip_tags($htmlBody);
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = function ($str, $level) {
            error_log('PHPMailer debug [' . $level . ']: ' . $str);
        };
        return $mail->send();
    } catch (Exception $e) {
        error_log('PHPMailer error: ' . $e->getMessage());
        return false;
    }
}

if ($action === 'login') {
    requireValidCsrfToken();
    $emailInput = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = is_string($emailInput) ? strtolower(trim($emailInput)) : '';
    $password = is_string($password) ? $password : '';

    if ($email === '' || $password === '') {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'message' => 'Completa todos los campos.'
        ];
        header('Location: login.php');
        exit();
    }

    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE username = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (isLoginLocked($pdo, $email)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Cuenta temporalmente bloqueada. Intenta de nuevo en 5 minutos.'
        ];
        header('Location: login.php');
        exit();
    }

    $storedPassword = (string)($user['password'] ?? '');
    $passwordIsValid = $user && password_verify($password, $storedPassword);
    $isLegacyPassword = $user && !$passwordIsValid && (
        hash_equals($storedPassword, md5($password)) || hash_equals($storedPassword, $password)
    );

    if ($user && ($passwordIsValid || $isLegacyPassword)) {
        clearLoginAttempts($pdo, $email);

        if ($isLegacyPassword || password_needs_rehash($storedPassword, PASSWORD_BCRYPT, ['cost' => 12])) {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $updatePassword = $pdo->prepare('UPDATE usuarios SET password = ? WHERE id = ?');
            $updatePassword->execute([$newHash, $user['id']]);
        }

        session_regenerate_id(true);
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nombre'];
        $_SESSION['user_role'] = $user['rol'];

        $emailSent = send_system_email(
            $user['username'],
            $user['nombre'],
            'Inicio de sesión detectado',
            '<p>Hola ' . htmlspecialchars($user['nombre']) . ',</p><p>Se ha detectado un inicio de sesión en tu cuenta.</p><p>Si no fuiste tú, cambia tu contraseña inmediatamente.</p>',
            'Hola ' . $user['nombre'] . ',\nSe ha detectado un inicio de sesión en tu cuenta. Si no fuiste tú, cambia tu contraseña inmediatamente.'
        );

        if (! $emailSent) {
            error_log('Login email not sent to ' . $user['username']);
        }

        header('Location: ' . authenticatedHomePath());
        exit();
    }

    if ($user) {
        recordFailedLogin($pdo, $email);
    }

    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'Correo o contraseña inválidos.'
    ];
    header('Location: login.php');
    exit();
}

if ($action === 'register') {
    requireValidCsrfToken();
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidopaterno = trim($_POST['apellidopaterno'] ?? '');
    $apellidomaterno = trim($_POST['apellidomaterno'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $acceptPolicy = ($_POST['accept_policy'] ?? '') === '1';

    if ($nombre === '' || $apellidopaterno === '' || $email === '' || $password === '' || $confirm_password === '' || !$acceptPolicy) {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'message' => 'Completa todos los campos.'
        ];
        header('Location: register.php');
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'message' => 'Las contraseñas no coinciden.'
        ];
        header('Location: register.php');
        exit();
    }

    if (!passwordMeetsPolicy($password)) {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'message' => 'Usa al menos 8 caracteres, una minúscula, una mayúscula y un número.'
        ];
        header('Location: register.php');
        exit();
    }

    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE username = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Este correo ya está registrado.'
        ];
        header('Location: register.php');
        exit();
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $rol = 3;
    $estatus = 1;

    $insert = $pdo->prepare('INSERT INTO usuarios (nombre, apellidopaterno, apellidomaterno, username, password, rol, estatus) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $created = $insert->execute([$nombre, $apellidopaterno, $apellidomaterno, $email, $hash, $rol, $estatus]);

    if ($created) {
        $emailSent = send_system_email(
            $email,
            $nombre,
            'Registro exitoso en Ecommerce',
            '<p>Hola ' . htmlspecialchars($nombre) . ',</p><p>Tu cuenta se ha creado correctamente.</p><p>Ahora puedes iniciar sesión con tu correo electrónico.</p>',
            'Hola ' . $nombre . '\nTu cuenta se ha creado correctamente. Ahora puedes iniciar sesión con tu correo electrónico.'
        );

        if (! $emailSent) {
            error_log('Registration email not sent to ' . $email);
        }

        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'Registro exitoso. Ya puedes iniciar sesión.'
        ];
        header('Location: login.php');
        exit();
    }

    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'Error al registrar. Intenta de nuevo.'
    ];
    header('Location: register.php');
    exit();
}

if ($action === 'logout') {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

header('Location: login.php');
exit();
