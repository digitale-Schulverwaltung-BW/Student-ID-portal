# Wallet Loading Spinner Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the plain text loading message with a smooth overlay spinner on the wallet download area in both the WP plugin and standalone-external templates.

**Architecture:** Both templates already have inline `<script>` click handlers that disable the wallet links and show a hidden `<p id="wallet-loading">`. We extend this by (1) wrapping the wallet links and the loading element in a new `<div class="wallet-area">`, (2) replacing the `<p>` with a `<div>` overlay containing an inline SVG spinner, and (3) updating the JS to toggle a `.active` CSS class instead of manipulating inline styles. An SVG circle is used for the spinner — not the CSS `border-top-color` trick — to avoid visual wobble from asymmetric rendering.

**Tech Stack:** PHP (WP plugin template), Fat-Free Framework HTML template (external), inline CSS `@keyframes`, inline SVG, vanilla JS

---

### Task 1: Update WP plugin template

**Files:**
- Modify: `wp-plugin/templates/register.php`

- [ ] **Step 1: Add the CSS style block**

In `wp-plugin/templates/register.php`, add a `<style>` block immediately after `<?php get_header(); ?>` (currently line 2):

```php
<?php get_header(); ?>
<style>
@keyframes sid-spin { to { transform: rotate(360deg); } }
.wallet-area { position: relative; }
#wallet-loading {
    display: none;
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(2px);
    border-radius: 4px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
}
#wallet-loading.active { display: flex; }
#wallet-loading svg { animation: sid-spin 0.85s linear infinite; transform-origin: center; }
#wallet-loading span { font-size: 13px; color: #4387cc; font-style: italic; }
</style>
```

- [ ] **Step 2: Wrap wallet links in `.wallet-area` and replace the loading indicator**

Inside the `<?php if ($deploy_error === ''): ?>` block, replace the current wallet links + script + loading paragraph with the version below. The `<script>` tag moves to **after** the closing `</div>` of `wallet-area`.

Current code (lines ~30–56):
```php
<?php if ($apple): ?>
    <a class="wallet-link" href="<?= esc_url($base) ?>/apple">
        <img src="<?= esc_url(SID_PLUGIN_URL . 'templates/add-to-apple-wallet-logo.png') ?>"
             alt="zur Apple Wallet hinzufügen" style="height: 48px;" /><br />
        Hinzufügen zu Apple Wallet
    </a><br /><br />
<?php endif; ?>
<?php if ($google): ?>
    <a class="wallet-link" href="<?= esc_url($base) ?>/google">
        <img src="<?= esc_url(SID_PLUGIN_URL . 'templates/save_to_google_pay_dark_de.svg') ?>"
             alt="zur Google Wallet hinzufügen" style="height: 48px;" /><br />
        Hinzufügen zu Google Wallet
    </a><br /><br />
<?php endif; ?>
<script nonce="<?= esc_attr($GLOBALS['sid_script_nonce']) ?>">
document.querySelectorAll('.wallet-link').forEach(function(link) {
    link.addEventListener('click', function() {
        document.querySelectorAll('.wallet-link').forEach(function(l) {
            l.style.pointerEvents = 'none';
            l.style.opacity = '0.5';
        });
        var msg = document.getElementById('wallet-loading');
        if (msg) msg.style.display = 'block';
    });
});
</script>
<p id="wallet-loading" style="display:none;">Ausweis wird erstellt, bitte warten...</p>
```

Replace with:
```php
<div class="wallet-area">
    <?php if ($apple): ?>
        <a class="wallet-link" href="<?= esc_url($base) ?>/apple">
            <img src="<?= esc_url(SID_PLUGIN_URL . 'templates/add-to-apple-wallet-logo.png') ?>"
                 alt="zur Apple Wallet hinzufügen" style="height: 48px;" /><br />
            Hinzufügen zu Apple Wallet
        </a><br /><br />
    <?php endif; ?>
    <?php if ($google): ?>
        <a class="wallet-link" href="<?= esc_url($base) ?>/google">
            <img src="<?= esc_url(SID_PLUGIN_URL . 'templates/save_to_google_pay_dark_de.svg') ?>"
                 alt="zur Google Wallet hinzufügen" style="height: 48px;" /><br />
            Hinzufügen zu Google Wallet
        </a><br /><br />
    <?php endif; ?>
    <div id="wallet-loading">
        <svg width="38" height="38" viewBox="0 0 38 38" xmlns="http://www.w3.org/2000/svg">
            <circle cx="19" cy="19" r="15" fill="none" stroke="#d0ddf5" stroke-width="4"/>
            <circle cx="19" cy="19" r="15" fill="none" stroke="#4387cc" stroke-width="4"
                stroke-dasharray="24 70" stroke-linecap="round"/>
        </svg>
        <span>Ausweis wird erstellt…</span>
    </div>
</div>
<script nonce="<?= esc_attr($GLOBALS['sid_script_nonce']) ?>">
document.querySelectorAll('.wallet-link').forEach(function(link) {
    link.addEventListener('click', function() {
        var msg = document.getElementById('wallet-loading');
        if (msg) msg.classList.add('active');
    });
});
</script>
```

