<?php

use app\models\settings\AntragsgruenApp;

/**
 * @var AntragsgruenApp $params
 */

define('ANTRAGSGRUEN_VERSION', '3.0.0');
define('ANTRAGSGRUEN_HISTORY_URL', 'https://github.com/CatoTH/antragsgruen/blob/master/History.md');

// For PHPExcel
define('PCLZIP_TEMPORARY_DIR', $params->tmpDir);