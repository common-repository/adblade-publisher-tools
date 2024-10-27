<?php
/**
 * The options page.
 */

defined( 'ABSPATH' ) or die( 'Access Denied!' );

require_once plugin_dir_path( __FILE__ ) . 'adblade-publisher-tools.php';

define( 'ADBLADE_DATABASE_VERSION', '1.8.3' );

function adblade_load_admin_assets() {
    wp_enqueue_script( 'adblade-admin-script', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), null, true );
    wp_enqueue_style( 'adblade-admin-style', plugins_url( 'css/admin.css', __FILE__ ) );
}
add_action( 'admin_enqueue_scripts', 'adblade_load_admin_assets' );

add_option(
    'adblade_options',
    array(
        'db_version'    => 1,
        'ga'            => 1,
        'bypass'        => 1,
        'beforePostTag' => '',
        'afterPostTag'  => '',
        'adsWithinTag'  => '',
        'adInjections'  => array(),
        'transients'    => array(),
    ),
    null,
    'yes'
);

if ( is_admin() ) { // Admin actions.
    add_action( 'admin_menu', 'adblade_menu' );
    add_action( 'admin_init', 'register_adblade_settings' );
}

/**
 * Add "Adblade" to the admin menu
 */
function adblade_menu() {
    add_options_page( 'Adblade Options', 'Adblade', 'manage_options', 'adblade', 'adblade_options' );
}

/**
 * Register plugin settings.
 */
function register_adblade_settings() {
    $options = get_option( 'adblade_options' );

    if ( false === $options || ! array_key_exists( 'db_version', $options ) || version_compare( $options['db_version'], ADBLADE_DATABASE_VERSION, '<' ) || ! array_key_exists( 'query_var', $options ) || empty( $options['query_var'] ) ) {
        // update the datbase version so we don't reset it each time
        $options['db_version'] = ADBLADE_DATABASE_VERSION;
        update_option( ADBLADE_OPTIONS_KEY, $options, 'yes' );

        // Remove any cron that was set and re-add it.
        wp_clear_scheduled_hook( ADBLADE_CRON );
        wp_schedule_event( time(), ADBLADE_CRON_INTERVAL, ADBLADE_CRON );
        // Make sure we add some bypass rules.
        adblade_regenerate_bypass_rules();
    }

    register_setting( 'adblade-group', 'adblade_options', 'adblade_options_sanitize' );

    // General settings.
    add_settings_section( 'general-settings', 'General Settings', 'adblade_general_settings_callback', 'adblade-publisher-tools' );
    add_settings_field( 'ga', 'Enable Google Analytics event logging', 'adblade_ga_callback', 'adblade-publisher-tools', 'general-settings' );
    add_settings_field( 'bypass', 'Attempt to bypass ad blockers', 'adblade_bypass_callback', 'adblade-publisher-tools', 'general-settings' );

    // Ad tag settings.
    add_settings_section( 'ad-tag-settings', 'Ad Tag Settings', 'adblade_ad_tag_settings_callback', 'adblade-publisher-tools' );
    add_settings_field( 'beforePostTag', 'Before Post Ad Tag', 'adblade_before_post_ad_tag_callback', 'adblade-publisher-tools', 'ad-tag-settings' );
    add_settings_field( 'adsWithinTag', '[AdsWithin] Short Code Ad Tag', 'adblade_ads_within_ad_tag_callback', 'adblade-publisher-tools', 'ad-tag-settings' );
    add_settings_field( 'afterPostTag', 'After Post Ad Tag', 'adblade_after_post_ad_tag_callback', 'adblade-publisher-tools', 'ad-tag-settings' );

    // advanced anti ad block settings
    add_settings_section( 'advanced-anti-adblock-settings', 'Advanced Ad Blocker Bypass Settings', 'adblade_advanced_anti_adblock_settings_callback', 'adblade-publisher-tools' );
    add_settings_field( 'adInjection', 'Ad Injections', 'adblade_ad_injection_callback', 'adblade-publisher-tools', 'advanced-anti-adblock-settings' );
}

/**
 * Advqanced adnti-adblock settings callback.
 */
function adblade_advanced_anti_adblock_settings_callback() {
    echo 'The advanced ad blocker bypass settings are optional. They can be used to only display Adblade ads when an ad blocker is detected. If no ad blocker is detected your regular ad tags will be displayed like normal. When an ad blocker is detected, the plugin will insert the Adblade ad tag into the document using the selector specified for that tag. If you change the advanced ad blocker bypass settings it is recommended you clear your WordPress cache after saving the changes.';
}

/**
 * Ad tag settings callback.
 */
function adblade_ad_tag_settings_callback() {
    echo 'These fields are optional. You can leave them blank and add tags however you wish.';
}

/**
 * General settings callback.
 */
function adblade_general_settings_callback() {
    // Don't do anything here.
}

