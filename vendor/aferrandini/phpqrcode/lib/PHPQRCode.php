<?php
/**
 * PHPQRCode.php
 *
 * Created by arielferrandini
 */
$QR_BASEDIR = dirname(__FILE__).DIRECTORY_SEPARATOR;

// Required libs

/*
 * PHP QR Code encoder
 *
 * Config file, feel free to modify
 */

if (!defined('QR_CACHEABLE')) define('QR_CACHEABLE', true);                                                               // use cache - more disk reads but less CPU power, masks and format templates are stored there
if (!defined('QR_CACHE_DIR')) define('QR_CACHE_DIR', dirname(__FILE__).DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR);  // used when QR_CACHEABLE === true
if (!defined('QR_LOG_DIR')) define('QR_LOG_DIR', dirname(__FILE__).DIRECTORY_SEPARATOR);                                // default error logs dir

if (!defined('QR_FIND_BEST_MASK')) define('QR_FIND_BEST_MASK', true);                                                          // if true, estimates best mask (spec. default, but extremally slow; set to false to significant performance boost but (propably) worst quality code
if (!defined('QR_FIND_FROM_RANDOM')) define('QR_FIND_FROM_RANDOM', false);                                                       // if false, checks all masks available, otherwise value tells count of masks need to be checked, mask id are got randomly
if (!defined('QR_DEFAULT_MASK')) define('QR_DEFAULT_MASK', 2);                                                               // when QR_FIND_BEST_MASK === false

if (!defined('QR_PNG_MAXIMUM_SIZE')) define('QR_PNG_MAXIMUM_SIZE',  1024);


// Supported output formats

if (!defined('QR_FORMAT_TEXT')) define('QR_FORMAT_TEXT', 0);
if (!defined('QR_FORMAT_PNG')) define('QR_FORMAT_PNG',  1);

/** PHPQRCode root directory */
if (!defined('PHPQRCODE_ROOT')) {
	if (!defined('PHPQRCODE_ROOT')) define('PHPQRCODE_ROOT', dirname(__FILE__) . '/');
	require(PHPQRCODE_ROOT . 'PHPQRCode/Autoloader.php');
}

class PHPQRCode
{

}
