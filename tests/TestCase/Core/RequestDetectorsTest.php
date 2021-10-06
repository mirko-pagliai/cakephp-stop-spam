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

use Cake\Cache\Cache;
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

        Cache::write(md5(serialize(['ip' => ['99.99.99.99']])), ['success' => 1, 'ip' => [[
            'value' => '99.99.99.99',
            'frequency' => 0,
            'appears' => 0,
            'asn' => 7018,
            'country' => 'us',
        ]]], 'StopSpam');
        $Request = $Request->withEnv('REMOTE_ADDR', '99.99.99.99');
        $this->assertFalse($Request->is('spammer'));
        $this->assertTrue($Request->getSession()->read('allowed_ip'));

        //Using an ip address reported as a spammer
        Cache::write(md5(serialize(['ip' => ['44.242.181.201']])), ['success' => 1, 'ip' => [[
            'value' => '44.242.181.201',
            'appears' => 1,
            'frequency' => 1,
            'lastseen' => '2021-07-28 08:14:35',
            'delegated' => 'us',
            'asn' => 16509,
            'country' => 'us',
            'confidence' => 0.06,
        ]]], 'StopSpam');
        $Request->getSession()->delete('allowed_ip');
        $Request = $Request->withEnv('REMOTE_ADDR', '44.242.181.201');
        $this->assertTrue($Request->is('spammer'));
        $this->assertNull($Request->getSession()->read('allowed_ip'));
    }
}
