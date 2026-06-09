Die Benutzeranleitung finden Sie [hier](USERDOCS.md).

# Installation
Zum Verständnis ist es wichtig, die zweigeteilte Architektur des Systems zu kennen (**interne Zone** mit sensiblen Schülerdaten und **DMZ**, welche die Zugriffe auf das Wallet-System regelt):

![Architektur.svg](Architektur.svg)

## Voraussetzungen
- zwei Webserver mit installiertem PHP-Support (mindestens Version 8.1)
- aktiviertes mod_rewrite auf beiden Servern
- installiertes sqlite3-Paket auf dem internen Server. Andere DBMS sind möglich, derzeit aber nicht unterstützt.
- Systemvoraussetzungen des Fat-free-PHP-Framework, siehe [hier](https://fatfreeframework.com/3.8/system-requirements)

_Es besteht die Möglichkeit, beide Serverteile auch auf einem einzelnen Webserver zu deployen. Dabei muss besonderes Augenmerk auf die Absicherung des internen Teils gelegt werden (z.B. Zugriff und Zugang nur über localhost) und insbesondere sichergestellt werden, dass die Schülerdatei keinesfalls im Zugriffsbereich des Webservers liegt. Diese Vorgehensweise wird hier nicht näher beschrieben, da sie nur gewählt werden sollte, wenn man genau weiß, was man tut (und dann benötigt man hierfür auch keine Anleitung...)_

---


## Installation

## Installation auf beiden Webservern
Das Paket sollte auf beiden beteiligten Servern in einem geeigneten Ordner im Webroot heruntergeladen werden:

```shell
cd /var/www/html/ID # Beispiel-Verzeichnis, anpassen!
git clone https://gitlab.hhs.karlsruhe.de/Seyfried/student-id-kortpress.git .
```

Die beiden Ordner ```internal```und ```external``` enthalten die Dateistrukturen für den internen bzw. externen Server. Um möglichst einfache Updates zu ermöglichen, besteht die Möglichkeit, die Dateien über Softlinks zugänglich zu machen:
```shell
ln -s external/* .
```
(und für `internal` auf dem internen Server analog). Den Ordner "internal" kann man im Webroot liegen lassen, dieser stellt kein Sicherheitsrisiko dar, solange keine Schülerdaten im Verzeichnis liegen. 

Alternativ kann man die Dateien auch per `mv` aus den beiden Unterordnern herausschieben und den jeweils nicht benötigten Ordner löschen. Dabei ist darauf zu achten, dass auf beiden Servern der lib-Ordner mit dem benötigten PHP-Framework vorliegt.

### Backend

Der Backend-Server (`internal/`) verwaltet die Schülerliste und stellt eine interne JSON-API für das Frontend bereit. Er sollte **nicht** öffentlich erreichbar sein.

1. Verzeichnis `internal/` auf dem Backend-Server ablegen (z. B. unter `/var/www/html/ID/internal/`).
2. [`internal/config-sample.php`](../internal/config-sample.php) nach `internal/config.php` kopieren und anpassen:
   - `STUDENTS_CSV` — Pfad zur Schüler-CSV-Datei aus dem Schulverwaltungsprogramm
   - `WALLET_API_BASE`, `WALLET_API_KEY`, `WALLET_THEME_ID` — Zugangsdaten der WalletStudentID-Instanz
   - `VERIFY_BASE_URL` — öffentliche URL des Frontends (wird in die QR-Codes eingebettet)
   - `SCHOOL`, `SCHOOLYEAR_START`, `IMG_BASE_URL` — schulspezifische Einstellungen
   - `ADMIN_CIDR` *(empfohlen)* — kommagetrennte IPv4-CIDRs mit Zugriff auf `/admin`, z. B. `10.0.0.0/8` — leer lässt alle Adressen zu
   - `ADMIN_BLOCK_IP` *(optional)* — kommagetrennte IPs/CIDRs, die `/admin` explizit verweigert werden (z. B. IP des externen Servers)
3. Webserver so konfigurieren, dass alle Anfragen auf `internal/index.php` umgeleitet werden (`.htaccess` liegt bei).
4. PHP ≥ 8.1 mit `allow_url_fopen = On` erforderlich (für Upstream-Requests zur WalletStudentID-API).

### Frontend
Für die Installation des Frontends (user-facing parts, also die Registrierungs- und Verifizierungs-Seiten) gibt es zwei Varianten. Für eine Wordpress-basierte Schulhomepage gibt es ein komfortables Plugin, siehe Variante B. Weitere Plugins für andere CMS-Systeme können noch folgen, ansonsten hier ist eine Standalone-Variante:

#### Frontend – Variante A: Standalone (Fat-Free Framework)

Geeignet, wenn das Frontend auf einem eigenen (Sub-)Pfad oder Subdomain betrieben wird, unabhängig von einem CMS.

1. Verzeichnis `external/` auf dem Webserver ablegen (z. B. unter `/var/www/html/ID/`).
2. [`external/config-sample.php`](../external/config-sample.php) nach `external/config.php` kopieren und anpassen:
   - `internal_server` — interne URL des Backend-Servers (nicht öffentlich zugänglich)
   - `BDAY_HMAC_SECRET` — zufälliges 32-Byte-Geheimnis, z. B. `openssl rand -hex 32`
   - `SCHOOL_HOMEPAGE` — öffentliche URL der Schulhomepage, z. B. `https://www.schule.de` (wird für den Content-Security-Policy-Header benötigt)
   - `require_birthday`, `WALLET_USE_APPLE`, `WALLET_USE_GOOGLE` — Feature-Flags
3. `external/templates/head.html` und `foot.html` so anpassen, dass die Registerierungs- und Validierungs-Seiten
   nahtlos in die Schulhomepage passen. Dies ist insofern besonders empfehlenswert, als die Verifizierungs-Aufrufe
   auf der Schulehomepage erscheinen und klar machen sollen, dass die Überprüfung *schulseitig* erfolgt ist.
4. Webserver so konfigurieren, dass alle Anfragen auf `external/index.php` umgeleitet werden (`.htaccess` liegt bei).
5. Das Frontend ist unter den konfigurierten Pfaden erreichbar, z. B.:
   - Verifikation: `https://schule.de/ID/v/{UUID}`
   - Registrierung: `https://schule.de/ID/r/{UUID}`

#### Frontend – Variante B: WordPress-Plugin

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

---

## Sicherheitshinweise

Für den sicheren Produktivbetrieb — insbesondere zur Absicherung des Admin-Interfaces, der HTTPS-Konfiguration und der Protokollierung — siehe [SECURITY.md](SECURITY.md).