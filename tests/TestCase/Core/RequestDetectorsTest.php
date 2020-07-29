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
namespace StopSpam\Test\TestCase\Core;

use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

/**
 * RequestDetectorsTest class
 */
class RequestDetectorsTest extends TestCase
{
    /**
     * Tests for `is('spammer')` detector
     * @test
     */
    public function testIsSpammer()
    {
        $Request = $this->getMockBuilder(ServerRequest::class)
            ->setMethods(null)
            ->getMock();

        $this->assertFalse($Request->isSpammer());
        $this->assertNull($Request->session()->read('allowed_ip'));

        $Request = $Request->env('REMOTE_ADDR', '99.99.99.99');
        $this->assertFalse($Request->isSpammer());
        $this->assertTrue($Request->session()->read('allowed_ip'));

        //Using an ip address reported as a spammer
        $Request->session()->delete('allowed_ip');
        $Request = $Request->env('REMOTE_ADDR', '31.133.120.18');
        $this->assertTrue($Request->isSpammer());
        $this->assertNull($Request->session()->read('allowed_ip'));
    }
}
