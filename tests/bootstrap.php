<?php
declare(strict_types=1);

/**
 * This file is part of cakephp-stop-spam.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright   Copyright (c) Mirko Pagliai
 * @link        https://github.com/mirko-pagliai/cakephp-stop-spam
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Cache\Cache;
use Cake\Core\Configure;

define('ROOT', dirname(__DIR__) . DS);
const CORE_PATH = ROOT . 'vendor' . DS . 'cakephp' . DS . 'cakephp' . DS;
const TESTS = ROOT . 'tests';
const APP = ROOT . 'tests' . DS . 'test_app' . DS;
const APP_DIR = 'test_app';
const WWW_ROOT = APP . 'webroot' . DS;
define('TMP', sys_get_temp_dir() . DS . 'cakephp-stop-spam' . DS);
const CACHE = TMP . 'cache' . DS;
const LOGS = TMP . 'cakephp_log' . DS;

foreach ([TMP . 'tests', LOGS, CACHE] as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once CORE_PATH . 'config' . DS . 'bootstrap.php';

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');
ini_set('intl.default_locale', 'en_US');

Configure::write('debug', true);
Configure::write('App', [
    'namespace' => 'App',
    'encoding' => 'UTF-8',
    'base' => false,
    'baseUrl' => false,
    'dir' => APP_DIR,
    'webroot' => 'webroot',
    'wwwRoot' => WWW_ROOT,
    'fullBaseUrl' => 'http://localhost',
    'imageBaseUrl' => 'img/',
    'jsBaseUrl' => 'js/',
    'cssBaseUrl' => 'css/',
]);
Configure::write('Session', ['defaults' => 'php']);

Cache::setConfig([
    '_cake_core_' => [
        'engine' => 'File',
        'prefix' => 'cake_core_',
        'serialize' => true,
    ],
]);

require_once ROOT . 'config/bootstrap.php';

$_SERVER['PHP_SELF'] = '/';
