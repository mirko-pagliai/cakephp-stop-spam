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
namespace StopSpam\Test\TestCase;

use BadMethodCallException;
use Cake\Cache\Cache;
use Cake\Http\Exception\InternalErrorException;
use MeTools\TestSuite\TestCase;
use StopSpam\SpamDetector;

/**
 * SpamDetectorTest class
 */
class SpamDetectorTest extends TestCase
{
    /**
     * @var \StopSpam\SpamDetector
     */
    protected $SpamDetector;

    /**
     * Called before every test method
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->SpamDetector = new SpamDetector();
    }

    /**
     * Called after every test method
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        Cache::clearAll();
    }

    /**
     * Test for `__call()` magic method, with a no existing method
     * @test
     */
    public function testCallMagicMethodNoExistingMethod()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method `StopSpam\SpamDetector::noExisting()` does not exist');
        $this->SpamDetector->noExisting();
    }

    /**
     * Test for `__call()` magic method, with too many arguments
     * @test
     */
    public function testCallMagicMethodTooManyArguments()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('1 argument required for `StopSpam\SpamDetector::username()` method, 3 arguments passed');
        $this->SpamDetector->username('first', 'second', 'third');
    }

    /**
     * Test for `verify()` method
     * @test
     */
    public function testVerify()
    {
        foreach ([
            ['email' => 'test@example.com'],
            ['email' => 'anothermail@example.com', 'username' => 'mirko'],
        ] as $args) {
            $expectedCache = [];
            $cacheKey = md5(serialize($args));
            $this->assertEmpty(Cache::read($cacheKey, 'StopSpam'));

            foreach ($args as $name => $value) {
                $expectedCache[$name] = ['frequency' => 0, 'appears' => 0];
                $this->SpamDetector->$name($value);
            }
            $result = $this->SpamDetector->verify();
            $this->assertTrue($result);

            $cache = Cache::read($cacheKey, 'StopSpam');
            $this->assertEquals(['success' => 1] + $expectedCache, $cache);
        }

        $cacheKey = md5(serialize(['ip' => '8.8.8.8']));
        $this->assertEmpty(Cache::read($cacheKey, 'StopSpam'));

        $result = $this->SpamDetector->ip('8.8.8.8')->verify();
        $this->assertTrue($result);

        $cache = Cache::read($cacheKey, 'StopSpam');
        $this->assertArrayKeysEqual(['success', 'ip'], $cache);
        $this->assertArrayKeysEqual(['frequency', 'appears', 'country', 'asn'], $cache['ip']);

        //Called without data to verify
        $this->expectException(InternalErrorException::class);
        $this->expectExceptionMessage('Method `StopSpam\SpamDetector::verify()` was called without data to verify');
        $this->SpamDetector->verify();
    }

    /**
     * Test for `verify()` method, with error from server
     * @test
     */
    public function testVerifyWithErrorFromServer()
    {
        $SpamDetector = $this->getMockBuilder(SpamDetector::class)
            ->setMethods(['_getResponse'])
            ->getMock();
        $SpamDetector->method('_getResponse')->willReturn([
            'success' => 0,
            'error' => 'invalid ip',
        ]);

        $this->expectException(InternalErrorException::class);
        $this->expectExceptionMessage('Error from server: `invalid ip`');
        $SpamDetector->ip('invalidIpAddress')->verify();
    }
}
