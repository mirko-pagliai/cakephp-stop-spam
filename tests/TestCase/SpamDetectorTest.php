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
    public function setUp(): void
    {
        parent::setUp();

        $this->SpamDetector = new SpamDetector();
    }

    /**
     * Called after every test method
     * @return void
     */
    public function tearDown(): void
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
     * Test for `__call()` magic method, missing arguments
     * @test
     */
    public function testCallMagicMethodMissingArguments()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('At least 1 argument required for `StopSpam\SpamDetector::username()` method');
        $this->SpamDetector->username();
    }

    /**
     * Test for `__call()` magic method, with multiple calls
     * @test
     */
    public function testCallMagicMethodWithMultipleCalls()
    {
        $expected = [
            'success' => 1,
            'email' => [
                [
                    'value' => 'test@example.com',
                    'frequency' => 0,
                    'appears' => 0,
                ],
                [
                    'value' => 'anothermail@example.com',
                    'frequency' => 0,
                    'appears' => 0,
                ],
            ],
        ];

        $result = $this->SpamDetector->email('test@example.com')->email('anothermail@example.com');
        $this->assertTrue($this->SpamDetector->verify());
        $this->assertSame($expected, $this->SpamDetector->getResult());

        $result = $this->SpamDetector->email('test@example.com', 'anothermail@example.com');
        $this->assertTrue($this->SpamDetector->verify());
        $this->assertSame($expected, $this->SpamDetector->getResult());
    }

    /**
     * Test for `verify()` method
     * @test
     */
    public function testVerify()
    {
        foreach ([
            ['email' => ['test@example.com']],
            ['email' => ['anothermail@example.com'], 'username' => ['mirko']],
        ] as $args) {
            $expected = ['success' => 1];
            $cacheKey = md5(serialize($args));
            $this->assertEmpty(Cache::read($cacheKey, 'StopSpam'));

            foreach ($args as $name => [$value]) {
                $expected[$name] = [compact('value') + ['frequency' => 0, 'appears' => 0]];
                $this->SpamDetector->$name($value);
            }
            $this->assertTrue($this->SpamDetector->verify());
            $this->assertSame($expected, $this->SpamDetector->getResult());

            $cache = Cache::read($cacheKey, 'StopSpam');
            $this->assertSame($expected, $cache);
        }

        $cacheKey = md5(serialize(['ip' => ['8.8.8.8']]));
        $this->assertEmpty(Cache::read($cacheKey, 'StopSpam'));

        $this->SpamDetector->ip('8.8.8.8');
        $this->assertTrue($this->SpamDetector->verify());

        $cache = Cache::read($cacheKey, 'StopSpam');
        $this->assertArrayKeysEqual(['success', 'ip'], $cache);
        $this->assertArrayKeysEqual(['value', 'frequency', 'appears', 'country', 'asn'], $cache['ip'][0]);

        //Tries with a real spammer
        $this->SpamDetector->username('spammer');
        $this->assertFalse($this->SpamDetector->verify());
        $this->assertSame([
            'success' => 1,
            'username' => [
                [
                    'value' => 'spammer',
                    'lastseen' => '2019-05-22 03:13:07',
                    'frequency' => 12,
                    'appears' => 1,
                    'confidence' => 72.73,
                ],
            ],
        ], $this->SpamDetector->getResult());

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
