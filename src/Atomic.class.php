<?php

/**
 * @author Oleg Isaev
 * @contacts vk.com/id50416641, t.me/pandcar, github.com/pandcar
 * @version 1.3.1
 */

class Atomic
{
	protected	$path_tmp = __DIR__,
				$path_cookie = null,
				$connect_timeout = 20,
				$timeout = 60,
				$proxy = null,
				$callback_request_start = null,
				$callback_request_end = null,
				$headers = [
					'default' => [
						'Connection: keep-alive',
						'Cache-Control: max-age=0',
						'Upgrade-Insecure-Requests: 1',
						'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36',
						'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
						'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
					],
					'ajax' => [
						'Connection: keep-alive',
						'Cache-Control: max-age=0',
						'Accept: */*',
						'X-Requested-With: XMLHttpRequest',
						'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36',
						'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
						'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
					],
				],
				$phantomjs_path = null,
				$rucaptcha_key = null,
				$rucaptcha_errors_in = [
					'ERROR_WRONG_USER_KEY',
					'ERROR_KEY_DOES_NOT_EXIST',
					'ERROR_ZERO_BALANCE',
					'ERROR_PAGEURL',
					'IP_BANNED',
					'MAX_USER_TURN',
				],
				$rucaptcha_errors_res = [
					'ERROR_WRONG_USER_KEY',
					'ERROR_KEY_DOES_NOT_EXIST',
				];
	
	public function __construct($setting = [])
	{
		$this->set($setting);
		
		if (empty($this->path_cookie))
		{
			$this->path_cookie = $this->path_tmp.'/atomic.cookie';
		}
	}
	
	// Настройки
	public function set($mixed, $value = null)
	{
		if (! empty($mixed))
		{
			if (! is_array($mixed))
			{
				$mixed = [$mixed => $value];
			}
			
			foreach ($mixed as $key => $value)
			{
				if (method_exists($this, 'set_'.$key))
				{
					$this->{'set_'.$key}($value);
				}
				elseif (property_exists($this, $key))
				{
					$this->$key = $value;
				}
			}
		}
	}
	
	// Настройка имени куки в tmp деректории
	protected function set_name_cookie($value)
	{
		$this->path_cookie = $this->path_tmp.'/'.$value.'.cookie';
	}
	
	// Работа с сервисом ruCaptcha
	public function ruCaptcha($img_path, $params = [])
	{
		if (empty($this->rucaptcha_key))
		{
			throw new Exception('no key');
		}
		
		if (! file_exists($img_path))
		{
			throw new Exception('file does not exist');
		}
		
		unset($params['key'], $params['method'], $params['body'], $params['json']);
		
		$in = $this->request([
			'url' => 'http://rucaptcha.com/in.php',
			'post/build' => array_merge([
						'key'		=> $this->rucaptcha_key,
						'method'	=> 'base64',
						'body'		=> base64_encode(file_get_contents($img_path)),
						'json'		=> 1,
					], 
					$params
				),
			'form' => 'json',
		]);
		
		if ($in['status'] == 0)
		{
			if ($in['request'] == 'ERROR_NO_SLOT_AVAILABLE')
			{
				sleep(4);
				
				return $this->ruCaptcha($img);
			}
			elseif (in_array($in['request'], $this->rucaptcha_errors_in))
			{
				throw new Exception('type "in": '.$in['request']);
			}
		}
		elseif ($in['status'] == 1)
		{
			while (true)
			{
				sleep(3);
				
				$res = $this->request([
					'url' => 'http://rucaptcha.com/res.php',
					'get' => [
						'key'		=> $this->rucaptcha_key,
						'action'	=> 'get',
						'id'		=> $in['request'],
						'json'		=> 1,
					],
					'form' => 'json',
				]);
				
				if ($res['status'] == 0)
				{
					if ($res['request'] == 'CAPCHA_NOT_READY')
					{
						continue;
					}
					elseif (in_array($res['request'], $this->rucaptcha_errors_res))
					{
						throw new Exception('type "res": '.$res['request']);
					}
				}
				elseif ($res['status'] == 1)
				{
					return $res['request'];
				}
				
				break;
			}
		}
		
		return false;
	}
	
