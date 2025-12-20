****# 💻 Code Lab - Interaktive Programmier-Lernplattform

Eine vollständige Lernplattform für Programmier-Übungen mit PHP-Backend, Code-Editor, Syntax-Highlighting, Lehrer-Dashboard und Schüler-Tracking.

## 🎯 Features

### Für Schüler
- ✅ **10 Programmiersprachen**: HTML, CSS, JavaScript, Python, Java, Git, Lua, Scratch, MakeCode, MicroPython
- 📝 **Interaktiver Code-Editor** mit Syntax-Highlighting (CodeMirror)
- 🖥️ **Live-Konsole** zur Code-Ausführung (JavaScript) oder Simulation
- 💡 **Tipps-System** zur Unterstützung beim Lösen
- 📊 **Fortschritts-Tracking** mit Punktesystem
- 🌓 **Hell/Dunkel-Modus**
- 🌍 **Mehrsprachig**: Deutsch, Englisch, Spanisch

### Für Lehrer
- 📚 **Aufgaben erstellen** mit Rich-Text-Editor
- 📄 **Datei-Upload**: Plaintext, Markdown oder PDF
- 📱 **QR-Code-Generierung** zum einfachen Teilen
- 📊 **Dashboard** mit detaillierten Statistiken
- 📈 **Grafische Auswertungen**: Beste/Schlechteste Noten, Durchschnitt, Verteilung
- 👥 **Schüler-Verwaltung** und Aufgabenzuweisungen
- ⏰ **Deadlines** und Zeitlimits
- 💾 **Datenbank-Speicherung** aller Ergebnisse

## 🛠️ Technologie-Stack

**Frontend:**
- HTML5, CSS3, JavaScript (Vanilla)
- CodeMirror (Code-Editor)
- Chart.js (Statistiken)
- QRCode.js (QR-Codes)
- Marked.js (Markdown)
- PDF.js (PDF-Verarbeitung)

**Backend:**
- PHP 7.4+ mit PDO
- MySQL/MariaDB

## 📋 Installation

### Voraussetzungen
- Webserver (Apache/Nginx)
- PHP 7.4 oder höher
- MySQL 5.7+ oder MariaDB 10.2+
- mod_rewrite aktiviert (für Apache)

### Schritt 1: Repository klonen

```bash
git clone https://github.com/DEIN-USERNAME/ejercicios_didacticos.git
cd ejercicios_didacticos
```

### Schritt 2: Datenbank einrichten

1. MySQL/MariaDB-Datenbank erstellen:

```sql
CREATE DATABASE code_lab CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Schema importieren:

```bash
mysql -u root -p code_lab < database/schema.sql
```

Oder manuell in phpMyAdmin importieren.

### Schritt 3: Konfiguration anpassen

Bearbeiten Sie `api/config.php` und passen Sie die Datenbankverbindung an:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'code_lab');
define('DB_USER', 'IHR_DB_BENUTZERNAME');  // ÄNDERN!
define('DB_PASS', 'IHR_DB_PASSWORT');      // ÄNDERN!
define('BASE_URL', 'http://localhost');     // Für Produktion anpassen!
```

### Schritt 4: Berechtigungen setzen

```bash
# Upload-Verzeichnis erstellen
mkdir -p uploads
chmod 755 uploads

# API-Berechtigungen
chmod 644 api/*.php
```

### Schritt 5: Webserver konfigurieren

**Apache (mit .htaccess):**
Die .htaccess-Datei ist bereits vorhanden. Stellen Sie sicher, dass mod_rewrite aktiviert ist:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**Nginx:**
Fügen Sie zu Ihrer nginx.conf hinzu:

```nginx
location /api/ {
    try_files $uri $uri/ /api/index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

### Schritt 6: Testen

1. Öffnen Sie `http://localhost/ejercicios_didacticos/ejercicios/code_lab.html`
2. Registrieren Sie einen Demo-Account oder nutzen Sie:
   - **Lehrer**: `demo_teacher` / `teacher123`
   - **Schüler**: `demo_student` / `student123`

## 📁 Projektstruktur

```
ejercicios_didacticos/
├── ejercicios/
│   └── code_lab.html          # Haupt-Frontend-Datei
├── api/
│   ├── config.php             # Datenbank-Konfiguration
│   ├── auth.php               # Authentifizierung
│   ├── tasks.php              # Aufgaben-Management
│   └── results.php            # Ergebnisse & Statistiken
├── database/
│   └── schema.sql             # Datenbank-Schema
├── uploads/                   # Upload-Verzeichnis (erstellen!)
├── .htaccess                  # Apache-Konfiguration
└── CODE_LAB_README.md         # Diese Datei
```

