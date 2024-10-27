<?php
/**
 * Plugin Name:    Adblade Publisher Tools
 * Description:    Provides publishers the ability to implement ad tags using WordPress short codes and additional tools to help monetize website traffic.
 * Version:    1.8.9
 * Author:    Adblade
 * Author URI:    https://www.adblade.com
 */

defined( 'ABSPATH' ) or die( 'Access Denied!' );

//with slash at the end
define( 'ADBLADE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'ADBLADE_URL_PARAM', 'ab-wordpress-plugin' );
define( 'ADBLADE_OPTIONS_KEY', 'adblade_options' );
define( 'ADBLADE_CRON', 'adblade_cron_event' );
define( 'ADBLADE_CRON_INTERVAL', 'daily' );
define( 'ADBLADE_OPTIONS_ARCHIVE_KEY', 'adblade_options_archive' );

require_once ADBLADE_PLUGIN_PATH . 'options.php';


// reschedule rewrite rules
function adblade_activation() {
    wp_schedule_event( time(), ADBLADE_CRON_INTERVAL, ADBLADE_CRON );
}
register_activation_hook( __FILE__, 'adblade_activation' );

function adblade_regenerate_bypass_rules() {
    if ( do_adblade_bypass() ) {
        $options = get_option( ADBLADE_OPTIONS_KEY );

        // save the old settings to help with caching issues
        set_transient( ADBLADE_OPTIONS_ARCHIVE_KEY, $options, WEEK_IN_SECONDS );

        $transients = array_key_exists( 'transients', $options ) ? $options['transients'] : array();

        $prev_paths = get_adblade_path_set( $options );
        $options['query_var'] = adblade_unique_path( $prev_paths, array(), 'query_var' );
        $options['transients'] = array();

        $options['redirect_actions'] = array();
        $redirect_paths = array( 'logab', 'blockadblock', 'show', 'impsc', 'static', 'click' );
        foreach ( $redirect_paths as $one_path ) {
            $options['redirect_actions'][ $one_path ] = adblade_unique_path( $prev_paths, get_adblade_path_set( $options ), $one_path );
        }

        $options['replacements'] = array(
            '/adblade-dyna/'           => 'a' . bin2hex( openssl_random_pseudo_bytes( 5 ) ),
            '/ad-type-1/'              => 'a' . bin2hex( openssl_random_pseudo_bytes( 5 ) ),
            '/ad_type_1/'              => 'a' . bin2hex( openssl_random_pseudo_bytes( 5 ) ),
            '/znContentIndicator/'     => 'a' . bin2hex( openssl_random_pseudo_bytes( 5 ) ),
            '/advlabel/'               => 'a' . bin2hex( openssl_random_pseudo_bytes( 5 ) ),
            '/adbladetitle/'           => 'a' . bin2hex( openssl_random_pseudo_bytes( 5 ) ),
            '/zone/'                   => 'a' . bin2hex( openssl_random_pseudo_bytes( 5 ) ),
        );

        update_option( ADBLADE_OPTIONS_KEY, $options, 'yes' );

        // delete the transients because they have bad data now
        foreach ( $transients as $transient ) {
            if ( delete_transient( $transient ) ) {
                adblade_log( 'deleted transient ' . $transient );
            }
        }

        // try to remove pages with bad tags
        wp_cache_flush();
    }
}
add_action( 'adblade_cron_event', 'adblade_regenerate_bypass_rules' );

function adblade_deactivation() {
    wp_clear_scheduled_hook( ADBLADE_CRON );
}
register_deactivation_hook( __FILE__, 'adblade_deactivation' );


// But WordPress has a whitelist of variables it allows, so we must put it on that list
function adblade_query_vars( $queryVars ) {
    $options = get_option( ADBLADE_OPTIONS_KEY );

    if ( false !== $options && array_key_exists( 'query_var', $options ) ) {
        $queryVars[] = $options['query_var'];
        $queryVars[] = $options['query_var'] . 'path';
    }

    // also add options for archived settings
    $archivedOptions = get_transient( ADBLADE_OPTIONS_ARCHIVE_KEY );
    if ( false !== $archivedOptions && array_key_exists( 'query_var', $archivedOptions ) ) {
        $queryVars[] = $archivedOptions['query_var'];
        $queryVars[] = $archivedOptions['query_var'] . 'path';
    }

    return $queryVars;
}
add_action( 'query_vars', 'adblade_query_vars' );

// add "settings" link on plugins page
function adblade_add_action_links( $links ) {
    $links[] = '<a href="' . admin_url( 'options-general.php?page=adblade' ) . '">Settings</a>';
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'adblade_add_action_links' );

// If this is done, we can access it later
// This example checks very early in the process:
// if the variable is set, we include our page and stop execution after it
function adblade_parse_request( &$wp ) {
    $options         = get_option( ADBLADE_OPTIONS_KEY );
    $archivedOptions = get_transient( ADBLADE_OPTIONS_ARCHIVE_KEY );

    if ( do_adblade_bypass() ) {
        $queryVars = $wp->query_vars;

        $query = null;
        $path  = null;
        if ( false !== $options && array_key_exists( $options['query_var'], $queryVars ) ) {
            $query = $queryVars[ $options['query_var'] ];

            if ( array_key_exists( $options['query_var'] . 'path', $queryVars ) ) {
                $path  = $queryVars[ $options['query_var'] . 'path' ];
            }
        } else if ( false !== $archivedOptions && array_key_exists( $archivedOptions['query_var'], $queryVars ) ) {
            $query = $queryVars[ $archivedOptions['query_var'] ];

            if ( array_key_exists( $archivedOptions['query_var'] . 'path', $queryVars ) ) {
                $path  = $queryVars[ $archivedOptions['query_var'] . 'path' ];
            }
        }

        if ( null !== $query ) {
            switch ( $query ) {
                case $options['redirect_actions']['blockadblock']:
                case $archivedOptions['redirect_actions']['blockadblock']:
                    header( 'Content-Type: application/javascript' );
                    echo file_get_contents( ADBLADE_PLUGIN_PATH. 'js/blockadblock.js' );
                    break;
                case $options['redirect_actions']['logab']:
                case $archivedOptions['redirect_actions']['logab']:
                    header( 'Content-Type: application/javascript' );
                    include ADBLADE_PLUGIN_PATH . 'js/log-ab.php';
                    break;
                case $options['redirect_actions']['show']:
                case $archivedOptions['redirect_actions']['show']:
                    header( 'Content-Type: application/javascript' );
                    adblade_proxy( 'web.adblade.com', '/js/ads/async/show.js' );
                    break;
                case $options['redirect_actions']['impsc']:
                case $archivedOptions['redirect_actions']['impsc']:
                    $queryString = filter_input( INPUT_SERVER, 'QUERY_STRING' );
                    if ( ! empty( $queryString ) ) {
                        adblade_proxy( 'web.adblade.com', sprintf( '/impsc.php?%s=1&%s', ADBLADE_URL_PARAM, $queryString ), true );
                    } else {
                        adblade_log( 'Missing query string' );
                    }
                    break;
                case $options['redirect_actions']['static']:
                case $archivedOptions['redirect_actions']['static']:
                    // fix "zone" in CSS URL
                    $zoneReplacement = $options['replacements']['/zone/'];
                    $fixedPath = preg_replace( "/$zoneReplacement/", 'zone', $path );
                    adblade_proxy( 'static-cdn.adblade.com', $fixedPath );
                    break;
                case $options['redirect_actions']['click']:
                case $archivedOptions['redirect_actions']['click']:
                    wp_redirect( sprintf( 'http://web.adblade.com/clicks.php?%s=1&%s', ADBLADE_URL_PARAM, $path ) );
                    exit();
                default:
                    adblade_log( 'No redirect action found for ' . $query );
            };
            exit();
        }
    }
}
add_action( 'parse_request', 'adblade_parse_request' );

/**
 * Add shortcode for Adblade ads.
 * e.g.[adblade container_id="####-##########"]
 * @param array $atts - The short code attribures.
 * @return An ad tag
 */
function adblade_shortcode( $atts ) {
    $a = shortcode_atts(
        array(
            'container_id' => '',
            'host'         => 'web.adblade.com',
            'protocol'     => 'https',
            'type'         => 2,
            'width'        => 1,
            'height'       => 1,
        ),
        $atts
    );

    // Do nothing if no container id was set.
    if ( empty( $a['container_id'] ) ) {
        return '';
    }

    return sprintf(
        '<ins class="adbladeads" data-cid="%s" data-host="%s" data-protocol="%s" data-width="%d" data-height="%d" data-tag-type="%d" style="display:none"></ins><script async src="%s://%s/js/ads/async/show.js" type="text/javascript"></script>',
        $a['container_id'], $a['host'], $a['protocol'], $a['width'], $a['height'], $a['type'], $a['protocol'], $a['host']
    );
}
add_shortcode( 'adblade', 'adblade_shortcode' );

/**
 * Replace "[AdsWithin] with a specified ad tag
 * @param array $atts - The short code attribures.
 * @return The replacement text
 */
function adblade_ads_within_shortcode( $atts ) {
    $options = get_option( ADBLADE_OPTIONS_KEY );

    if ( array_key_exists( 'adsWithinTag', $options ) && ! empty( $options['adsWithinTag'] ) ) {
        return $options['adsWithinTag'];
    }

    return '';
}
add_shortcode( 'AdsWithin', 'adblade_ads_within_shortcode' );

/**
 * Filter posts so we can add tags before and after the content.
 * @param string $content The content of the post.
 * @return filtered content
 */
function adblade_content_filter( $content ) {
    if ( ! is_single() ) {
        return $content;
    }

    $options = get_option( ADBLADE_OPTIONS_KEY );
    $beforeTag = '';
    $afterTag = '';

    if ( array_key_exists( 'beforePostTag', $options ) && ! empty( $options['beforePostTag'] ) ) {
        $beforeTag = stripslashes( $options['beforePostTag'] );
    }

    if ( array_key_exists( 'afterPostTag', $options ) && ! empty( $options['afterPostTag'] ) ) {
        $afterTag = stripslashes( $options['afterPostTag'] );
    }

    return $beforeTag . $content . $afterTag;
}
add_filter( 'the_content', 'adblade_content_filter' );

/**
 * Add scripts to page.
 */
function adblade_enqueue_scripts() {
    if ( do_adblade_bypass() ) {
        $options = get_option( ADBLADE_OPTIONS_KEY );

        // we only want to do this if the query_var was successfully made.
        if ( array_key_exists( 'query_var', $options ) ) {
            // These should not be added until after jQuery load.
            wp_enqueue_script( 'blockadblock', '/?' . $options['query_var'] . '=' . $options['redirect_actions']['blockadblock'], array( 'jquery' ), null, true );
            wp_enqueue_script( 'log-ab', '/?' . $options['query_var'] . '=' . $options['redirect_actions']['logab'], array( 'blockadblock', 'jquery' ), null, true );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'adblade_enqueue_scripts' );

/**
 * Make requests to the adblade servers.
 * @param string  $host The host the request is going to.
 * @param string  $path The path of the URL.
 * @param boolean $skipCache Whether or not we should skip the cache.
 */
function adblade_proxy( $host, $path, $skipCache = false ) {
    // Make sure it is an adblade domain.
    $validDomains = array(
        'web.adblade.com',
        'web.industrybrains.com',
        'static.adblade.com',
        'static-cdn.adblade.com',
        'staticd.cdn.adblade.com',
        'static.industrybrains.com',
        'staticd.cdn.industrybrains.com',
    );
    if ( ! in_array( $host, $validDomains ) ) {
        adblade_log( $host . ' is not valid' );
        return;
    }

    // Make sure the request matches one we are familiar with.
    $requestMatch = false;
    $validRequestPatterns = array(
        '#^/?banners/images/\d+x\d+/.*\.[a-z]{3,4}$#',
        '#^/?css/.*css$#',
        '#^/?impsc?.php#',
        '#^/?js/ads/async/show.js$#',
    );

    foreach ( $validRequestPatterns as $pattern ) {
        if ( preg_match( $pattern, $path ) ) {
            $requestMatch = true;
            break;
        }
    }

    if ( ! $requestMatch ) {
        adblade_log( $path . ' is not valid' );
        return;
    }

    $url = sprintf( 'http://%s%s', $host, $path );
    adblade_log( 'Proxy for ' . $url );

    /**
     * A callback function to proxy requests.
     * @param $url
     * @return mixed (array|false) false if there was an error
     */
    $proxy = function ( $url ) {
        $options = get_option( ADBLADE_OPTIONS_KEY );
        $defaultDisclosure = 'Advertisement';

        $replacements = array(
            '#/?\Qimpsc.php?\E#'                         => sprintf( '/?%s=%s&', $options['query_var'], $options['redirect_actions']['impsc'] ),
            '#\Qhttp\Es?\Q://static-cdn.adblade.com\E#' => sprintf( '/?%s=%s&%spath=', $options['query_var'], $options['redirect_actions']['static'], $options['query_var'] ),
            '/\QAds by Adblade\E/'                       => $defaultDisclosure,
            '/\Q_common_dz.css\E/'                       => sprintf( '/?%s=%s&%spath=/css/zones/_common_dz.css', $options['query_var'], $options['redirect_actions']['static'], $options['query_var'] ),
        );

        if ( array_key_exists( 'replacements', $options ) ) {
            $replacements = array_merge( $replacements, $options['replacements'] );
        };

        // Make the request.
        $startTime = microtime();
        $response = wp_remote_get(
            $url,
            array(
                'user-agent'  => 'Adblade WordPress Plugin',
                'httpversion' => '1.1',
                'timeout'     => 10,
                'headers'     => array(
                    'X-Forwarded-For' => array_key_exists( 'HTTP_X_FORWARDED_FOR', $_SERVER ) ? filter_input( INPUT_SERVER, 'HTTP_X_FORWARDED_FOR' ) : filter_input( INPUT_SERVER, 'REMOTE_ADDR' ),
                ),
                'cookies'     => array(
                    '__tuid' => '6198487235506032324',
                ),
            )
        );
        adblade_log( sprintf( 'Request to %s took %s seconds', $url, microtime() - $startTime ) );

        if ( is_wp_error( $response ) ) {
            adblade_log( 'Error: ' . $response->get_error_message() );
            header( 'HTTP/1.0 500 Internal Server Error' );
            echo $response->get_error_message();
            return false;
        }

        $result = array(
            // Replace anything that an ad blocker might not like.
            'body' => preg_replace( array_keys( $replacements ), array_values( $replacements ), $response['body'] ),
        );

        // If one updates the plugin from a version before we did click rewrites, we need to add that option.
        if ( ! array_key_exists( 'click', $options['redirect_actions'] ) ) {
            $options['redirect_actions']['click'] = bin2hex( openssl_random_pseudo_bytes( 5 ) );
            update_option( ADBLADE_OPTIONS_KEY, $options, 'yes' );
        }

        // this replacement needs a callback, so must be done on its own
        $result['body'] = preg_replace_callback(
            '#\Qhttp\Es?\Q://web.adblade.com/clicks.php?\E([^"\']+)#',
            function( $matches ) {
                $options = get_option( ADBLADE_OPTIONS_KEY );
                return sprintf( '/?%s=%s&%spath=%s', $options['query_var'], $options['redirect_actions']['click'], $options['query_var'], urlencode( $matches[1] ) );
            },
            $result['body']
        );

        $result['headers'] = array();
        $headerKeys = array(
            'Content-Type',
            'Cache-control',
            'Etag',
            'Expires',
            'Last-modified',
            'Pragma',
        );

        foreach ( $headerKeys as $key ) {
            $keyLc = strtolower( $key );
            if ( isset( $response['headers'][ $keyLc ] ) ) {
                $result['headers'][ $key ] = $response['headers'][ $keyLc ];
            }
        }

        return $result;
    };

    $responseHandler = function ( $response ) {
        if ( $response ) {
            if ( array_key_exists( 'headers', $response ) ) {
                foreach ( $response['headers']  as $header => $value ) {
                    header( sprintf( '%s: %s', $header, $value ) );
                }
            }

            echo $response['body'];
            return;
        }
    };

    if ( $skipCache ) {
        $response = $proxy( $url );
        $responseHandler( $response );
        return;
    } else {
        // This is a static call, use a cache.
        $transientKey = hash( 'crc32b', $url );
        $transient = get_transient( $transientKey );
        if ( ! $transient ) {
            adblade_log( $url . ' is not cached' );
            // Get transient and set it.
            $response = $proxy( $url );

            if ( $response ) {
                adblade_log( sprintf( 'caching %s as %s', $url, $transientKey ) );

                $options = get_option( ADBLADE_OPTIONS_KEY );
                if ( ! array_key_exists( 'transients', $options ) || ! is_array( $options['transients'] ) ) {
                    $options['transients'] = array();
                }

                $options['transients'][] = $transientKey;
                update_option( ADBLADE_OPTIONS_KEY, $options, 'yes' );
                set_transient( $transientKey, $response, HOUR_IN_SECONDS );
            }

            $responseHandler( $response );
            return;
        } else {
            adblade_log( sprintf( 'using %s cache for %s', $transientKey, $url ) );
            $responseHandler($transient);
            return;
        }
    }
}

/**
 * Whether or not bypass is enabled.
 * @return boolean - True if enabled.
 */
function do_adblade_bypass() {
    $options = get_option( ADBLADE_OPTIONS_KEY );
    return false !== $options && array_key_exists( 'bypass', $options ) && intval( $options['bypass'] ) === 1;
}

/**
 * Log a message if debugging is enabled.
 * @param $message - The message to log.
 */
function adblade_log( $message ) {
    if ( WP_DEBUG === true ) {
        if ( is_array( $message ) || is_object( $message ) ) {
            error_log( print_r( $message, true ) );
        } else {
            error_log( '[ADBLADE] ' . $message );
        }
    }
}

function adblade_unique_path( array $previous_values, array $current_values, $target ) {
    $merged = array();
    // merge previous set of randomly generated paths ... to make sure there won't be a conflict
    if ( null != $previous_values ) {
        $merged = array_merge( $merged, array_values( $previous_values ) );
    }
    // merge any present-generation random paths, to make sure we aren't going to generate an invalid state
    if ( null != $current_values ) {
        $merged = array_merge( $merged, array_values( $current_values ) );
    }
    $merged = array_flip( $merged );
    $tries = 20;
    while ( $tries > 0 ) {
        $tries--;
        $random_path = bin2hex( openssl_random_pseudo_bytes( 5 ) );
        if ( ! array_key_exists( $random_path, $merged ) ) {
            return $random_path;
        }
    }
    // should never get here
    adblade_log( 'Unable to generate a unique path for: ' . $target );
}

function get_adblade_path_set( array $option_set ) {
    $retval = array();
    if ( null != $option_set ) {
        if ( array_key_exists( 'query_var', $option_set ) ) {
            $retval['query_var'] = $option_set['query_var'];
        }
        if ( array_key_exists( 'redirect_actions', $option_set ) && null != $option_set['redirect_actions'] && is_array( $option_set['redirect_actions'] ) ) {
            $retval = array_merge( $retval, $option_set['redirect_actions'] );
        }
    }
    return $retval;
}
