<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/dbcon.php';

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
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

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

    if ($user && password_verify($password, $user['password'])) {
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

        header('Location: usuarios.php');
        exit();
    }

    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'Correo o contraseña inválidos.'
    ];
    header('Location: login.php');
    exit();
}

if ($action === 'register') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidopaterno = trim($_POST['apellidopaterno'] ?? '');
    $apellidomaterno = trim($_POST['apellidomaterno'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($nombre === '' || $apellidopaterno === '' || $email === '' || $password === '' || $confirm_password === '') {
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

    if (strlen($password) < 8) {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'message' => 'La contraseña debe tener al menos 8 caracteres.'
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

    $hash = password_hash($password, PASSWORD_DEFAULT);
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
