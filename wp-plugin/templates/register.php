<?php defined('ABSPATH') || exit; ?>
<?php get_header(); ?>

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
