<?php defined('ABSPATH') || exit; ?>
<?php get_header(); ?>

<div class="header-post-title-container clearfix">
    <div class="inner-wrap">
        <div class="post-title-wrapper">
            <h1 class="header-post-title-class entry-title">
                Digitaler <?= esc_html($card_type) ?> <br />Nr. <?= esc_html($shortid) ?>
            </h1>
        </div>
    </div>
</div>

<div id="main"><div class="inner-wrap"><div id="content">
    <article class="post type-post status-publish format-standard">
        <form class="form-signin" method="post" action="<?= esc_url($auth) ?>">
            <h2 class="form-signin-heading">Abruf Ausweis</h2>
            <p>Bitte authentifizieren Sie sich mit Ihrem Geburtsdatum:</p>
            <div>
                <input type="number" name="day"   min="01" max="31"   class="autoinput form-twodigits"
                    autofocus required inputmode="numeric" pattern="[0-9]*" placeholder="TT"   id="day"   maxlength="2">
                <input type="number" name="month" min="01" max="12"   class="autoinput form-twodigits"
                    required inputmode="numeric" pattern="[0-9]*" placeholder="MM"   id="month" maxlength="2">
                <input type="number" name="year"  min="1900" max="2050" class="autoinput form-fourdigits"
                    required inputmode="numeric" pattern="[0-9]*" placeholder="JJJJ" id="year"  maxlength="4">
            </div>
            <div style="margin-top: 8px;">
                <button type="submit" class="btn btn-lg btn-primary btn-block">Absenden</button>
            </div>
        </form>
        <script>
        var elts = document.getElementsByClassName('autoinput');
        Array.from(elts).forEach(function(elt) {
            elt.addEventListener('keyup', function(event) {
                if (event.keyCode !== 9 && (event.keyCode === 13 || elt.value.length == elt.maxLength))
                    if (elt.nextElementSibling) elt.nextElementSibling.focus();
            });
        });
        </script>
    </article>
</div></div></div>

<?php get_footer(); ?>
