<?php
/**
 * common.php
 *
 * Copyright (c) 2011-2015 PayU india
 * @author     Ayush Mittal
 * @copyright  2011-2015 PayU India
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
//// Create user agent string
// User agent constituents (for cURL)
define( 'PF_SOFTWARE_NAME', 'PrestaShop' );
define( 'PF_SOFTWARE_VER', Configuration::get('PS_INSTALL_VERSION') );
define( 'PF_MODULE_NAME', 'PayUbiz-Prestashop' );
define( 'PF_MODULE_VER', '2.0' );
define( 'PB_DEBUG', ( Configuration::get('PAYUBIZ_LOGS')  ? true : false ) );

// Features
// - PHP
$pbFeatures = 'PHP '. phpversion() .';';

// - cURL
if( in_array( 'curl', get_loaded_extensions() ) )
{
    define( 'PF_CURL', '' );
    $pbVersion = curl_version();
    $pbFeatures .= ' curl '. $pbVersion['version'] .';';
}
else
    $pbFeatures .= ' nocurl;';

// Create user agrent
define( 'PF_USER_AGENT', PF_SOFTWARE_NAME .'/'. PF_SOFTWARE_VER .' ('. trim( $pbFeatures ) .') '. PF_MODULE_NAME .'/'. PF_MODULE_VER );

// General Defines
define( 'PF_TIMEOUT', 15 );
define( 'PF_EPSILON', 0.01 );




function pblog( $msg = '', $close = false )
{
    static $fh = 0;
    global $module;

    // Only log if debugging is enabled
    if( PB_DEBUG )
    {
        if( $close )
        {
            fclose( $fh );
        }
        else
        {
            // If file doesn't exist, create it
            if( !$fh )
            {
                $pathinfo = pathinfo( __FILE__ );
                $fh = fopen( $pathinfo['dirname'] .'/paybiz.log', 'a+' );
            }

            // If file was successfully created
            if( $fh )
            {
                $line = date( 'Y-m-d H:i:s' ) .' : '. $msg ."\n";

                fwrite( $fh, $line );
            }
        }
    }
}

/**
 * pfGetData
 *  
 * @author Jonathan Smit
 */
function pbGetData()
{
    // Posted variables from ITN
    $pbData = $_POST;

    // Strip any slashes in data
    foreach( $pbData as $key => $val )
        $pbData[$key] = stripslashes( $val );

    // Return "false" if no data was received
    if( sizeof( $pbData ) == 0 )
        return( false );
    else
        return( $pbData );
}


?>