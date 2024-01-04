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

use Cake\Http\ServerRequest;
use StopSpam\SpamDetector;

/**
 * Adds `is('spammer')` detector.
 *
 * Checks if the user's IP address is reported as spammer.
 *
 * Example:
 * <code>
 * $this->getRequest()->isSpammer();
 * </code>
 */
ServerRequest::addDetector('spammer', function (ServerRequest $Request): bool {
    $clientIp = $Request->clientIp();

    //Not a spammer if:
    //  - the ip of the client is unknown;
    //  - is localhost;
    //  - the IP address has already been verified.
    if (!$clientIp || in_array($clientIp, ['127.0.0.1', '::1']) || $Request->getSession()->read('allowed_ip')) {
        return false;
    }

    $StopSpam = new SpamDetector();
    if (!$StopSpam->ip($clientIp)->verify()) {
        return true;
    }

    //In any other case, saves the result in the session
    $Request->getSession()->write('allowed_ip', true);

    return false;
});
