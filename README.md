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

## Настройки

```php
// Одиночная настройка
$atom->set('path_tmp', __DIR__ .'/tmp');

// Множественная
$atom->set([
	// Папка для временных файлов (cookie, phantomjs-tmp)
	$path_tmp = __DIR__ .'/tmp',
	// Имя куки
	$name_cookie = 'atomic',
	// Кука по умолчанию
	$path_cookie = __DIR__ .'/tmp/atomic.cookie',
	// Прокси сервер по умолчанию
	$proxy = 'http://login:password@host:port',
	// Лямбда после каждого выполнения $this->request();
	$callback_request = function($query, $response){},
	// Путь до PhantomJS
	$phantomjs_path = __DIR__ .'/bin/phantomjs.exe',
	// Ключь сервиса ruCaptcha
	$rucaptcha_key = '6df61bfae47c6a9214729143c4fc9a82',
	// HTTP заголовки по умолчанию
	$headers = [
		'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
		'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36',
	],
]);
```

## Описание опций $this->request();

```php
// Простой get запрос
$result = $atom->request('http://site.ru/');

$result = $atom->request([
	'url' => 'http://site.ru/',
	'get' => [
		'foo' => 'bar',
	],
	'connect_timeout' => 5,
	'timeout' => 20,
	'post' => 'foo=bar&ddd=ccc',
	'post/build' => [
		'username' => $login,
		'password' => $password,
		'submit' => 'Войти',
	],
	'file_handle' => $fopen,
	'headers' => [
		'Referer: http://site.ru/',
	],
	'headers/merge' => [
		'Referer: http://site.ru/',
	],
	'proxy' => 'http://login:password@host:port',
	'cookie' => 'foo=bar&ddd=ccc',
	'cookie/build' => [
		'foo' => 'bar',
	],
	'cookie_path' => __DIR__ .'/tmp/atomic.cookie',
	'charset' => 'windows-1251',
	'follow_location' => true,
	'form' => 'json',
	'debug' => true,
	'no_callback' => true,
]);
```

## Простая авторизация

```php
$result = $atom->request([
	'url' => 'http://site.ru/login.php',
	'post/build' => [
		'username' => $login,
		'password' => $password,
		'submit' => 'Войти',
	],
	'headers/merge' =>[
		'Referer: http://site.ru/',
	],
]);

// Проверка авторизации
var_dump( $atom->existStr($result, 'outlogin.php"') );
```
