<?php
/**
 * Plugin Name: Student ID Portal
 * Plugin URI:  https://github.com/joergseyfried/Student-ID-portal
 * Description: Schüler- und Lehrkraft-Nachweis Verifikation und Wallet-Registrierung
 * Version:     1.0.0
 * Requires PHP: 8.1
 */

if (!defined('ABSPATH')) exit;

define('SID_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SID_PLUGIN_URL', plugin_dir_url(__FILE__));

// --- Activation / Deactivation ---

register_activation_hook(__FILE__,   'sid_flush_rewrite_rules');
register_deactivation_hook(__FILE__, 'sid_flush_rewrite_rules');
function sid_flush_rewrite_rules(): void { flush_rewrite_rules(); }

// Flush rewrite rules when prefix settings change
add_action('update_option_sid_prefix_student', 'sid_flush_rewrite_rules');
add_action('update_option_sid_prefix_teacher', 'sid_flush_rewrite_rules');

// --- Rewrite rules ---

add_action('init', 'sid_register_rewrite_rules');
function sid_register_rewrite_rules(): void {
    $sp = preg_quote(get_option('sid_prefix_student', 'ID'),  '#');
    $tp = preg_quote(get_option('sid_prefix_teacher', 'LID'), '#');
    $pfx = "($sp|$tp)";

    // Verify: /ID/v/{uuid}  or  /LID/v/{uuid}
    add_rewrite_rule(
        "^$pfx/v/([^/]+)/?$",
        'index.php?sid_action=verify&sid_cardtype=$matches[1]&sid_id=$matches[2]',
        'top'
    );
    // Deploy with birthday hash: /ID/r/{uuid}/{64-hex}/{apple|google}
    add_rewrite_rule(
        "^$pfx/r/([^/]+)/([a-f0-9]{64})/(apple|google)/?$",
        'index.php?sid_action=deploy&sid_cardtype=$matches[1]&sid_id=$matches[2]&sid_hash=$matches[3]&sid_deploy=$matches[4]',
        'top'
    );
    // Deploy without auth: /ID/r/{uuid}/{apple|google}
    add_rewrite_rule(
        "^$pfx/r/([^/]+)/(apple|google)/?$",
        'index.php?sid_action=deploy_noauth&sid_cardtype=$matches[1]&sid_id=$matches[2]&sid_deploy=$matches[3]',
        'top'
    );
    // Register: /ID/r/{uuid}
    add_rewrite_rule(
        "^$pfx/r/([^/]+)/?$",
        'index.php?sid_action=register&sid_cardtype=$matches[1]&sid_id=$matches[2]',
        'top'
    );
    // Apple pass proxy: /{prefix}/apple/v1/passes/{uuid}/apple.pkpass
    // The prefix must match what the wallet API was configured with (VERIFY_BASE_URL).
    add_rewrite_rule(
        "^$pfx/apple/(.+)$",
        'index.php?sid_action=apple_proxy&sid_path=$matches[2]',
        'top'
    );
}

// --- Suppress theme page title on plugin pages ---
// Without this, WP falls back to the blog-posts page query and the theme
// renders that page's title (e.g. "Aktuelles") above our content.
add_action('wp', function (): void {
    if (!get_query_var('sid_action')) return;
    global $wp_query;
    $wp_query->queried_object    = null;
    $wp_query->queried_object_id = 0;
    $wp_query->is_404            = false;
    $wp_query->is_home           = false;
    $wp_query->is_front_page     = false;
    $wp_query->is_posts_page     = false;
    $wp_query->is_singular       = false;
    $wp_query->is_page           = false;
}, 1);

// --- Query vars ---

add_filter('query_vars', function (array $vars): array {
    return array_merge($vars, ['sid_action', 'sid_cardtype', 'sid_id', 'sid_hash', 'sid_deploy', 'sid_path']);
});

// --- Enqueue plugin CSS (available when wp_head() fires) ---

add_action('wp_enqueue_scripts', function (): void {
    if (!get_query_var('sid_action')) return;
    wp_register_style('student-id-portal', false);
    wp_enqueue_style('student-id-portal');
    wp_add_inline_style('student-id-portal', sid_inline_css());
});

