<?php
/**
 * Database Configuration for Import System
 */

class Database {
    private static $connection = null;

    private static function parseEnvLine(string $line): ?array {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            return null;
        }

        if (str_starts_with($line, 'export ')) {
            $line = trim(substr($line, 7));
        }

        $pos = strpos($line, '=');
        if ($pos === false) {
            return null;
        }

        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        if ($key === '') {
            return null;
        }

        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $quote = $value[0];
            $value = substr($value, 1, -1);
            if ($quote === '"') {
                $value = str_replace(['\\n', '\\r', '\\t', '\\\\', '\\"'], ["\n", "\r", "\t", "\\", '"'], $value);
            }
        }

        return ['key' => $key, 'value' => $value];
    }

    private static function loadEnvFileValues(): array {
        $root = dirname(__DIR__);
        $appEnv = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'dev';

        $files = [
            $root . '/.env',
            $root . '/.env.local',
            $root . '/.env.' . $appEnv,
            $root . '/.env.' . $appEnv . '.local',
        ];

        $vars = [];
        foreach ($files as $filePath) {
            if (!is_file($filePath) || !is_readable($filePath)) {
                continue;
            }
            $lines = file($filePath, FILE_IGNORE_NEW_LINES);
            if (!is_array($lines)) {
                continue;
            }
            foreach ($lines as $line) {
                $parsed = self::parseEnvLine($line);
                if ($parsed === null) {
                    continue;
                }
                $vars[$parsed['key']] = $parsed['value'];
            }
        }

        return $vars;
    }

    private static function envValue(string $key, array $fileVars, string $default = ''): string {
        $value = $_SERVER[$key] ?? $_ENV[$key] ?? getenv($key);
        if ($value !== false && $value !== null && $value !== '') {
            return (string)$value;
        }
        if (array_key_exists($key, $fileVars) && $fileVars[$key] !== '') {
            return (string)$fileVars[$key];
        }
        return $default;
    }

    private static function buildConfigFromUrl(?string $databaseUrl): ?array {
        $databaseUrl = trim((string)$databaseUrl);
        if ($databaseUrl === '') {
            return null;
        }

        $databaseUrl = trim($databaseUrl, "\"'");
        $parsedUrl = parse_url($databaseUrl);
        if ($parsedUrl === false) {
            return null;
        }

        return [
            'host' => $parsedUrl['host'] ?? 'localhost',
            'port' => isset($parsedUrl['port']) ? (int)$parsedUrl['port'] : 3306,
            'dbname' => rawurldecode(ltrim($parsedUrl['path'] ?? '/shopware678', '/')),
            'username' => rawurldecode($parsedUrl['user'] ?? 'root'),
            'password' => rawurldecode($parsedUrl['pass'] ?? ''),
        ];
    }

    private static function readDatabaseConfig(): array {
        $host = 'localhost';
        $port = 3306;
        $dbname = 'shopware678';
        $username = 'root';
        $password = '';

        $fileVars = self::loadEnvFileValues();

        $databaseUrl = self::envValue('DATABASE_URL', $fileVars, '');

        $cfg = self::buildConfigFromUrl($databaseUrl);

        if ($cfg !== null) {
            $host = $cfg['host'];
            $port = $cfg['port'];
            $dbname = $cfg['dbname'];
            $username = $cfg['username'];
            $password = $cfg['password'];
        }

        $host = self::envValue('IMPORT_DB_HOST', $fileVars, $host);
        $port = (int)self::envValue('IMPORT_DB_PORT', $fileVars, (string)$port);
        $dbname = self::envValue('IMPORT_DB_NAME', $fileVars, $dbname);
        $username = self::envValue('IMPORT_DB_USER', $fileVars, $username);
        $password = self::envValue('IMPORT_DB_PASS', $fileVars, $password);

        return [
            'host' => $host,
            'port' => $port,
            'dbname' => $dbname,
            'username' => $username,
            'password' => $password,
        ];
    }
    
    public static function getConnection() {
        if (self::$connection === null) {
            try {
                $config = self::readDatabaseConfig();
                $host = $config['host'];
                $port = $config['port'];
                $dbname = $config['dbname'];
                $username = $config['username'];
                $password = $config['password'];
                
                // Create PDO connection
                $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
                self::$connection = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                
                // Create table if not exists
                self::createTableIfNotExists();
                
            } catch (PDOException $e) {
                $errorMsg = "Database connection failed: " . $e->getMessage();
                error_log($errorMsg);
                // For debugging, show the actual error in development
                throw new Exception($errorMsg . " (Host: $host, DB: $dbname, User: $username)");
            }
        }
        
        return self::$connection;
    }
    
    public static function fetchOne($sql, $params = []) {
        $stmt = self::$connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
    
    private static function createTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS `vendor_import_jobs` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `job_id` VARCHAR(50) NOT NULL UNIQUE,
          `vendor_name` VARCHAR(100) DEFAULT NULL,
          `import_type` ENUM('product', 'images', 'tierprice') NOT NULL,
          `file_name` VARCHAR(255) NOT NULL,
          `file_path` VARCHAR(500) NOT NULL,
          `batch_size` INT(11) DEFAULT 25,
          `total_rows` INT(11) DEFAULT 0,
          `processed_rows` INT(11) DEFAULT 0,
          `error_rows` INT(11) DEFAULT 0,
          `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
          `category_mapping` TEXT DEFAULT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `started_at` DATETIME DEFAULT NULL,
          `completed_at` DATETIME DEFAULT NULL,
          `created_by` VARCHAR(100) DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_job_id` (`job_id`),
          KEY `idx_import_type` (`import_type`),
          KEY `idx_status` (`status`),
          KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            self::$connection->exec($sql);
        } catch (PDOException $e) {
            error_log("Table creation failed: " . $e->getMessage());
        }
    }
}
