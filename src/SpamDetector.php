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
namespace StopSpam;

use BadMethodCallException;
use Cake\Cache\Cache;
use Cake\Core\InstanceConfigTrait;
use Cake\Http\Client;
use Cake\Http\Exception\InternalErrorException;

/**
 * A spam detector
 * @method void email(string $email)
 * @method void ip(string $ip)
 * @method void username(string $username)
 */
class SpamDetector
{
    use InstanceConfigTrait;

    /**
     * @var \Cake\Http\Client
     */
    protected $Client;

    /**
     * Default configuration
     * @var array
     */
    protected $_defaultConfig = [
        'cache' => true,
    ];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * Construct
     * @param Client|null $Client A Client instance
     * @uses $Client
     */
    public function __construct($Client = null)
    {
        $this->Client = $Client ?: new Client();
    }

    /**
     * Magic method, is triggered when invoking inaccessible methods
     * @param string $name Method name
     * @param mixed $arguments Method arguments
     * @return $this
     * @throws BadMethodCallException
     * @uses $data
     */
    public function __call($name, array $arguments)
    {
        if (!in_array($name, ['email', 'ip', 'username'])) {
            throw new BadMethodCallException(__d('stop-spam', 'Method `{0}::{1}()` does not exist', get_class($this), $name));
        }
        if (count($arguments) !== 1) {
            throw new BadMethodCallException(__d('stop-spam', '1 argument required for `{0}::{1}()` method, {2} arguments passed', get_class($this), $name, count($arguments)));
        }

        $this->data += array_combine([$name], $arguments);

        return $this;
    }

    /**
     * Performs a single GET request and returns result
     * @param array $data The query data you want to send
     * @return array Result
     * @uses $Client
     */
    protected function _getResponse(array $data)
    {
        ksort($data);
        $cacheKey = md5(serialize($data));
        $result = $this->getConfig('cache') ? Cache::read($cacheKey, 'StopSpam') : false;

        if (!$result) {
            $result = $this->Client->get('http://api.stopforumspam.org/api', $data + ['json' => '']);
            $result = json_decode((string)$result->getBody(), true);

            if ($this->getConfig('cache')) {
                Cache::write($cacheKey, $result, 'StopSpam');
            }
        }

        return $result;
    }

    /**
     * Verifies, based on the set data, if it's a spammer
     * @return bool
     * @throws InternalErrorException
     * @uses _getResponse()
     * @uses $data
     */
    public function verify()
    {
        if (!$this->data) {
            throw new InternalErrorException(__d('stop-spam', 'Method `{0}()` was called without data to verify', __METHOD__));
        }
        $result = $this->_getResponse($this->data);

        if (isset($result['error'])) {
            throw new InternalErrorException(__d('stop-spam', 'Error from server: `{0}`', $result['error']));
        }
        $this->data = [];

        return !isset($result['success']) || $result['success'] != false;
    }
}
