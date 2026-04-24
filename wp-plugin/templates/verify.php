<?php defined('ABSPATH') || exit; ?>
<?php get_header(); ?>

<div class="header-post-title-container clearfix">
    <div class="inner-wrap">
        <div class="post-title-wrapper">
            <table><tr><td style="text-align: right; border-right: 2px solid">
                <?php if ($valid): ?>
                    <h2 class="header-post-title-class entry-title sid-valid">
                        <?= esc_html($card_type) ?><br />
                        <?= esc_html($card_type_fr) ?><br />
                        <?= esc_html($card_type_en) ?>
                    </h2>
                <?php else: ?>
                    <h2 class="header-post-title-class entry-title sid-invalid">
                        ungültig<br />invalide<br />invalid
                    </h2>
                <?php endif; ?>
            </td><td>
                <h1 class="<?= $valid ? 'sid-valid' : 'sid-invalid' ?>" style="padding-bottom: 0">
                    <b>Nr. <?= esc_html($card_ID) ?></b><br />
                </h1>
                <?php if ($valid): ?>
                    <b>Geburtsdatum:</b> <?= esc_html($birthday) ?><br />
                    <b>Initialen <?= esc_html($person_label) ?>:</b>
                    <?= esc_html($fn) ?>.&nbsp;<?= esc_html($sn) ?>.
                <?php endif; ?>
            </td></tr></table>
        </div>
    </div>
</div>

<div id="main"><div class="inner-wrap"><div id="content">
    <article class="post type-post status-publish format-standard">
        <?php if ($valid): ?>
            <?= esc_html($person_desc) ?> mit der Ausweis ID <b><?= esc_html($card_ID) ?></b>
            <?= esc_html($role_desc) ?> <?= esc_html($school) ?>.
        <?php else: ?>
            Der vorgelegte Ausweis ist im aktuellen Schuljahr nicht (mehr) gültig und
            der Inhaber ist aktuell <?= esc_html($person_invalid) ?> unserer Schule.
        <?php endif; ?>
    </article>
</div></div></div>

<?php get_footer(); ?>
