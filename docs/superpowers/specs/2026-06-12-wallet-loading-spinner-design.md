# Design: Wallet-Download Lade-Indikator

**Datum:** 2026-06-12  
**Scope:** WP-Plugin (`wp-plugin/templates/register.php`) und Standalone-External (`external/templates/register.html`)

## Ziel

Nach dem Klick auf einen Wallet-Download-Link (Apple oder Google) soll der Nutzer sofortiges visuelles Feedback erhalten, dass der Ausweis generiert wird. Aktuell werden die Links nur ausgegraut (opacity + pointer-events), ein expliziter Lade-Indikator fehlt.

## Entschiedenes Design: Overlay über dem Wallet-Bereich (Variante B)

Ein halbtransparentes Overlay legt sich nach dem Klick über den `.wallet-area`-Container (enthält ausschließlich die Apple/Google-Links). Der Rest der Seite — Überschrift, Datenschutz-Box, Fehlertext — bleibt sichtbar und unverändert.

### Visuelles

- **Overlay:** `position: absolute; inset: 0` auf `.wallet-area` (die `position: relative` bekommt), `background: rgba(255,255,255,0.92)`, leichter `backdrop-filter: blur(2px)`
- **Spinner:** Inline-SVG, zwei konzentrische `<circle>`-Elemente — ein heller Ring als Hintergrund (`#d0ddf5`), ein kürzerer farbiger Bogen (`#4387cc`) als drehender Indikator. Das gesamte SVG-Element wird per CSS-Animation rotiert (`transform-origin: center`). **Kein CSS-Border-Trick** — der erzeugt ein visuelles Eiern durch asymmetrische Masse beim Drehen.
- **Text:** `"Ausweis wird erstellt…"` — kursiv, Farbe `#4387cc` (bestehendes Theme)
- **Spinner-Größe:** 38×38 px

### CSS-Klassen (werden in beiden Templates inline per `<style>`-Block ergänzt)

```css
@keyframes sid-spin { to { transform: rotate(360deg); } }

.wallet-area { position: relative; }

#wallet-loading {
  display: none;
  position: absolute;
  inset: 0;
  background: rgba(255,255,255,0.92);
  backdrop-filter: blur(2px);
  border-radius: 4px;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 10px;
}
#wallet-loading.active { display: flex; }

#wallet-loading svg {
  animation: sid-spin 0.85s linear infinite;
  transform-origin: center;
}

#wallet-loading span {
  font-size: 13px;
  color: #4387cc;
  font-style: italic;
}
```

### Spinner-SVG (inline, keine externe Ressource)

```html
<div id="wallet-loading">
  <svg width="38" height="38" viewBox="0 0 38 38" xmlns="http://www.w3.org/2000/svg">
    <circle cx="19" cy="19" r="15" fill="none" stroke="#d0ddf5" stroke-width="4"/>
    <circle cx="19" cy="19" r="15" fill="none" stroke="#4387cc" stroke-width="4"
      stroke-dasharray="24 70" stroke-linecap="round"/>
  </svg>
  <span>Ausweis wird erstellt…</span>
</div>
```

### JavaScript

Das bestehende Click-Handler-Script bleibt. Es wird erweitert: statt `msg.style.display = 'block'` wird die CSS-Klasse `.active` gesetzt.

```js
var msg = document.getElementById('wallet-loading');
if (msg) msg.classList.add('active');
```

Der Rest (pointer-events, opacity auf den Links) entfällt — das Overlay blockiert Klicks bereits vollständig.

## Strukturelle Template-Änderung

Die Wallet-Links sind aktuell **nicht** in einem gemeinsamen Container. Ein neues `<div class="wallet-area">` muss um die Links (inkl. der `<br /><br />`-Abstände) und das `#wallet-loading`-Element gewickelt werden. Der bestehende `<p id="wallet-loading">` wird zu einem `<div id="wallet-loading">` mit SVG + `<span>`.

## Dateien

| Datei | Änderung |
|---|---|
| `wp-plugin/templates/register.php` | `<style>`-Block ergänzen, `wallet-area`-Wrapper hinzufügen, `#wallet-loading`-HTML ersetzen, JS anpassen |
| `external/templates/register.html` | identisch, ohne WP-Nonce auf dem `<script>`-Tag |

## Nicht geändert

- Keine neuen Dateien, keine externen Abhängigkeiten
- `verify.php` / `verify.html` bleiben unberührt
- Kein PHP-Backend-Code betroffen