	// Работа с PhantomJS (при его наличии)
	public function PhantomJS($script, $params = [])
	{
		if (! empty($this->phantomjs_path) && file_exists($this->phantomjs_path))
		{
			$funcs = '
				function report(data) {
					console.log("<pjs-json>" + JSON.stringify(data) + "</pjs-json>");
					phantom.exit();
				}
			';
			
			$params_str = '';
			
			foreach ($params as $key => $value)
			{
				$params_str .= ' --'.$key.'='.$value;
			}
			
			$path_script = $this->path_tmp.'/pjs-'.md5(mt_rand().mt_rand()).'.js';
			
			file_put_contents($path_script, $funcs.$script);
			
			$result = shell_exec($this->phantomjs_path . $params_str.' '.$path_script);
			
			unlink($path_script);
			
			if ($this->existStr($result, '</pjs-json>'))
			{
				$json = $this->parse('<pjs-json>(.+?)</pjs-json>', $result);
				
				return json_decode($json, true);
			}
			
			return $result;
		}
		
		return false;
	}
	
	public function strFilter($string, $items = [])
	{
		foreach ($items as $value)
		{
			if ($value == ':trim') {
				$string = trim($string);
			} elseif ($value == ':tags') {
				$string = strip_tags($string);
			} else {
				$string = str_replace($value, '', $string);
			}
		}
		
		return $string;
	}
	
	// Поиск вхождения в строке
	public function existStr($string, $search)
	{
		return (substr_count($string, $search) > 0);
	}
	
	// Обёртка над preg_match
	public function regexp($pattern, $string, $get = null)
	{
		if (preg_match('~'.$pattern.'~isu', $string, $array))
		{
			if ($get === null) {
				return $array[1];
			} elseif ($get === true) {
				return $array;
			} else {
				return (isset($array[$get]) ? $array[$get] : null);
			}
		}
		
		return false;
	}
	
	// Обёртка над preg_match_all
	public function regexpAll($pattern, $string, $get = null)
	{
		if (preg_match_all('~'.$pattern.'~isu', $string, $array))
		{
			if ($get === null)
			{
				$result = [];
				
				foreach ($array as $key => $item)
				{
					foreach ($item as $key2 => $value)
					{
						$result[$key2][$key] = $value;
					}
				}
				
				return $result;
			}
			elseif (is_array($get))
			{
				$result = [];
				
				foreach ($get as $key)
				{
					$result[] = (isset($array[$key]) ? $array[$key] : false);
				}
				
				return $result;
			}
			elseif (isset($array[$get]))
			{
				return $array[$get];
			}
			
			return null;
		}
		
		return false;
	}
	
	public function strTimeToUnix($string, $patterns = [], $months = false)
	{
		$string = mb_strtolower( trim($string), 'utf8');
		
		if (! empty($months))
		{
			$at = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
			
			$string = str_replace($months, $at, $string);
		}
		
		$patterns_new = [];
		
		foreach ($patterns as $key => $value)
		{
			$patterns_new['~'.$key.'~isu'] = $value;
		}
		
		$string = preg_replace( array_keys($patterns_new), array_values($patterns_new), $string);
		
		$timeunix = strtotime($string);
		
		return $timeunix > 0 ? $timeunix : 0;
	}
	
	public function XMLDecode($string)
	{
		return $this->xml2array( @simplexml_load_string($string, 'SimpleXMLElement', LIBXML_NOCDATA) );
	}
	
	// Выводит массив кук
	public function getCookie($key = null)
	{
		$array = [];
		
		$this->cookieFileWp($this->path_cookie);
		
		$string = file_get_contents($this->path_cookie);
		
		preg_match_all("~\t([0-9]+)\t(.+?)\t([^\n]+)~isu", $string, $preg);
		
		foreach ($preg[2] as $i => $k)
		{
			$array[ urldecode($k) ] = trim( urldecode($preg[3][$i]) );
		}
		
		if (! empty($key))
		{
			return (isset($array[$key]) ? $array[$key] : false);
		}
		
		return $array;
	}
	
	// Добавляет или изменяет куку
	public function setCookie($domen, $key, $value, $time = 0)
	{
		$this->cookieFileWp($this->path_cookie);
		
		$data	= file($this->path_cookie);
		$count	= count($data);
		$insert	= true;
		
		for ($i = 0; $i < $count; $i++)
		{
			if (substr_count($data[$i], $domen."\t") > 0 && preg_match("~\t".$key."\t([^\t]+)$~isu", $data[$i]))
			{
				$data[$i] = preg_replace("~([0-9]+)\t".$key."\t(.+?)$~i", $time."\t".$key."\t".$value, $data[$i]);
				
				$insert = false;
				break;
			}
		}
		
		if ($insert)
		{
			$data[] = ".".$domen."\tTRUE\t/\tFALSE\t".$time."\t".$key."\t".$value."\n";
		}
		
		file_put_contents($this->path_cookie, $data, LOCK_EX);
	}
	
