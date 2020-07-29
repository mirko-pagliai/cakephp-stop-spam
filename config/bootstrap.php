<?php

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

if (!Cache::config('StopSpam')) {
    Cache::config('StopSpam', [
        'className' => 'File',
        'duration' => '+1 month',
        'path' => CACHE,
        'prefix' => 'stop_spam_',
    ]);
}
