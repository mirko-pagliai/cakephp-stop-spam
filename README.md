# cakephp-stop-spam

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)
[![CI](https://github.com/mirko-pagliai/cakephp-stop-spam/actions/workflows/ci.yml/badge.svg)](https://github.com/mirko-pagliai/cakephp-stop-spam/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/mirko-pagliai/cakephp-stop-spam/branch/master/graph/badge.svg)](https://codecov.io/gh/mirko-pagliai/cakephp-stop-spam)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/10ac26f709d94aaa8e6bbb5d07d7b050)](https://www.codacy.com/gh/mirko-pagliai/cakephp-stop-spam/dashboard?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=mirko-pagliai/cakephp-stop-spam&amp;utm_campaign=Badge_Grade)
[![CodeFactor](https://www.codefactor.io/repository/github/mirko-pagliai/cakephp-stop-spam/badge)](https://www.codefactor.io/repository/github/mirko-pagliai/cakephp-stop-spam)

*cakephp-stop-spam* is a CakePHP plugin that allows you to check if a username,
email address or ip address has been reported as a spammer using services and
APIs offered by [stopforumspam.org](https://stopforumspam.com).

Did you like this plugin? Its development requires a lot of time for me.
Please consider the possibility of making [a donation](//paypal.me/mirkopagliai):
even a coffee is enough! Thank you.

[![Make a donation](https://www.paypalobjects.com/webstatic/mktg/logo-center/logo_paypal_carte.jpg)](//paypal.me/mirkopagliai)

***

* [Installation](#installation)
        + [Installation on older CakePHP and PHP versions](#installation-on-older-cakephp-and-php-versions)
                - [For PHP 7.2 and CakePHP 4 or later](#for-php-72-and-cakephp-4-or-later)
                - [For PHP 5.6 and CakePHP 3 or later](#for-php-56-and-cakephp-3-or-later)
* [How to use](#how-to-use)
        + [The Request detector](#the-request-detector)
        + [How to create a validation rule](#how-to-create-a-validation-rule)
        + [How to configure the cache](#how-to-configure-the-cache)
* [Versioning](#versioning)

## Installation
You can install the plugin via composer:

```bash
$ composer require --prefer-dist mirko-pagliai/cakephp-stop-spam
```

Then you have to load the plugin. For more information on how to load the plugin,
please refer to the [Cookbook](//book.cakephp.org/4.0/en/plugins.html#loading-a-plugin).

Simply, you can execute the shell command to enable the plugin:

```bash
bin/cake plugin load StopSpam
```

This would update your application's bootstrap method.

### Installation on older CakePHP and PHP versions
Recent packages and the master branch require at least CakePHP 5.0 and PHP 8.1
and the current development of the code is based on these and later versions of
CakePHP and PHP.
However, there are still some branches compatible with previous versions of
CakePHP and PHP.

#### For PHP 7.2 and CakePHP 4 or later
The [php7.2](//github.com/mirko-pagliai/cakephp-stop-spam/tree/php7.2) branch
requires at least PHP `>=7.2` and CakePHP `^4.0`.

In this case, you can install the package as well:
```bash
$ composer require --prefer-dist mirko-pagliai/cakephp-stop-spam:dev-php7.2
```

Note that the `php7.2` branch will no longer be updated as of January 7, 2024,
except for security patches, and it matches the
[1.1.8](//github.com/mirko-pagliai/cakephp-stop-spam/releases/tag/1.1.8) version.

#### For PHP 5.6 and CakePHP 3 or later
The [cakephp3](//github.com/mirko-pagliai/cakephp-stop-spam/tree/cakephp3) branch
requires at least PHP 5.6 and CakePHP 3.

In this case, you can install the package as well:
```bash
$ composer require --prefer-dist mirko-pagliai/cakephp-stop-spam:dev-cakephp3
```

Note that the `cakephp3` branch will no longer be updated as of May 7, 2021,
except for security patches, and it matches the
[1.1.3](//github.com/mirko-pagliai/cakephp-stop-spam/releases/tag/1.1.3) version.

## How to use
After instantiating the class, you can use `email()`, `ip()` and `username()`
methods to specify the values to be verified and finally use the `verify()` method
to get the result. Example:

```php
$SpamDetector = new SpamDetector();
$SpamDetector->email('test@example.com')
    ->ip('8.8.8.8')
    ->username('mirko');
$result = $SpamDetector->verify();
```

The `verify()` method returns `false` if ***certainly at least one of the
parameters*** has been reported as a spammer, otherwise returns `true`.
In other words, ***the `verify()` method verifies that it is not a spammer***.

If the API returns an error (for example if you specify an invalid ip address,
or in any case if you generate a request that cannot be interpreted), an exception
will be throwed.

`email()`, `ip()` and `username()` methods are chainable and can be called more
than once, or you can pass multiple arguments.
Example (the `email()`) method is called multiple times, while the `ip()` method
is called with multiple arguments):

```php
$SpamDetector = new SpamDetector();
$SpamDetector->email('test@example.com');
$SpamDetector->email('anothermail@example.com');
$SpamDetector->ip('8.8.8.8', '8.8.4.4');
$result = $SpamDetector->verify();
```

### The Request detector
The plugin also adds the `is('spammer')` request detector. This detector checks if the user's IP address is reported as spammer.
Wherever the server request is accessible, you can use the detector. An example:

```php
class PagesController extends AppController
{
    /**
     * A "view" action for PagesController
     */
    public function view()
    {
		$isSpammer = $this->getRequest()->is('spammer');

		if ($isSpammer) {
			throw new InternalErrorException('Ehi, you are a spammer! Get out of my site!');
		}

		// ...
	}
}
```

The detector checks if the IP address of the user client is reported as a spammer. This happens as described above. If the IP address is not reported, the detector uses the session to store the control result.

This is very convenient and fast. It avoids repeating the code and also, using the session to memorize the result, it does not even use the cache.

### How to create a validation rule
The plugin can also be used as a validation rule.
Just an example:

```php
class ContactUsForm extends Form
{
    protected function _buildValidator(Validator $validator)
    {
	    //some rules for my form...

        $validator->add('email', [
            'notSpammer' => [
                'message' => 'Sorry, this email address has been reported as a spammer!',
                'rule' => function ($value, $context) {
                    return (new SpamDetector())->email($value)->verify();
                },
            ],
        ]);

        return $validator;
    }
}
```

In this case, the validator will verify that the email address has not been reported as a spammer.

For more information on how to create and use validation rules, please refer to the
[Cookbook](https://book.cakephp.org/4/en/core-libraries/validation.html#using-custom-validation-rules).

### How to configure the cache
This plugin uses the [HTTP Client](https://book.cakephp.org/4/en/core-libraries/httpclient.html)
to make requests and get responses, which are inspected and processed one by one.
This can take a lot of resources and generate a lot of network traffic. For this
reason, the plugin uses the cache (except for error responses).

By default, the cache is active. You can enable or disable it using the `cache`
option. Example:

```php
$SpamDetector = new SpamDetector();
//Disables the cache
$SpamDetector->setConfig('cache', false);
//Re-enables the cache
$SpamDetector->setConfig('cache', true);
```

`StopSpam` will use the homonymous cache engine
[defined in its bootstrap file](https://github.com/mirko-pagliai/cakephp-stop-spam/blob/master/config/bootstrap.php#L18).

If you want to use your own cache engine or if you want to use a different
onfiguration than the default one, then you have to configure the `StopSpam`
cache engine **before** loading the plugin. Example:

```php
Cache::setConfig('StopSpam, [
    'className' => 'File',
    'duration' => '+1 month',
    'path' => CACHE,
    'prefix' => 'stop_spam_',
]);
```

For more information on how to configure a cache engine, please refer to the
[Cookbook](https://book.cakephp.org/4/en/core-libraries/caching.html#configuring-cache-engines).

Pay particular attention to what is reported by
[stopforumspam.org](https://www.stopforumspam.com/usage) :

> This API is NOT to be used as a general software firewall. Checking every incoming connection against the API will be treated as a denial of service attack against us and will result in the immediate blocking of any offending IP addresses.

For this reason, it is advisable to use the cache, limit the requests and use
the session to mark a user as already verified.

## Versioning
For transparency and insight into our release cycle and to maintain backward compatibility,
*cakephp-stop-spam* will be maintained under the [Semantic Versioning guidelines](http://semver.org).
