<?php
// tcpdf_config.php
// TCPDF Configuration file

if (!defined('K_TCPDF_EXTERNAL_CONFIG')) {
    
    // Default configuration for TCPDF
    define('K_TCPDF_EXTERNAL_CONFIG', true);
    
    // Installation path
    define('K_PATH_MAIN', dirname(__FILE__) . '/tcpdf/');
    
    // URL path
    define('K_PATH_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/tcpdf/');
    
    // Fonts path
    define('K_PATH_FONTS', K_PATH_MAIN . 'fonts/');
    
    // Cache directory for temporary files
    define('K_PATH_CACHE', K_PATH_MAIN . 'cache/');
    
    // Images path
    define('K_PATH_IMAGES', K_PATH_MAIN . 'images/');
    
    // Blank image
    define('K_BLANK_IMAGE', K_PATH_IMAGES . '_blank.png');
    
    // Page format
    define('PDF_PAGE_FORMAT', 'A4');
    
    // Page orientation (P=portrait, L=landscape)
    define('PDF_PAGE_ORIENTATION', 'P');
    
    // Document creator
    define('PDF_CREATOR', 'TCPDF');
    
    // Document author
    define('PDF_AUTHOR', 'Ask Visa Portal');
    
    // Header title
    define('PDF_HEADER_TITLE', 'Visa Application Summary');
    
    // Header string
    define('PDF_HEADER_STRING', "by Ask Visa Portal\n" . date('Y-m-d H:i:s'));
    
    // Document unit of measure [pt=point, mm=millimeter, cm=centimeter, in=inch]
    define('PDF_UNIT', 'mm');
    
    // Header margin
    define('PDF_MARGIN_HEADER', 5);
    
    // Footer margin
    define('PDF_MARGIN_FOOTER', 10);
    
    // Top margin
    define('PDF_MARGIN_TOP', 27);
    
    // Bottom margin
    define('PDF_MARGIN_BOTTOM', 25);
    
    // Left margin
    define('PDF_MARGIN_LEFT', 15);
    
    // Right margin
    define('PDF_MARGIN_RIGHT', 15);
    
    // Default main font name
    define('PDF_FONT_NAME_MAIN', 'helvetica');
    
    // Default main font size
    define('PDF_FONT_SIZE_MAIN', 10);
    
    // Default data font name
    define('PDF_FONT_NAME_DATA', 'helvetica');
    
    // Default data font size
    define('PDF_FONT_SIZE_DATA', 8);
    
    // Default monospaced font name
    define('PDF_FONT_MONOSPACED', 'courier');
    
    // Ratio used to adjust the conversion of pixels to user units
    define('PDF_IMAGE_SCALE_RATIO', 1.25);
    
    // Magnification factor for titles
    define('HEAD_MAGNIFICATION', 1.1);
    
    // Height of cell respect font height
    define('K_CELL_HEIGHT_RATIO', 1.25);
    
    // Title magnification respect main font size
    define('K_TITLE_MAGNIFICATION', 1.3);
    
    // Reduction factor for small font
    define('K_SMALL_RATIO', 2/3);
    
    // Set to true to enable the special procedure used to avoid the overlappind of symbols on Thai language
    define('K_THAI_TOPCHARS', true);
    
    // If true allows to call TCPDF methods using HTML syntax
    define('K_TCPDF_CALLS_IN_HTML', false);
    
    // If true and PHP version is greater than 5, then the Error() method throw new exception instead of terminating the execution.
    define('K_TCPDF_THROW_EXCEPTION_ERROR', false);
}