/**
 * Output the settings input for Google Analytics.
 */
function adblade_ga_callback() {
    $options = get_option( 'adblade_options' );

    echo '<input name="adblade_options[ga]" type="checkbox" value="1" ';
    checked( '1', $options['ga'] );
    echo ' />';
}

/**
 * Output the settings input for bypassing ad blockers.
 */
function adblade_bypass_callback() {
    $options = get_option( 'adblade_options' );
    echo '<input name="adblade_options[bypass]" type="checkbox" value="1" ';
    checked( '1', $options['bypass'] );
    echo ' />';
}

/**
 * Output the settings input for the pre-post ad tag.
 */
function adblade_before_post_ad_tag_callback() {
    $options = get_option( 'adblade_options' );
    printf( '<textarea name="adblade_options[beforePostTag]" cols="100" rows="10">%s</textarea>', $options['beforePostTag'] );
}

/**
 * Output the settings input for the post-post ad tag.
 */
function adblade_after_post_ad_tag_callback() {
    $options = get_option( 'adblade_options' );
    printf( '<textarea name="adblade_options[afterPostTag]" cols="100" rows="10">%s</textarea>', $options['afterPostTag'] );
}

/**
 * Output the settings input for the [AdsWithin] ad tag.
 */
function adblade_ads_within_ad_tag_callback() {
    $options = get_option( 'adblade_options' );
    printf( '<textarea name="adblade_options[adsWithinTag]" cols="100" rows="10">%s</textarea>', $options['adsWithinTag'] );
}

/**
 * Output the settings input for the ad injections.
 */
function adblade_ad_injection_callback() {
    $options    = get_option( 'adblade_options' );
    $injections = array_key_exists( 'adInjections', $options ) ? $options['adInjections'] : array();

    echo '<div class="ad-injection">';
    if ( ! empty( $injections ) && is_array( $injections ) ) {
        foreach ( $injections as $injection ) {
            echo '<label>Selector:</label>';
            printf( '<input type="text" name="adblade_options[adInjections][selector][]" value="%s" />', $injection['selector'] );
            echo '<label>Adblade Ad Tag:</label>';
            printf( '<textarea name="adblade_options[adInjections][tag][]" cols="100" rows="10">%s</textarea>', $injection['tag'] );
        }
    }

    echo '<label>Selector:</label>';
    echo '<input type="text" name="adblade_options[adInjections][selector][]" />';
    echo '<label>Adblade Ad Tag:</label>';
    echo '<textarea name="adblade_options[adInjections][tag][]" cols="100" rows="10"></textarea>';
    echo '</div>';
}

/**
 * Sanitize the form input.
 * @param array $input - The form input.
 * @return The sanitized input.
 */
function adblade_options_sanitize( $input ) {
    $options = get_option( 'adblade_options' );
    $clean   = array();

    // save some options from the database
    $clean['redirect_actions'] = $options['redirect_actions'];
    $clean['transients']       = $options['transients'];
    $clean['query_var']        = $options['query_var'];
    $clean['replacements']     = $options['replacements'];

    $clean['ga']     = (intval( $input['ga'] ) === 1) ? 1 : 0;
    $clean['bypass'] = (intval( $input['bypass'] ) === 1) ? 1 : 0;

    $clean['beforePostTag'] = $input['beforePostTag'];
    $clean['adsWithinTag']  = $input['adsWithinTag'];
    $clean['afterPostTag']  = $input['afterPostTag'];

    $clean['adInjections']  = array();
    if ( array_key_exists( 'adInjections', $input ) ) {
        if ( ! empty( $input['adInjections'] ) ) {
            // we should have the same number of selectors as tags
            $selectors     = array_values( array_filter( $input['adInjections']['selector'], 'strlen' ) );
            $tags          = array_values( array_filter( $input['adInjections']['tag'], 'strlen' ) );
            $selectorCount = count( $selectors );
            $tagCount      = count( $tags );

            if ( $selectorCount === $tagCount ) {
                for ( $i = 0; $i < $selectorCount; $i++ ) {
                    $clean['adInjections'][] = array(
                        'selector' => $selectors[ $i ],
                        'tag'      => $tags[ $i ],
                    );
                }
            } else {
                add_settings_error( 'adblade-injection-error', 'adblade_options', 'You need the same number of tags and selectors for &quot;Ad Injections&quot;.' );
            }
        }
    }

    return $clean;
}

/**
 * Build the admin options form.
 */
function adblade_options() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
?>

    <div class="wrap">
        <form method="post" action="options.php"> 
        <?php
        wp_nonce_field( 'adblade-publisher-tools-options' );
        settings_fields( 'adblade-group' );
        do_settings_sections( 'adblade-publisher-tools' );
        submit_button();
        ?>
        </form>
    </div>

<?php
}
