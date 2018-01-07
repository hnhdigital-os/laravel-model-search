```
___  ___          _      _ _____                     _     
|  \/  |         | |    | /  ___|                   | |    
| .  . | ___   __| | ___| \ `--.  ___  __ _ _ __ ___| |__  
| |\/| |/ _ \ / _` |/ _ \ |`--. \/ _ \/ _` | '__/ __| '_ \ 
| |  | | (_) | (_| |  __/ /\__/ /  __/ (_| | | | (__| | | |
\_|  |_/\___/ \__,_|\___|_\____/ \___|\__,_|_|  \___|_| |_|
                                                           
```

Search a model by providing an array of attributes. Correctly builds relationships and the appropiate query.

[![Latest Stable Version](https://poser.pugx.org/hnhdigital-os/laravel-model-search/v/stable.svg)](https://packagist.org/packages/hnhdigital-os/laravel-model-search) [![Total Downloads](https://poser.pugx.org/hnhdigital-os/laravel-model-search/downloads.svg)](https://packagist.org/packages/hnhdigital-os/laravel-model-search) [![Latest Unstable Version](https://poser.pugx.org/hnhdigital-os/laravel-model-search/v/unstable.svg)](https://packagist.org/packages/hnhdigital-os/laravel-model-search) [![Built for Laravel](https://img.shields.io/badge/Built_for-Laravel-green.svg)](https://laravel.com/) [![License](https://poser.pugx.org/hnhdigital-os/laravel-model-search/license.svg)](https://packagist.org/packages/hnhdigital-os/laravel-model-search)

[![Build Status](https://travis-ci.org/hnhdigital-os/laravel-model-search.svg?branch=master)](https://travis-ci.org/hnhdigital-os/laravel-model-search) [![StyleCI](https://styleci.io/repos/116483691/shield?branch=master)](https://styleci.io/repos/116483691) [![Test Coverage](https://codeclimate.com/github/hnhdigital-os/laravel-model-search/badges/coverage.svg)](https://codeclimate.com/github/hnhdigital-os/laravel-model-search/coverage) [![Issue Count](https://codeclimate.com/github/hnhdigital-os/laravel-model-search/badges/issue_count.svg)](https://codeclimate.com/github/hnhdigital-os/laravel-model-search) [![Code Climate](https://codeclimate.com/github/hnhdigital-os/laravel-model-search/badges/gpa.svg)](https://codeclimate.com/github/hnhdigital-os/laravel-model-search)

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

* Laravel 5.5
* PHP 7.1

## Install

Via composer:

`$ composer require hnhdigital-os/laravel-model-search ~1.0`

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

Some simple searches:

```php
SomeModel::search(['title' => 'Test']);
SomeModel::search(['title' => [['=', 'Test']]]);
SomeModel::search(['title' => '= Test']);
SomeModel::search(['title' => '!= Test']);

```

## Contributing

Please see [CONTRIBUTING](https://github.com/hnhdigital-os/laravel-model-search/blob/master/CONTRIBUTING.md) for details.

## Credits

* [Rocco Howard](https://github.com/RoccoHoward)
* [All Contributors](https://github.com/hnhdigital-os/laravel-model-search/contributors)

## License

The MIT License (MIT). Please see [License File](https://github.com/hnhdigital-os/laravel-model-search/blob/master/LICENSE) for more information.
