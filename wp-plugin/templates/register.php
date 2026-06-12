<?php defined('ABSPATH') || exit; ?>
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
    z-index: 10;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
}
#wallet-loading.active { display: flex; }
#wallet-loading svg { animation: sid-spin 0.85s linear infinite; transform-origin: center; }
#wallet-loading span { font-size: 13px; color: #4387cc; font-style: italic; }
</style>

<div class="header-post-title-container clearfix">
    <div class="inner-wrap">
        <div class="post-title-wrapper">
            <h1 class="header-post-title-class entry-title">Digitaler <?= esc_html($card_type) ?></h1>
        </div>
    </div>
</div>

<div id="main"><div class="inner-wrap"><div id="content">
    <article class="post type-post status-publish format-standard">
        <?php if ($valid): ?>
            <?php if ($deploy_error === ''): ?>
                <p>Mit den folgenden Links können Sie den <?= esc_html($card_type) ?> zu einem Wallet
                hinzufügen.<br /></p>
                <div class="datenschutz">Bitte beachten Sie, dass Sie mit dem Klicken auf einen der Links der
                    Übertragung Ihrer
                    <span class="tooltip">Daten
                        <span class="tooltiptext">
                            Name, Vorname, Gültigkeitsdauer, Geburtsdatum, Zugehörigkeit zur Schule
                        </span>
                    </span> gemäß den Datenschutzbestimmungen
                    zustimmen. Für die Nutzung des Google Wallet
                    gelten weitere <a href="https://support.google.com/wallet/answer/12205617?hl=de">Bedingungen und Datenschutzerklärungen</a>.
                    Informationen zur Datensicherheit sind <a href="https://www.hhs.karlsruhe.de/registrierung-digitaler-schuelerausweis/">hier
                    zusammengestellt</a>.
                </div>
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
            <?php else: ?>
                <?= esc_html($deploy_error) ?>
            <?php endif; ?>
        <?php else: ?>
            Der eingescannte QR-Code ist mit keiner gültigen ID verknüpft oder es wurde bereits ein
            Papier-Ausweis erstellt. Sollte es sich hier um einen Irrtum handeln, wenden Sie sich bitte an unser Sekretariat.
        <?php endif; ?>
    </article>
</div></div></div>

<?php get_footer(); ?>
