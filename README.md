# ![logo](/examples/assets/logo2.png) Atomic [![Latest Stable Version](https://poser.pugx.org/PandCar/Atomic/v/stable.svg)](https://packagist.org/packages/pandcar/atomic) [![Total Downloads](https://poser.pugx.org/PandCar/Atomic/downloads)](https://packagist.org/packages/pandcar/atomic) ![compatible](https://img.shields.io/badge/php-%3E=5.4-green.svg)

## Установка

Для установки Atomic выполните команду:

```sh
composer require pandcar/atomic
```

## Быстрый старт

```php
require __DIR__.'/vendor/autoload.php';

$atom = new Atomic([
  'path_tmp' => __DIR__ .'/tmp'
]);

$result = $atom->request('http://site.ru/');
```

Все примеры можно найти [здесь](https://github.com/PandCar/Atomic/tree/master/examples).

## Простая авторизация

```php
$result = $atom->request([
	'url' => 'http://site.ru/login.php',
	'post/build' => [
		'username' => $login,
		'password' => $password,
		'checkbox' => 1,
		'submit' => 'Войти',
	],
	'headers/merge' =>[
		'Referer: http://site.ru/',
	],
]);

// Проверка авторизации
var_dump($atom->existStr($result, 'outlogin.php"'));
```
