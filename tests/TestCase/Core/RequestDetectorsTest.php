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
namespace StopSpam\Test\TestCase\Core;

use Cake\Http\ServerRequest;
use MeTools\TestSuite\TestCase;

/**
 * RequestDetectorsTest class
 */
class RequestDetectorsTest extends TestCase
{
    /**
     * Tests for `is('spammer')` detector
     * @test
     */
    public function testIsSpammer(): void
    {
        $Request = new ServerRequest();

        $this->assertFalse($Request->is('spammer'));
        $this->assertNull($Request->getSession()->read('allowed_ip'));

        $Request = $Request->withEnv('REMOTE_ADDR', '99.99.99.99');
        $this->assertFalse($Request->is('spammer'));
        $this->assertTrue($Request->getSession()->read('allowed_ip'));

        //Using an ip address reported as a spammer
        $Request->getSession()->delete('allowed_ip');
        $Request = $Request->withEnv('REMOTE_ADDR', '44.242.181.201');
        $this->assertTrue($Request->is('spammer'));
        $this->assertNull($Request->getSession()->read('allowed_ip'));
    }
}