## 🔧 Konfiguration

### API-Endpunkt anpassen

In `ejercicios/code_lab.html` (Zeile ~750):

```javascript
const API_BASE = '/api';  // Lokale Entwicklung
// const API_BASE = 'https://example.com/api';  // Produktion
```

### Sprache ändern

Standard-Sprache in `code_lab.html` ändern:

```javascript
let currentLanguage = 'de';  // 'de', 'en', oder 'es'
```

## 📊 Datenbank-Tabellen

| Tabelle | Beschreibung |
|---------|--------------|
| `users` | Benutzer (Schüler & Lehrer) |
| `tasks` | Aufgaben |
| `task_assignments` | Zuweisungen |
| `submissions` | Schüler-Lösungen |
| `activity_log` | Aktivitäts-Tracking |
| `sessions` | Session-Management |
| `classes` | Klassen/Gruppen (optional) |
| `class_members` | Klassen-Mitgliedschaften |

## 🎨 Anpassungen

### Theme anpassen

CSS-Variablen in `code_lab.html` anpassen:

```css
:root {
    --accent-primary: #3498db;      /* Primärfarbe */
    --accent-secondary: #2ecc71;    /* Erfolgsfarbe */
    --accent-danger: #e74c3c;       /* Fehlerfarbe */
}
```

### Neue Programmiersprache hinzufügen

1. In Datenbank-Schema (`schema.sql`) ENUM erweitern
2. In Frontend `language-picker` Option hinzufügen
3. CodeMirror-Mode hinzufügen (falls verfügbar)

## 🔒 Sicherheit

- ✅ Passwörter werden mit `bcrypt` gehasht
- ✅ Prepared Statements gegen SQL-Injection
- ✅ Session-basierte Authentifizierung
- ✅ CORS-Header konfigurierbar
- ✅ Input-Validierung auf Backend

**Produktions-Hinweise:**
- HTTPS verwenden!
- `display_errors = 0` in php.ini setzen
- Regelmäßige Backups der Datenbank
- Starke Passwörter für DB-Zugang

## 📱 QR-Code-Funktion

Lehrer können für jede Aufgabe einen QR-Code generieren. Schüler können diesen scannen und direkt zur Aufgabe gelangen.

**QR-Code-URL-Format:**
```
https://example.com/ejercicios/code_lab.html?task=SHARECODE
```

## 📈 Statistiken & Dashboard

Das Lehrer-Dashboard zeigt:
- 📊 Anzahl Aufgaben, Schüler, Einreichungen
- 📉 Durchschnittliche Punktzahl
- 🏆 Beste/Schlechteste Leistung
- 📅 Zeitverlauf der Einreichungen
- 👥 Rangliste der Schüler

## 🐛 Troubleshooting

### Problem: "Datenbankverbindung fehlgeschlagen"
- Prüfen Sie DB-Zugangsdaten in `api/config.php`
- MySQL-Service läuft: `sudo systemctl status mysql`

### Problem: "Keine Berechtigung"
- Dateirechte prüfen: `chmod 644 api/*.php`
- Upload-Verzeichnis: `chmod 755 uploads`

### Problem: API-Fehler 404
- mod_rewrite aktiviert?
- .htaccess wird gelesen? (`AllowOverride All`)

### Problem: Session ungültig
- Session-Tabelle in DB vorhanden?
- Browser-Cookies aktiviert?

## 🚀 Deployment (GitHub Pages + externem Server)

**Frontend (GitHub Pages):**
1. Push zu GitHub
2. Settings → Pages → Deploy from main
3. Frontend ist unter `https://USERNAME.github.io/ejercicios_didacticos/ejercicios/code_lab.html` verfügbar

**Backend (separater Server):**
1. PHP-Hosting (z.B. shared hosting, VPS)
2. Datenbank einrichten
3. API-Dateien hochladen
4. In `code_lab.html` API_BASE auf Server-URL setzen

## 📝 Lizenz

MIT License - Frei verwendbar für Bildungszwecke.

## 👨‍💻 Entwickler

Erstellt mit Claude Code für den Einsatz in Schulen und Bildungseinrichtungen.

## 🤝 Beitragen

Pull Requests sind willkommen! Für größere Änderungen bitte zuerst ein Issue öffnen.

## 📧 Support

Bei Fragen oder Problemen bitte ein GitHub Issue erstellen.

---

**Viel Erfolg beim Programmieren lernen! 🎓**
