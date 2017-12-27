# ![logo](/examples/assets/logo2.png) Atomic [![Latest Stable Version](https://poser.pugx.org/PandCar/Atomic/v/stable.svg)](https://packagist.org/packages/pandcar/atomic) [![Total Downloads](https://poser.pugx.org/PandCar/Atomic/downloads)](https://packagist.org/packages/pandcar/atomic) ![compatible](https://img.shields.io/badge/php-%3E=5.4-green.svg)

## Installation

### Using Composer

```sh
composer require pandcar/atomic
```

```php
require __DIR__.'/../vendor/autoload.php';

$atom = new Atomic([
  'path_tmp' => __DIR__ .'/tmp'
]);
```

If you want to test new and possibly unstable code that is in the master branch, and which hasn't yet been released, then you can use master instead (at your own risk):

```sh
composer require pandcar/atomic:dev-master
```

## Examples

All examples can be found [here](https://github.com/PandCar/Atomic/tree/master/examples).
