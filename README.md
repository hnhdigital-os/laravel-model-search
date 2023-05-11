```
___  ___          _      _ _____                     _     
|  \/  |         | |    | /  ___|                   | |    
| .  . | ___   __| | ___| \ `--.  ___  __ _ _ __ ___| |__  
| |\/| |/ _ \ / _` |/ _ \ |`--. \/ _ \/ _` | '__/ __| '_ \ 
| |  | | (_) | (_| |  __/ /\__/ /  __/ (_| | | | (__| | | |
\_|  |_/\___/ \__,_|\___|_\____/ \___|\__,_|_|  \___|_| |_|
                                                           
```

Search a model by providing an array of attributes. Correctly builds relationships and the appropiate query. Provides inline operaters for improved user searching.

[![Latest Stable Version](https://poser.pugx.org/hnhdigital-os/laravel-model-search/v/stable.svg)](https://packagist.org/packages/hnhdigital-os/laravel-model-search) [![Total Downloads](https://poser.pugx.org/hnhdigital-os/laravel-model-search/downloads.svg)](https://packagist.org/packages/hnhdigital-os/laravel-model-search) [![Latest Unstable Version](https://poser.pugx.org/hnhdigital-os/laravel-model-search/v/unstable.svg)](https://packagist.org/packages/hnhdigital-os/laravel-model-search) [![Built for Laravel](https://img.shields.io/badge/Built_for-Laravel-green.svg)](https://laravel.com/) [![License](https://poser.pugx.org/hnhdigital-os/laravel-model-search/license.svg)](https://packagist.org/packages/hnhdigital-os/laravel-model-search) [![Donate to this project using Patreon](https://img.shields.io/badge/patreon-donate-yellow.svg)](https://patreon.com/RoccoHoward)

This package has been developed by H&H|Digital, an Australian botique developer. Visit us at [hnh.digital](http://hnh.digital).

## Documentation

* [Requirements](#requirements)
* [Installation](#install)
* [Configuration](#configuration)
* [Usage](#usage)
* [Contributing](#contributing)
* [Credits](#credits)
* [License](#license)

## Requirements

* PHP 8.0.2
* Laravel 9

## Install

Via composer:

`$ composer require hnhdigital-os/laravel-model-search ~3.0`

## Configuration

Enable the trait on any given model.

```php
use HnhDigital\ModelSearch\ModelTrait as ModelSearchTrait;

class SomeModel extends Model
{
    use ModelSearchTrait;
}
```

## Usage

A simple search could be:

```php
SomeModel::search(['title' => 'Test']);
SomeModel::search(['title' => [['=', 'Test']]]);
SomeModel::search(['title' => '= Test']);
SomeModel::search(['title' => '!= Test']);

```

For a better look at what is possible, check out the `tests/ModelTest.php` test case for an extensive list of possiblities.

## Contributing

Please see [CONTRIBUTING](https://github.com/hnhdigital-os/laravel-model-search/blob/master/CONTRIBUTING.md) for details.

## Credits

* [Rocco Howard](https://github.com/RoccoHoward)
* [All Contributors](https://github.com/hnhdigital-os/laravel-model-search/contributors)

## License

The MIT License (MIT). Please see [License File](https://github.com/hnhdigital-os/laravel-model-search/blob/master/LICENSE) for more information.