function sid_inline_css(): string {
    return '
        .sid-valid   { color: green; }
        .sid-invalid { color: red; }
        .form-twodigits  { width: 3ch; border-width: 1px; }
        .form-fourdigits { width: 8ch; border-width: 1px; }
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }
        table, tr, td { border: none; }
        td { vertical-align: middle; }
        div.datenschutz { font-size: 0.75em; background-color: #ddd; margin: 0 5px; padding: 5px 10px; }
        .tooltip { position: relative; display: inline-block; text-decoration: underline; }
        .tooltip .tooltiptext {
            visibility: hidden; width: 120px; background-color: #555; color: #fff;
            text-align: center; padding: 5px 0; border-radius: 6px;
            position: absolute; z-index: 1; top: 135%; left: 50%; margin-left: -60px;
            opacity: 0; transition: opacity 0.3s;
        }
        .tooltip .tooltiptext::after {
            content: ""; position: absolute; bottom: 100%; left: 50%; margin-left: -5px;
            border-width: 5px; border-style: solid;
            border-color: transparent transparent #555 transparent;
        }
        .tooltip:hover .tooltiptext { visibility: visible; opacity: 1; }
    ';
}

// --- Request dispatch ---

add_action('template_redirect', 'sid_handle_request');
function sid_handle_request(): void {
    $action = get_query_var('sid_action');
    if (!$action) return;

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; frame-ancestors 'none'");

    require_once SID_PLUGIN_DIR . 'classes/controller.php';
    $controller = new WPController();

    switch ($action) {
        case 'verify':
            $controller->v(); break;
        case 'register':
            if ($_SERVER['REQUEST_METHOD'] === 'POST')
                $controller->authReg();
            else
                $controller->r();
            break;
        case 'deploy':
            $controller->deployAuth(); break;
        case 'deploy_noauth':
            $controller->deploy(); break;
        case 'apple_proxy':
            $controller->appleProxy(); break;
    }
    exit();
}

// --- Settings page ---

add_action('admin_menu', function (): void {
    add_options_page(
        'Student ID Portal',
        'Student ID Portal',
        'manage_options',
        'student-id-portal',
        'sid_settings_page'
    );
});

add_action('admin_init', 'sid_register_settings');
function sid_register_settings(): void {
    $fields = [
        'sid_internal_server'   => ['Interner Server (inkl. Pfad, z. B. http://internal.local/ID)', 'url', ''],
        'sid_bday_hmac_secret'  => ['HMAC-Secret (Geburtsdatum)',    'password', ''],
        'sid_require_birthday'  => ['Geburtstag erforderlich',       'checkbox', ''],
        'sid_wallet_apple'      => ['Apple Wallet anbieten',         'checkbox', '1'],
        'sid_wallet_google'     => ['Google Wallet anbieten',        'checkbox', '1'],
        'sid_school'            => ['Schulname',                     'text',     ''],
        'sid_prefix_student'    => ['URL-Prefix Schülerausweis',     'text',     'ID'],
        'sid_prefix_teacher'    => ['URL-Prefix Lehrkraft-Nachweis', 'text',     'LID'],
    ];
    foreach ($fields as $key => [$label, $type, $default]) {
        register_setting('sid_options', $key, ['default' => $default]);
        add_settings_field($key, $label, 'sid_render_field', 'student-id-portal', 'sid_main', [
            'key' => $key, 'type' => $type,
        ]);
    }
    add_settings_section('sid_main', 'Einstellungen', '__return_false', 'student-id-portal');
}

function sid_render_field(array $args): void {
    $key   = $args['key'];
    $type  = $args['type'];
    $value = get_option($key, '');
    if ($type === 'checkbox') {
        printf('<input type="hidden" name="%s" value="0" />', esc_attr($key));
        printf('<input type="checkbox" name="%s" value="1"%s />', esc_attr($key), checked('1', $value, false));
    } else {
        printf('<input type="%s" name="%s" value="%s" class="regular-text" autocomplete="off" />',
            esc_attr($type), esc_attr($key), esc_attr($value));
    }
}

function sid_settings_page(): void {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="wrap">
        <h1>Student ID Portal</h1>
        <form method="post" action="options.php">
            <?php settings_fields('sid_options'); do_settings_sections('student-id-portal'); submit_button(); ?>
        </form>
        <hr>
        <h2>Deployment-Hinweis</h2>
        <p>
            Das Plugin-Verzeichnis kann per Symlink in WordPress eingebunden werden:<br>
            <code>ln -s /pfad/zum/repo/wp-plugin /var/www/html/wordpress/wp-content/plugins/student-id-portal</code><br>
            Der Symlink <code>wp-plugin/common</code> zeigt auf <code>../common</code> und muss im Repo vorhanden sein.
        </p>
        <p>Nach Änderung der URL-Prefixe bitte einmal unter <em>Einstellungen → Permalinks</em> speichern (Rewrite-Regeln aktualisieren).</p>
    </div>
    <?php
}
