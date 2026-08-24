<?php
declare(strict_types=1);

require_once CONFIG_PATH . '/database.php';

class Settings {
    private static array $cache = [];
    private static bool $loaded = false;

    public static function load(): void {
        if (self::$loaded) return;

        $db = Database::getConnection();
        $result = $db->query("SELECT key_name, value, is_serialized FROM settings");
        
        while ($row = $result->fetch_assoc()) {
            if ((int)$row['is_serialized'] === 1) {
                self::$cache[$row['key_name']] = json_decode((string)$row['value'], true) ?? [];
            } else {
                self::$cache[$row['key_name']] = $row['value'];
            }
        }
        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed {
        self::load();
        return self::$cache[$key] ?? $default;
    }
}

function get_setting(string $key, mixed $default = ''): mixed {
    return Settings::get($key, $default);
}