<?php

use app\models\settings\AntragsgruenApp;

/**
 * @var AntragsgruenApp $params
 */

define('ANTRAGSGRUEN_VERSION', '3.8.0-pre');
define('ANTRAGSGRUEN_HISTORY_URL', 'https://github.com/CatoTH/antragsgruen/blob/v3/History.md');

// For PHPExcel
define('PCLZIP_TEMPORARY_DIR', $params->tmpDir);