- [ ] **Step 3: Verify visually**

Load the WP plugin register page in a browser with a valid session and click either wallet link. Expected behaviour:
- A semi-transparent white overlay appears immediately, covering only the wallet links area
- A smooth, wobble-free spinning ring (blue arc on light-blue circle) is centred in the overlay
- "Ausweis wird erstellt…" appears in italic blue below the spinner
- The Datenschutz-Box, intro text and page header above remain fully visible and interactive
- Clicking the overlay does nothing (overlay blocks pointer events)

- [ ] **Step 4: Commit**

```bash
git add wp-plugin/templates/register.php
git commit -m "feat: add loading overlay spinner to WP plugin wallet download"
```

---

### Task 2: Update standalone-external template

**Files:**
- Modify: `external/templates/register.html`

- [ ] **Step 1: Add the CSS style block**

In `external/templates/register.html`, add a `<style>` block immediately after the `<include>` on line 1:

```html
<include href="templates/head.html" />
<style>
@keyframes sid-spin { to { transform: rotate(360deg); } }
.wallet-area { position: relative; }
#wallet-loading {
    display: none;
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(2px);
    border-radius: 4px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
}
#wallet-loading.active { display: flex; }
#wallet-loading svg { animation: sid-spin 0.85s linear infinite; transform-origin: center; }
#wallet-loading span { font-size: 13px; color: #4387cc; font-style: italic; }
</style>
```

- [ ] **Step 2: Wrap wallet links in `.wallet-area` and replace the loading indicator**

Inside the `<check if="{{ @deploy_error=='' }}"><true>` block, replace the current wallet links + script + loading paragraph (currently lines ~31–51) with:

```html
<div class="wallet-area">
    <check if="{{ @apple }}"><a class="wallet-link" href="{{ @base | esc }}/apple"><img src="../templates/add-to-apple-wallet-logo.png" alt="zur Apple Wallet hinzufügen"
        style="height: 48px;" /><br />
        Hinzufügen zu Apple Wallet</a><br /><br />
    </check>
    <check if="{{ @google }}"><a class="wallet-link" href="{{ @base | esc }}/google"><img src="../templates/save_to_google_pay_dark_de.svg" alt="zur Google Wallet hinzufügen"
        style="height: 48px;" /><br />
        Hinzufügen zu Google Wallet</a><br /><br />
    </check>
    <div id="wallet-loading">
        <svg width="38" height="38" viewBox="0 0 38 38" xmlns="http://www.w3.org/2000/svg">
            <circle cx="19" cy="19" r="15" fill="none" stroke="#d0ddf5" stroke-width="4"/>
            <circle cx="19" cy="19" r="15" fill="none" stroke="#4387cc" stroke-width="4"
                stroke-dasharray="24 70" stroke-linecap="round"/>
        </svg>
        <span>Ausweis wird erstellt…</span>
    </div>
</div>
<script>
document.querySelectorAll('.wallet-link').forEach(function(link) {
    link.addEventListener('click', function() {
        var msg = document.getElementById('wallet-loading');
        if (msg) msg.classList.add('active');
    });
});
</script>
```

Note: no `nonce` attribute on `<script>` here — the external template does not use WP's CSP nonce.

- [ ] **Step 3: Verify visually**

Load the standalone-external register page in a browser and click either wallet link. Expected behaviour is identical to Task 1 Step 3.

- [ ] **Step 4: Commit**

```bash
git add external/templates/register.html
git commit -m "feat: add loading overlay spinner to standalone-external wallet download"
```
