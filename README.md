# TextNg PHP Library

[![Latest Version on Packagist][ico-version-dev]][link-packagist]
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]

This is an unofficial client library for the TextNg SMS API. We provide an intuitive, stable interface to integrate TextNG SMS into your PHP project.

## Installation
Install the library using Composer. Please read the [Composer Documentation](https://getcomposer.org/doc/01-basic-usage.md) if you are unfamiliar with Composer or dependency managers in general.

``` bash
$ composer require elmage/textng-php
```

## Authentication

### API Key
```php
use Elmage\TextNg\Configuration;
use Elmage\TextNg\Client;

$configuration = new Configuration($apiKey, $senderName);
$client = Client::create($configuration);
```

## Usage
This is not intended to provide complete documentation of the API. For more detail, please refer to the [Official Documentation](https://textng.xyz/api).

**Get Unit Balance**

```php
$currencies = $client->getBalance();
```
**Send SMS**

```php
$currencies = $client->sendSMS($route, $phoneNumbers, $message, $bypassCode, $optionalParamsArray);
```


**Send OTP**

```php
// This method accepts only one phone number 
// and calls $client->sendSMS(..., [$phoneNumber], ...)
// passing the supplied phone number as the single element in an array

$currencies = $client->sendOTP($route = 3, $phoneNumber, $message, $bypassCode, $optionalParamsArray);
```

**Get Delivery Report**

```php
$currencies = $client->getDeliveryReport($reference, $req, $used_route);
```
```$req``` can take one of the 3 values ```all```, ```dnd``` or ```success``` (as specified in the [API DOCs](https://textng.xyz/api)) 


## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email mails4mage@gmail.com or use the issue tracker.

## Credits

- [Samuel Ogaba][link-author]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://poser.pugx.org/elmage/textng-php/version
[ico-version-dev]: https://poser.pugx.org/elmage/textng-php/v/unstable
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://travis-ci.com/elmage/textng-php.svg?branch=master
[ico-scrutinizer]: https://scrutinizer-ci.com/g/elmage/textng-php/badges/coverage.png?b=master
[ico-code-quality]: https://scrutinizer-ci.com/g/elmage/textng-php/badges/quality-score.png?b=master
[ico-downloads]: https://poser.pugx.org/elmage/textng-php/d/total.svg

[link-packagist]: https://packagist.org/packages/elmage/textng-php
[link-travis]: https://travis-ci.org/elmage/textng-php
[link-scrutinizer]: https://scrutinizer-ci.com/g/elmage/textng-php/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/elmage/textng-php
[link-downloads]: https://packagist.org/packages/elmage/textng-php
[link-author]: https://github.com/elmage