	// Удаляет одну или все куки
	public function removeCookie($key = null, $domen = null)
	{
		$this->cookieFileWp($this->path_cookie);
		
		$data = file($this->path_cookie);
		
		if (! empty($key))
		{
			for ($i = 0; $i < count($data); $i++)
			{
				if (preg_match("~\t".$key."\t([^\t]+)$~isu", $data[$i]) && (empty($domen) || substr_count($data[$i], $domen."\t") > 0))
				{
					unset($data[$i]);
				}
			}
		}
		else
		{
			$data = '';
		}
		
		file_put_contents($this->path_cookie, $data, LOCK_EX);
	}
	
	// Http запрос
	public function request($data = [])
	{
		list($ch, $data) = $this->curlPreparation($data);
		
		$response = curl_exec($ch);
		
		$result = $this->curlResult($ch, $data, $response);
		
		curl_close($ch);
		
		return $result;
	}
	
	// Multi http запрос
	public function request_multi($array = [])
	{
		$mh = curl_multi_init();
		$curl_array = [];
		$count = count($array);
		
		for ($i = 0; $i < $count; $i++)
		{
			list($curl_array[$i], $array[$i]) = $this->curlPreparation($array[$i]);
			
			curl_multi_add_handle($mh, $curl_array[$i]);
		}
		
		$running = NULL;
		
		do {
			usleep(1000);
			curl_multi_exec($mh, $running);
		}
		while ($running > 0);
		
		$result = [];
		
		for ($i = 0; $i < $count; $i++)
		{
			$response = curl_multi_getcontent($curl_array[$i]);
			
			$result[$i] = $this->curlResult($curl_array[$i], $array[$i], $response);
			
			curl_multi_remove_handle($mh, $curl_array[$i]);
		}
		
		curl_multi_close($mh);
		
		return $result;
	}
	
