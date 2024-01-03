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
namespace StopSpam;

use Cake\Cache\Cache;
use Cake\Core\InstanceConfigTrait;
use Cake\Http\Client;
use Cake\Utility\Hash;
use LogicException;

/**
 * A spam detector
 * @method \StopSpam\SpamDetector email(string ...$email) Sets an email address to verify
 * @method \StopSpam\SpamDetector ip(string ...$ip) Sets an IP address to verify
 * @method \StopSpam\SpamDetector username(string ...$username) Sets a username to verify
 */
class SpamDetector
{
    use InstanceConfigTrait;

    /**
     * @var \Cake\Http\Client
     */
    public Client $Client;

    /**
     * Default configuration
     * @var array
     */
    protected array $_defaultConfig = [
        'cache' => true,
    ];

    /**
     * Data to be verified
     * @var array
     */
    protected array $data = [];

    /**
     * Results of the last verification
     * @var array
     */
    protected array $result = [];

    /**
     * Construct
     * @param \Cake\Http\Client|null $Client A Client instance
     */
    public function __construct(?Client $Client = null)
    {
        $this->Client = $Client ?: new Client();
    }

    /**
     * Magic method, is triggered when invoking inaccessible methods
     * @param string $name Method name
     * @param array $arguments Method arguments
     * @return $this
     * @throw \LogicException
     */
    public function __call(string $name, array $arguments)
    {
        $methodName = sprintf('%s::%s', get_class($this), $name);
        if (!in_array($name, ['email', 'ip', 'username'])) {
            throw new LogicException(__d('stop-spam', 'Method `{0}()` does not exist', $methodName));
        }
        if (!$arguments) {
            throw new LogicException(__d('stop-spam', 'At least 1 argument required for `{0}()` method', $methodName));
        }

        $this->data[$name] = array_merge($this->data[$name] ?? [], $arguments);

        return $this;
    }

    /**
     * Performs a single GET request and returns result
     * @param array $data The query data you want to send
     * @return array Result
     */
    protected function _getResponse(array $data): array
    {
        ksort($data);

        return $this->getConfig('cache') ? Cache::remember(md5(serialize($data)), function () use ($data): array {
            return $this->Client->get('https://api.stopforumspam.org/api', $data + ['json' => ''])->getJson();
        }, 'StopSpam') : [];
    }

    /**
     * Returns results of the last verification
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * Verifies, based on the set data, if it's a spammer
     * @return bool Returns `false` if certainly at least one of the parameters has been reported as a spammer
     * @throws \LogicException
     */
    public function verify(): bool
    {
        if (!$this->data) {
            throw new LogicException(__d('stop-spam', 'Method `{0}()` was called without data to verify', __METHOD__));
        }
        $this->result = $this->_getResponse($this->data);

        if (array_key_exists('error', $this->result)) {
            throw new LogicException(__d('stop-spam', 'Error from server: `{0}`', $this->result['error']));
        }
        $this->data = [];

        return !Hash::check($this->result, '{s}.{n}[appears=1]');
    }
}
