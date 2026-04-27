# Sicherheitshinweise für den Produktivbetrieb

Dieses Dokument ergänzt die Installationsanleitung in der [README](README.md) um Hinweise zur sicheren Konfiguration im Produktivbetrieb.

---

## Checkliste Produktivbetrieb

- [ ] HTTPS auf allen extern erreichbaren Endpunkten erzwungen
- [ ] Backend-Server (`internal/`) nicht öffentlich erreichbar
- [ ] `ADMIN_CIDR` auf Intranet-Adressbereich eingeschränkt
- [ ] `BDAY_HMAC_SECRET` gesetzt (mind. 32 zufällige Bytes)
- [ ] `SCHOOL_HOMEPAGE` korrekt gesetzt (für CSP-Header)
- [ ] Log-Verzeichnis außerhalb des Document-Root oder per Webserver-Regel geschützt
- [ ] PHP-Sessions aktiviert (für CSRF-Schutz des Geburtstagsdatum-Formulars)
- [ ] DEBUG in `external/index.php` auf `0` belassen

---

## Admin-Interface absichern

Das Admin-Interface (`/admin`) des Backend-Servers erlaubt das Löschen, Neuausstellen und Massenaktualisieren von Ausweisen. Es sollte ausschließlich aus dem Schulintranet erreichbar sein.

### Empfohlene Konfiguration in `internal/config.php`

```php
// Nur Zugriff aus dem Schulintranet erlauben:
define('ADMIN_CIDR', '10.0.0.0/8');          // Intranet-CIDR anpassen

// Zugriff vom externen Server explizit sperren (optional, aber empfohlen):
define('ADMIN_BLOCK_IP', '203.0.113.5');      // IP des externen Servers eintragen
```

Mehrere CIDRs bzw. IPs werden kommagetrennt angegeben, z. B. `'192.168.1.0/24,10.0.0.0/8'`.

Wird `ADMIN_CIDR` leer gelassen, ist `/admin` von allen IP-Adressen erreichbar, die den Backend-Server erreichen können.

### Zusätzliche Absicherung auf Webserver-Ebene

Die Zugriffsbeschränkung per `ADMIN_CIDR` greift auf PHP-Ebene. Ergänzend empfiehlt sich eine Einschränkung direkt im Webserver, die greift, bevor PHP gestartet wird:

**Apache:**
```apache
<Location /admin>
    Require ip 10.0.0.0/8
</Location>
```

**nginx:**
```nginx
location /admin {
    allow 10.0.0.0/8;
    deny all;
}
```

---

## HTTPS

Alle extern erreichbaren Endpunkte (Frontend, Variante A und B) müssen ausschließlich über HTTPS erreichbar sein. Die Student-UUIDs werden als URL-Pfadsegmente übertragen und dürfen nicht im Klartext übertragen werden.

Empfohlen wird eine Weiterleitung von HTTP auf HTTPS auf Webserver-Ebene sowie das Setzen des `Strict-Transport-Security`-Headers (HSTS):

**Apache:**
```apache
Header always set Strict-Transport-Security "max-age=31536000"
```

**nginx:**
```nginx
add_header Strict-Transport-Security "max-age=31536000" always;
```

---

## Content Security Policy

Die externen Endpunkte senden einen `Content-Security-Policy`-Header, der über `SCHOOL_HOMEPAGE` in `external/config.php` konfiguriert wird. Dieser erlaubt das Laden von Stylesheets und Bildern von der Schulhomepage.

Nach dem Ersetzen von `head.html` durch ein schuleigenes Boilerplate sollte der CSP-Header in `external/index.php` entsprechend angepasst werden — insbesondere kann `style-src` dann auf `'self'` eingeschränkt werden, sofern keine externen Stylesheets mehr eingebunden werden.

---

## PHP-Sessions

Der CSRF-Schutz des Geburtstagsdatum-Formulars (Standalone-Variante A) setzt funktionierende PHP-Sessions voraus. Sicherzustellen ist:

- `session.cookie_httponly = On` — verhindert Zugriff auf das Session-Cookie per JavaScript
- `session.cookie_secure = On` — Cookie nur über HTTPS übertragen (setzt HTTPS voraus)
- `session.cookie_samesite = Lax` — schützt zusätzlich gegen CSRF

Diese Einstellungen können in der `php.ini` oder per `.htaccess` gesetzt werden:

```ini
php_value session.cookie_httponly On
php_value session.cookie_secure   On
php_value session.cookie_samesite Lax
```

---

## Log-Dateien

Die Anwendung schreibt Protokolldateien (`deploy.log`, `update.log`, `admin.log`, `api-error.log`) in das Arbeitsverzeichnis des jeweiligen Servers. Diese Dateien enthalten Student-UUIDs und Pass-IDs, aber keine personenbezogenen Daten (Namen, Geburtsdaten).

Empfehlungen:
- Log-Verzeichnis außerhalb des Document-Root ablegen, oder per Webserver-Regel vor direktem Abruf schützen
- Regelmäßige Log-Rotation einrichten (z. B. via `logrotate`)
- Zugriffsrechte auf die Log-Dateien auf den Webserver-Prozess beschränken (`chmod 640`)

---

## Geheimnisse

| Konfigurationswert | Mindestanforderung | Wo |
|---|---|---|
| `BDAY_HMAC_SECRET` | 32 zufällige Bytes (Hex) | `external/config.php` und WP-Plugin-Einstellungen |
| `WALLET_API_KEY` | vom WalletStudentID-Dienst vorgegeben | `internal/config.php` |

Erzeugung eines sicheren Geheimnisses:
```bash
openssl rand -hex 32
```

Geheimnisse dürfen nicht in das Repository eingecheckt werden. Die `config.php`-Dateien sind in `.gitignore` eingetragen.
