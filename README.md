## Laravel Image Optimizer
[![Packagist License](https://poser.pugx.org/dmitrykonovalchuk/laravel-image-optimizer/license.png)](http://choosealicense.com/licenses/mit/)
[![Latest Stable Version](https://poser.pugx.org/dmitrykonovalchuk/laravel-image-optimizer/version.png)](https://packagist.org/packages/dmitrykonovalchuk/laravel-image-optimizer)
[![Total Downloads](https://poser.pugx.org/dmitrykonovalchuk/laravel-image-optimizer/d/total.png)](https://packagist.org/packages/dmitrykonovalchuk/laravel-image-optimizer)

This is a package for Laravel 5 that optimize images for Google PageSpeed Insights requirements.

## Requirements

This package uses the following tools in your system:
 - [optipng](http://optipng.sourceforge.net/) (tested on v0.7.6)
 - [jpegoptim](http://freecode.com/projects/jpegoptim/) (tested on v1.4.4)
 
## Installation


Require this package with composer:

```shell
composer require dmitrykonovalchuk/laravel-image-optimizer
```

After updating composer, add the ServiceProvider to the providers array in config/app.php

```php
Konovalchuk\LaravelImageOptimizer\ServiceProvider::class,
```

Copy the package config to your local config with the publish command:

```shell
php artisan vendor:publish --provider="Konovalchuk\LaravelImageOptimizer\ServiceProvider"
```

## Usage

Set your directories pathes that will be optimized in config/image-optimizer.php

```php
'dirs' => [
  public_path('media'),            // all png/jpeg images in folder public/media will be optimized recursively
  public_path('upload') => [
    'types'     => ['images/png'], // array of mime types, that will be optimized (now supported image/png and image/jpeg)
    'recursive' => false,          // search images only in root directory (public/upload)
  ],
],
```

Run artisan command (or add to cron) with root priveleges (to avoid problems with permissions)
```shell
sudo php artisan image-optimizer:run
```

All files in your directories will be optimized with max optimization levels (takes some time).