	// Подготовка http запроса
	protected function curlPreparation($data = [])
	{
		if (is_string($data))
		{
			return $this->curlPreparation(['url' => $data]);
		}
		
		if (!empty($this->callback_request_start) && !isset($data['no_callback']))
		{
			$callback = $this->callback_request_start;
			
			if (is_callable($callback))
			{
				$array = $callback($data);
				
				if (is_array($array))
				{
					$data = $array;
				}
			}
		}
		
		$ch = curl_init();
		
		// Конструктор query data
		if (! empty($data['get']))
		{
			$data['url'] .= ($this->existStr($data['url'], '?') ? '&' : '?').http_build_query($data['get'], '', '&');
		}
		
		// Основные параметры
		curl_setopt($ch, CURLOPT_URL, $data['url']); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (! empty($data['connect_timeout']) ? $data['connect_timeout'] : $this->connect_timeout));
		curl_setopt($ch, CURLOPT_TIMEOUT, (! empty($data['timeout']) ? $data['timeout'] : $this->timeout));
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		// Post запрос
		if (isset($data['post']) || isset($data['post/build']))
		{
			curl_setopt($ch, CURLOPT_POST, true);
			
			if (isset($data['post'])) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data['post']);
			} else {
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data['post/build'], '', '&'));
			}
		}
		
		// Дескриптор загружаемого файла
		if (isset($data['file_handle']))
		{
			curl_setopt($ch, CURLOPT_FILE, $data['file_handle']);
		}
		
		$headers_key = array_keys($this->headers)[0];
		
		if (is_array($this->headers[$headers_key]))
		{
			$headers = $this->headers[$headers_key];
		}
		else
		{
			$headers = $this->headers;
		}
		
		// План заголовков по умолчанию
		if (isset($data['headers_plan']))
		{
			if ( isset($this->headers[ $data['headers_plan'] ]) )
			{
				$headers = $this->headers[ $data['headers_plan'] ];
			}
		}
		
		// Заголовки
		if (isset($data['headers']))
		{
			if ($data['headers'] !== false)
			{
				curl_setopt($ch, CURLOPT_HTTPHEADER, $data['headers']);
			}
		}
		elseif (! empty($data['headers/merge']))
		{
			if (! empty($headers))
			{
				$data['headers'] = $headers;
				
				$new = $this->expHeaders($headers);
				$new2 = $this->expHeaders($data['headers/merge']);
				$headers_merge = [];
				
				$new = array_merge($new, $new2);
				
				foreach ($new as $key => $value)
				{
					$headers_merge[] = $key.': '.$value;
				}
			}
			
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_merge);
		}
		elseif (! empty($headers))
		{
			$data['headers'] = $headers;
			
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		
		// Прокси
		if (isset($data['proxy']))
		{
			if ($data['proxy'] !== false)
			{
				curl_setopt($ch, CURLOPT_PROXY, $data['proxy']);
			}
		}
		elseif (! empty($this->proxy))
		{
			$data['proxy'] = $this->proxy;
			
			curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
		}
		
		// Куки
		if (isset($data['cookie']) || isset($data['cookie/build']))
		{
			if (isset($data['cookie']))
			{
				if ($data['cookie'] !== false)
				{
					curl_setopt($ch, CURLOPT_COOKIE, $data['cookie']);
				}
			}
			else
			{
				curl_setopt($ch, CURLOPT_COOKIE, str_replace('&', '; ', http_build_query($data['cookie/build'], '', '&')));
			}
		}
		elseif (! empty($this->path_cookie)|| !empty($data['cookie_path']))
		{
			$cookie_path = $this->path_cookie;
			
			if (isset($data['cookie_path']))
			{
				$cookie_path = $data['cookie_path'];
			}
			
			$data['cookie_path'] = $cookie_path;
			
			$this->cookieFileWp($cookie_path);
			
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_path);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_path);
		}
		
		return [$ch, $data];
	}
	
	// Обработка результата запроса
	protected function curlResult($ch, $data = [], $response)
	{
		// Кодировка
		if (! empty($data['charset']))
		{
			$response = iconv($data['charset'], 'UTF-8', $response);
		}
		
		// Дебаг
		if (isset($data['debug']))
		{
			echo "\n===== Atomic debug =====\n";
			print_r([
				'request'		=> $data,
				'curl_error'	=> curl_error($ch),
				'curl_info'		=> curl_getinfo($ch),
				'response'		=> $response,
			]);
			echo "===== Debug end ========\n\n";
		}
		
		if (!empty($this->callback_request_end) && !isset($data['no_callback']))
		{
			$callback = $this->callback_request_end;
			
			if (is_callable($callback))
			{
				$callback($data, $response);
			}
		}
		
		// Следование по заголовку Location
		if (isset($data['follow_location']))
		{
			if (list($headers, $body) = $this->expResponse($response))
			{
				if ($this->existStr($headers, 'Location:'))
				{
					$cinfo = curl_getinfo($ch);
					
					unset($data['url'], $data['get'], $data['post'], $data['post/build']);
					
					$data['url'] = $cinfo['redirect_url'];
					
					return $this->request($data);
				}
			}
		}
		
		// Вид результата
		if (! empty($data['form']))
		{
			list($headers, $body) = $this->expResponse($response);
			
			switch ($data['form'])
			{
				case 'headers': $response = $headers; break;
				
				case 'body': $response = $body; break;
				
				case 'array':
					$response = [
						'headers'	=> $headers, 
						'body'		=> $body,
					];
				break;
				
				case 'json': $response = json_decode($body, true); break;
				
				case 'xml': $response = $this->XMLDecode($body); break;
			}
		}
		
		return $response;
	}
	
	// Разбитие заголовков
	protected function expHeaders($headers = [])
	{
		$array = [];
		
		foreach ($headers as $row)
		{
			@list($key, $value) = explode(':', $row, 2);
			
			$array[ trim( mb_strtolower($key) ) ] = trim($value);
		}
		
		return $array;
	}
	
	// Разбитие результата http запроса
	protected function expResponse($response = '')
	{
		$exp = explode("\r\n\r\n", $response, 2);
		
		if (! preg_match('~^HTTP~i', $exp[1]))
		{
			return $exp;
		}
		
		$exp = explode("\r\n\r\n", $response, 3);
		
		return [$exp[0]."\r\n\r\n".$exp[1], $exp[2]];
	}
	
	protected function cookieFileWp($path)
	{
		if (! file_exists($path))
		{
			file_put_contents($path, '');
		}
	}
	
	protected function xml2array($xmlObject, $out = [])
	{
		foreach ((array) $xmlObject as $index => $node)
		{
			$out[$index] = (is_object($node) ? $this->xml2array($node) : $node);
		}
		
		return $out;
	}
}
