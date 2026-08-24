<?php
declare(strict_types=1);

require_once CONFIG_PATH . '/constants.php';
require_once CONFIG_PATH . '/database.php';
require_once CONFIG_PATH . '/security.php';

class Auth {
    public static function check(): bool {
        return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }

    public static function requireAuth(): void {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/admin/login.php');
            exit;
        }
    }

    public static function user(): ?array {
        if (!self::check()) return null;
        return [
            'id'        => $_SESSION['admin_id'] ?? null,
            'username'  => $_SESSION['admin_username'] ?? '',
            'full_name' => $_SESSION['admin_name'] ?? '',
            'role'      => $_SESSION['admin_role'] ?? 'editor'
        ];
    }

    public static function attempt(string $username, string $password): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, username, password_hash, full_name, role, is_active FROM admins WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && (int)$user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
            // Prevent Session Fixation
            session_regenerate_id(true);

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = (int)$user['id'];
            $_SESSION['admin_username']  = $user['username'];
            $_SESSION['admin_name']      = $user['full_name'];
            $_SESSION['admin_role']      = $user['role'];

            // Update timestamp login terakhir
            $updateStmt = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
            $updateStmt->bind_param('i', $user['id']);
            $updateStmt->execute();

            return true;
        }
        return false;
    }

    public static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }
}