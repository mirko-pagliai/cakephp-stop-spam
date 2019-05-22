# cakephp-stop-spam

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)
[![Build Status](https://travis-ci.org/mirko-pagliai/cakephp-stop-spam.svg?branch=master)](https://travis-ci.org/mirko-pagliai/cakephp-stop-spam)
[![Build status](https://ci.appveyor.com/api/projects/status/rxadqjs0blb906jq?svg=true)](https://ci.appveyor.com/project/mirko-pagliai/cakephp-entity-file-log)
[![codecov](https://codecov.io/gh/mirko-pagliai/cakephp-stop-spam/branch/master/graph/badge.svg)](https://codecov.io/gh/mirko-pagliai/cakephp-stop-spam)

*cakephp-stop-spam* is a CakePHP plugin that allows you to check if a username,
email address or ip address has been reported as a spammer using services and
APIs offered by [stopforumspam.org](https://stopforumspam.com).

Did you like this plugin? Its development requires a lot of time for me.  
Please consider the possibility of making [a donation](//paypal.me/mirkopagliai):
even a coffee is enough! Thank you.

[![Make a donation](https://www.paypalobjects.com/webstatic/mktg/logo-center/logo_paypal_carte.jpg)](//paypal.me/mirkopagliai)

## Installation
You can install the plugin via composer:

    $ composer require --prefer-dist mirko-pagliai/cakephp-stop-spam

**NOTE: the latest version available requires at least CakePHP 3.7**.

Instead, the [cakephp3.2](//github.com/mirko-pagliai/cakephp-stop-spam/tree/cakephp3.2)
branch is compatible with all previous versions of CakePHP from version 3.2. 
In this case, you can install the package as well:

    $ composer require --prefer-dist mirko-pagliai/cakephp-stop-spam:dev-cakephp3.2

After installation, you have to edit `APP/config/bootstrap.php` to load the plugin:

    Plugin::load('StopSpam', ['bootstrap' => true]);

For more information on how to load the plugin, please refer to the 
[Cookbook](https://book.cakephp.org/3.0/en/core-libraries/logging.html#logging-configuration).

## How to use

## Versioning
For transparency and insight into our release cycle and to maintain backward compatibility, 
MeTools will be maintained under the [Semantic Versioning guidelines](http://semver.org).
