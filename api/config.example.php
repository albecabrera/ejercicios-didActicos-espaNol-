<?php
/**
 * Code Lab - Beispiel-Konfiguration
 *
 * WICHTIG: Diese Datei kopieren und als config.php speichern!
 * Dann die Werte anpassen.
 */

// Datenbank-Konfiguration - ÄNDERN SIE DIESE WERTE!
define('DB_HOST', 'localhost');
define('DB_NAME', 'code_lab');
define('DB_USER', 'root');           // ÄNDERN!
define('DB_PASS', '');               // ÄNDERN!
define('DB_CHARSET', 'utf8mb4');

// Session-Konfiguration
define('SESSION_LIFETIME', 86400);   // 24 Stunden
define('SESSION_NAME', 'code_lab_session');

// Sicherheit
define('PASSWORD_MIN_LENGTH', 6);
define('MAX_LOGIN_ATTEMPTS', 5);
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5 MB

// Pfade - FÜR PRODUKTION ANPASSEN!
define('BASE_URL', 'http://localhost');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Rest der config.php hier einfügen...
