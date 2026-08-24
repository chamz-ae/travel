<?php
declare(strict_types=1);

require_once CONFIG_PATH . '/constants.php';

class I18n {
    private static string $currentLocale = DEFAULT_LOCALE;
    private static array $dictionary = [];

    public static function setLocale(string $locale): void {
        self::$currentLocale = in_array($locale, SUPPORTED_LOCALES, true) ? $locale : DEFAULT_LOCALE;
        self::loadDictionary();
    }

    public static function getLocale(): string {
        return self::$currentLocale;
    }

    private static function loadDictionary(): void {
        // 1. Muat default fallback (id.json)
        $defaultFile = LANGUAGES_PATH . '/' . DEFAULT_LOCALE . '.json';
        $defaultDict = file_exists($defaultFile) ? (json_decode(file_get_contents($defaultFile), true) ?? []) : [];

        // 2. Muat target locale jika bukan default
        if (self::$currentLocale !== DEFAULT_LOCALE) {
            $targetFile = LANGUAGES_PATH . '/' . self::$currentLocale . '.json';
            $targetDict = file_exists($targetFile) ? (json_decode(file_get_contents($targetFile), true) ?? []) : [];
            self::$dictionary = array_replace_recursive($defaultDict, $targetDict);
        } else {
            self::$dictionary = $defaultDict;
        }
    }

    public static function trans(string $key, array $replace = []): string {
        $keys = explode('.', $key);
        $value = self::$dictionary;

        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $key;
            }
        }

        if (is_string($value)) {
            foreach ($replace as $p => $r) {
                $value = str_replace(':' . $p, (string)$r, $value);
            }
            return $value;
        }

        return $key;
    }
}

function __(string $key, array $replace = []): string {
    return Security::e(I18n::trans($key, $replace));
}