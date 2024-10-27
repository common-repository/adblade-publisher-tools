<?php

header( 'Content-type: text/javascript' );

$options = get_option( 'adblade_options' );

$siteUrl         = site_url();
$strippedSiteUrl = preg_replace( '/^https?:\/\//', '', $siteUrl );
$queryVar = esc_attr( $options['query_var'] );
$redirImpsc = esc_attr( $options['redirect_actions']['impsc'] );
$redirShow = esc_attr( $options['redirect_actions']['show'] );
?>

jQuery(function() {
    'use strict';

    var adBlockDetected = function() {
        <?php if ( array_key_exists( 'qa', $options ) && intval( $options['qa'] ) === 1 ) : ?>
        // log event to Google Analytics
        if (typeof window.ga !== 'undefined') {
            window.ga('send', 'event', 'Adblock', 'Yes', {'nonInteraction': 1});
        }
        else if (typeof window._gaq !== 'undefined') {
            window._gaq.push(['_trackEvent', 'Adblock', 'Yes', undefined, undefined, true]);
        }
        <?php endif; ?>

        var $adbladeTags = jQuery('.adbladeads');
        $adbladeTags.each(function(idx, tag) {
            tag.setAttribute('data-host', '<?php echo $strippedSiteUrl ?>');
        });

        jQuery('iframe[src^="http://web.adblade"]').each(function(idx, iframe) {
            var src = iframe.src;
            iframe.src = src.replace(/^https?:\/\/web.adblade.com\/impsc.php\?(.+)/, '/?<?php printf( '%s=%s&$1', $queryVar, $redirImpsc ); ?>');
        });
        
        <?php if ( do_adblade_bypass() ) : ?>

    <?php
    if ( array_key_exists( 'adInjections', $options ) ) :
        foreach ( $options['adInjections'] as $injection ) :
            echo '(function() {';
            printf( 'var tag =  "%s";', addslashes( str_replace( array( "\r", "\n", 'http://web.adblade.com/js/ads/async/show.js', 'web.adblade.com' ), array( '', '', sprintf( '/?%s=%s', $queryVar, $redirShow ), $strippedSiteUrl ), $injection['tag'] ) ) );
            printf( "jQuery('%s').html(tag);", $injection['selector'] );
            echo '})();';
        endforeach;
    endif;
    ?>

        jQuery.getScript('/?<?php echo $queryVar . '=' . $redirShow ?>');
        <?php endif; ?>
    };

    // Recommended audit because AdBlock lock the file 'blockadblock.js' 
    // If the file is not called, the variable does not exist 'blockAdBlock'
    // This means that AdBlock is present
    if (typeof blockAdBlock === 'undefined') {
        adBlockDetected();
    } else {
        blockAdBlock.onDetected(adBlockDetected);
    }
});
