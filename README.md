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
// В конструкторе
$atom = new Atomic([
	'path_tmp' => __DIR__ .'/tmp'
]);

// Одиночная настройка
$atom->set('path_tmp', __DIR__ .'/tmp');

// Множественная
$atom->set([
	// Папка для временных файлов (cookie, phantomjs-tmp)
	'path_tmp' => __DIR__ .'/tmp',
	// Имя куки
	'name_cookie' => 'atomic',
	// Кука по умолчанию
	'path_cookie' => __DIR__ .'/tmp/atomic.cookie',
	// Ограничение времени на подключение (сек.)
	'connect_timeout' => 20,
	// Ограничение времени на выполнение запроса (сек.)
	'timeout' => 60,
	// Прокси сервер по умолчанию
	'proxy' => 'http://login:password@host:port',
	// Лямбда до выполнения $this->request(); влияющая на запрос
	'callback_request_start' => function($query){},
	// Лямбда после каждого выполнения $this->request();
	'callback_request_end' => function($query, $response){},
	// Путь до PhantomJS
	'phantomjs_path' => __DIR__ .'/bin/phantomjs.exe',
	// Ключь сервиса ruCaptcha
	'rucaptcha_key' => '6df61bfae47c6a9214729143c4fc9a82',
	// HTTP заголовки/планы по умолчанию
	'headers' => [
		'default' => [
			'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
			'Upgrade-Insecure-Requests: 1',
			'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36',
		],
		'ajax' => [
			'Accept: */*',
			'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
			'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36',
			'X-Requested-With: XMLHttpRequest',
		],
	],
]);
```

## Описание опций $this->request();

```php
// Простой get запрос
$result = $atom->request('http://site.ru/');

// Мульти запрос
$array = $atom->request_multi([
	[
		'url' => 'http://site.ru/?foo=bar'
	],
	[
		'url' => 'http://sitetwo.ru/'
	],
]);

// Все опции
$result = $atom->request([
	// url запроса
	'url' => 'http://site.ru/',
	// Конструктор query
	'get' => [
		'foo' => 'bar',
	],
	// Ограничение времени на подключение (сек.)
	'connect_timeout' => 5,
	// Ограничение времени на выполнение запроса (сек.)
	'timeout' => 20,
	// Простой Post (приоритет)
	'post' => 'foo=bar&ddd=ccc',
	// Конструктор Post
	'post/build' => [
		'username' => $login,
		'password' => $password,
		'submit' => 'Войти',
	],
	// Загрузка контента в файл
	'file_handle' => $fopen,
	// План заголовков
	'headers_plan' => 'ajax',
	// Заголовки (приоритет)
	'headers' => [
		'Referer: http://site.ru/',
	],
	// Сливает заголовки с заголовками по умолчанию
	'headers/merge' => [
		'Referer: http://site.ru/',
	],
	// Прокси
	'proxy' => 'http://login:password@host:port',
	// Куки (приоритет)
	'cookie' => 'foo=bar&ddd=ccc',
	// Конструктор cookie
	'cookie/build' => [
		'foo' => 'bar',
	],
	// Путь к файл cookie
	'cookie_path' => __DIR__ .'/tmp/atomic.cookie',
	// Лямбда функции cURL (header, progress, read, write)
	'callbacks' => [
		'write' => function($curl, $content){
			return strlen($content);
		}
	],
	// Прямая установка параметров cURL (приоритет)
	'curl_setopt' => [
		CURLOPT_HEADER => true,
	],
	// Исключения Заголовков ответа из результата cURL
	'no_headers' => true,
	// Исключения Тела ответа из результата cURL
	'no_body' => true,
	// Сменить кодировку контента из windows-1251 на utf8
	'charset' => 'windows-1251',
	// Следовать по заголовкам Location
	'follow_location' => true,
	// Форма данных (headers, body, array, json, xml)
	'form' => 'json',
	// Включает отладку
	'debug' => true,
	// Не вызывать callback_request_*
	'no_callback' => true,
]);
```

## Работа с cookie файлом по умолчанию

```php
// Получить массив кук
$array = $atom->getCookie();

// Установка куки
$atom->setCookie($domen, $key, $value, $time);

// Удаление одной или всех кук (если без параметров)
$atom->removeCookie($key, $domen);
```

## Инструменты

#### Обёртка над preg_match()

```php
$string = $atom->regexp('>([^<]+)</span>', $html);

// Получает второй элемент
$string = $atom->regexp('>([^<]+)</span>, <b>([0-9]+?)', $html, 2);

$array = $atom->regexp('>([^<]+)</span>, <b>([0-9]+?)', $html, true);
```

#### Обёртка над preg_match_all()

```php
$array = $atom->regexpAll('>([^<]+)</span>, <b>([0-9]+?)', $html);

$array = $atom->regexpAll('>([^<]+)</span>, <b>([0-9]+?)', $html, 2);

$array = $atom->regexpAll('>([^<]+)</span>, <b>([0-9]+?)', $html, [2, 1]);
```

#### Поиск вхождения в строке

```php
$bool = $atom->existStr($html, '<span>');

var_dump($bool); // true
```

#### Фильтр

```php
$string = $atom->strFilter($string, [
	':trim', ':tags', "\n", "\t"
]);
```

#### Преобразование времени в timestamp

```php
$time_str = '04 декабря 2016, в 10:05';

$timestamp = $atom->strTimeToUnix(
	$time_str, [
		'(.+) час\. назад'	=> '-$1 hour',
		'(.+) г\. в (.+)'	=> '$1, $2',
		'(.+), в (.+)'		=> '$1, $2',
		'вчера в (.+)'		=> '-1 day, $1',
		'сегодня в (.+)'	=> '$1',
		'(.+) г\.'			=> '$1',
	], [
		'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 
		'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'
	]
);
```

## Использование сервиса ruCaptcha

```php
$atom->set('rucaptcha_key', '6df61bfae47c6a9214729143c4fc9a82');

try {
	$code = $atom->ruCaptcha($img_path, [
		'phrase' => 0,
		'regsense' => 0,
	]);
}
catch (Exception $e) {
    echo 'Выброшено исключение: '. $e->getMessage();
}
```

## Использование PhantomJS

```php
$atom->set('phantomjs_path', __DIR__ .'/bin/phantomjs.exe');

$script = '
	console.log("Loading a web page");
	var page = require("webpage").create();
	var url = "http://phantomjs.org/";
	page.open(url, function (status) {
	  //Page is loaded!
	  phantom.exit();
	});
';

$result = $atom->PhantomJS($script, [
	'cookies-file' => __DIR__ .'/tmp/phantomjs.cookie'
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
