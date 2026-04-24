# Digitaler Schülerausweis (Google, Apple) mit WalletStudentID

Dieses Projekt stellt eine Schnittstelle zwischen der Schule/dem Schulverwaltungsprogramm und dem [WalletStudentID](https://gitlab.hhs.karlsruhe.de/digitale-schulverwaltung/walletstudentid) zur Ausgabe von Apple/Google-Wallet-Schülerausweisen dar.

Installation und Benutzeranleitung finden sich im [Wiki](https://gitlab.hhs.karlsruhe.de/digitale-schulverwaltung/student-id-portal/-/wikis/home).

Die folgenden Schaubilder stellen die unterstützten Vorgänge schematisch dar:

## Registrierung
Die Schülerausweise können über eine ID, welche etwa aus dem Schulverwaltungsprogramm automatisch erzeugt wird, registriert werden. Es sind keine vorbereitenden Import- und Re-Import-Läufe erforderlich:

![Registierung: 1. Abscannen QR-Code, 2. Auswahl Wallet-Typ, 3. Ausweis in Wallet](https://gitlab.hhs.karlsruhe.de/Seyfried/student-id-kortpress/-/wikis/uploads/4ae4d830696a7067f01ae05e869696f7/Registrierung.png)

## Verifizierung
Eine Überprüfung des Ausweises erfolgt über die Schulhomepage durch einen nachgelagerten Server, auf dem eine aktuelle Schülerliste aus dem Schulverwaltungsprogramm hinterlegt ist. Es werden hierbei keinerlei Schülerdaten über das Internet übertragen.

![Verifizierung: 1. Abscannen QR-Code Ausweis, 2. Bestätigungsseite auf Schul-Homepage](https://gitlab.hhs.karlsruhe.de/Seyfried/student-id-kortpress/-/wikis/uploads/a14fa7a27770a0d1e8d014108b9a3585/Verifizierung.png)

## Vorteile
Der Vorteil des Systems ist, dass nicht der ganze Datenbestand aller Schülerinnen und Schüler in WalletStudentID, Apple und Google importiert werden muss, sondern nur die Datensätze in abgerufen werden gelangen, deren Nutzer diesem ausdrücklich zugestimmt haben und die den digitalen Schülerausweis auch nutzen wollen.

Die verify-Funktion stellt einen weiteren Vorteil für eine bessere Akzeptanz der digitalen Schülerausweise dar. Wird der Ausweis-QR-Code gescannt, so erfolgt eine Gültigkeitsprüfung auf der Schulhomepage. Diese kann (je nach Aktualität des Datenexports aus dem Schulverwaltungsprogramm) tagesaktuell erfolgen.

## Architektur
Um möglichst keine Schülerdaten auf exponierten Servern lagern zu müssen, ist die Architektur des Registrierungssystems so aufgebaut, dass ein nachgelagerter Server die Schülerliste verwaltet und dem Frontend auf der Homepage lediglich Informationen über die Schulzugehörigkeit mitteilt. Ebenso erfolgt die Registrierung eines Wallet-Passes in WalletStudentID aus dem nachgelagerten System.

![Systemarchitektur](https://gitlab.hhs.karlsruhe.de/Seyfried/student-id-kortpress/-/wikis/uploads/f364f8768044c4841c524a7b73b89253/Architektur.svg)

*Performance:* ein erfolgreicher Verify-Aufruf benötigt 22ms ohne Webserver-Overhead. Verify-Aufrufe auf nicht existierende IDs werden per rate limit gedrosselt. Neue Registrierungs-Aufrufe sind durch den nachgelagerten Web-Request auf die WalletStudentID- und Google/Apple-Server langsamer. Registierungs-Aufrufe auf bereits ausgestellte Passes werden durch den Datenbank-Abruf auch innerhalb von 22ms bereit gestellt. Der Abruf der Download-URLs beinhaltet dann wieder einen nachgelagerten Web-Request.

---

## Installation

### Backend

Der Backend-Server (`internal/`) verwaltet die Schülerliste und stellt eine interne JSON-API für das Frontend bereit. Er sollte **nicht** öffentlich erreichbar sein.

1. Verzeichnis `internal/` auf dem Backend-Server ablegen (z. B. unter `/var/www/html/ID/internal/`).
2. `internal/config-sample.php` nach `internal/config.php` kopieren und anpassen:
   - `STUDENTS_CVS` — Pfad zur Schüler-CSV-Datei aus dem Schulverwaltungsprogramm
   - `WALLET_API_BASE`, `WALLET_API_KEY`, `WALLET_THEME_ID` — Zugangsdaten der WalletStudentID-Instanz
   - `VERIFY_BASE_URL` — öffentliche URL des Frontends (wird in die QR-Codes eingebettet)
   - `SCHOOL`, `SCHOOLYEAR_START`, `IMG_BASE_URL` — schulspezifische Einstellungen
3. Webserver so konfigurieren, dass alle Anfragen auf `internal/index.php` umgeleitet werden (`.htaccess` liegt bei).
4. PHP ≥ 8.1 mit `allow_url_fopen = On` erforderlich (für Upstream-Requests zur WalletStudentID-API).

### Frontend – Variante A: Standalone (Fat-Free Framework)

Geeignet, wenn das Frontend auf einem eigenen (Sub-)Pfad oder Subdomain betrieben wird, unabhängig von einem CMS.

1. Verzeichnis `external/` auf dem Webserver ablegen (z. B. unter `/var/www/html/ID/`).
2. `external/config-sample.php` nach `external/config.php` kopieren und anpassen:
   - `internal_server` — interne URL des Backend-Servers (nicht öffentlich zugänglich)
   - `BDAY_HMAC_SECRET` — zufälliges 32-Byte-Geheimnis, z. B. `openssl rand -hex 32`
   - `require_birthday`, `WALLET_USE_APPLE`, `WALLET_USE_GOOGLE` — Feature-Flags
3. Webserver so konfigurieren, dass alle Anfragen auf `external/index.php` umgeleitet werden (`.htaccess` liegt bei).
4. Das Frontend ist unter den konfigurierten Pfaden erreichbar, z. B.:
   - Verifikation: `https://schule.de/ID/v/{UUID}`
   - Registrierung: `https://schule.de/ID/r/{UUID}`

### Frontend – Variante B: WordPress-Plugin

Geeignet, wenn die Schulhomepage bereits auf WordPress läuft. Das Plugin integriert sich vollständig in das aktive WP-Theme.

1. **Symlink** aus dem WP-Plugin-Verzeichnis auf `wp-plugin/` im Repository setzen:
   ```bash
   ln -s /pfad/zum/repo/wp-plugin \
       /var/www/html/wordpress/wp-content/plugins/student-id-portal
   ```
   > Der Symlink `wp-plugin/common → ../common` muss im Repository vorhanden sein (wird beim Klonen automatisch miterstellt, sofern das Dateisystem Symlinks unterstützt).

2. Plugin in WordPress aktivieren: *wp-admin → Plugins → Student ID Portal → Aktivieren*.

3. Einstellungen unter *wp-admin → Einstellungen → Student ID Portal* vornehmen:
   - **Interner Server URL** — interne URL des Backend-Servers
   - **HMAC-Secret** — zufälliges 32-Byte-Geheimnis (wie oben)
   - **Schulname**, Feature-Flags (Apple/Google Wallet, Geburtsdatum erforderlich)
   - **URL-Prefixe** — Pfadsegmente für Schülerausweis (`ID`) und Lehrkraft-Nachweis (`LID`); nach Änderung einmal unter *Einstellungen → Permalinks* speichern.

4. Permalinks einmalig neu speichern (*Einstellungen → Permalinks → Speichern*), damit die Rewrite-Regeln registriert werden.

5. Das Frontend ist direkt auf der WordPress-Domain erreichbar, z. B.:
   - Verifikation: `https://schule.de/ID/v/{UUID}`
   - Registrierung: `https://schule.de/ID/r/{UUID}`
   - Lehrkraft: `https://schule.de/LID/v/{UUID}`