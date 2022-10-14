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

use Cake\Cache\Cache;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Exception;
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
    protected SpamDetector $SpamDetector;

    /**
     * Called before every test method
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $Client = $this->getMockBuilder(Client::class)
            ->onlyMethods(['get'])
            ->getMock();

        $Client->expects($this->any())
            ->method('get')
            ->willReturnCallback(function (string $url, $data = []): Response {
                //Gets the `Response` instance already saved in the test files
                $file = TESTS . DS . 'responses' . DS . md5(serialize($data));
                if (file_exists($file)) {
                    return new Response([], file_get_contents($file) ?: '');
                }

                echo PHP_EOL . 'Creating file `' . $file . '`...' . PHP_EOL;
                $response = (new Client())->get($url, $data);
                file_put_contents($file, $response->getStringBody());

                return $response;
            });

        if (empty($this->SpamDetector)) {
            $this->SpamDetector = new SpamDetector($Client);
        }
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
    public function testCallMagicMethodNoExistingMethod(): void
    {
        $this->expectExceptionMessage('Method `StopSpam\SpamDetector::noExisting()` does not exist');
        (new SpamDetector())->noExisting();
    }

    /**
     * Test for `__call()` magic method, missing arguments
     * @test
     */
    public function testCallMagicMethodMissingArguments(): void
    {
        $this->expectExceptionMessage('At least 1 argument required for `StopSpam\SpamDetector::username()` method');
        (new SpamDetector())->username();
    }

    /**
     * Test for `__call()` magic method, with multiple calls
     * @test
     */
    public function testCallMagicMethodWithMultipleCalls(): void
    {
        $expected = [
            'success' => 1,
            'email' => [
                [
                    'value' => 'anothermail@example.com',
                    'frequency' => 0,
                    'appears' => 0,
                ],
                [
                    'value' => 'test@example.com',
                    'frequency' => 0,
                    'appears' => 0,
                ],
            ],
        ];

        $this->SpamDetector->email('test@example.com')->email('anothermail@example.com');
        $this->assertTrue($this->SpamDetector->verify());
        $this->assertSame($expected, $this->SpamDetector->getResult());

        $this->SpamDetector->email('test@example.com', 'anothermail@example.com');
        $this->assertTrue($this->SpamDetector->verify());
        $this->assertSame($expected, $this->SpamDetector->getResult());
    }

    /**
     * Test for `verify()` method
     * @test
     */
    public function testVerify(): void
    {
        foreach ([
            ['email' => ['test@example.com']],
            ['email' => ['anothermail@example.com'], 'username' => ['myusernameforexample']],
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

        $cacheKey = md5(serialize(['ip' => ['8.8.4.4']]));
        $this->assertEmpty(Cache::read($cacheKey, 'StopSpam'));

        $this->SpamDetector->ip('8.8.4.4');
        $this->assertTrue($this->SpamDetector->verify());

        $cache = Cache::read($cacheKey, 'StopSpam');
        $this->assertArrayKeysEqual(['success', 'ip'], $cache);
        $this->assertSame('8.8.4.4', $cache['ip'][0]['value']);
        $this->assertSame(0, $cache['ip'][0]['frequency']);
        $this->assertSame(0, $cache['ip'][0]['appears']);

        //Tries with a real spammer
        $this->SpamDetector->username('spammer');
        $this->assertFalse($this->SpamDetector->verify());
        $result = $this->SpamDetector->getResult();
        $this->assertArrayKeysEqual(['success', 'username'], $result);
        $this->assertArrayKeysEqual(['value', 'lastseen', 'frequency', 'appears', 'confidence'], $result['username'][0]);
        $this->assertGreaterThan(0, $result['username'][0]['frequency']);
        $this->assertGreaterThan(0, $result['username'][0]['appears']);

        //Called without data to verify
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Method `StopSpam\SpamDetector::verify()` was called without data to verify');
        $this->SpamDetector->verify();
    }

    /**
     * Test for `verify()` method, with error from server
     * @test
     */
    public function testVerifyWithErrorFromServer(): void
    {
        $SpamDetector = @$this->getMockBuilder(SpamDetector::class)
            ->setMethods(['_getResponse'])
            ->getMock();
        $SpamDetector->method('_getResponse')->willReturn([
            'success' => 0,
            'error' => 'invalid ip',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error from server: `invalid ip`');
        $SpamDetector->ip('invalidIpAddress')->verify();
    }
}
