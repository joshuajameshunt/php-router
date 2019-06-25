<?php

class REST {
	
	private $method;
	private $request;
	private $protocol;
	private $payload;
	private $allowed_methods = array(
		'GET',
		'POST',
		'PUT',
		'DELETE'
	);
	function __construct($api_path=null) {
		$this->protocol = $_SERVER['SERVER_PROTOCOL'];
		$this->method = $_SERVER['REQUEST_METHOD'];
		
		if ($this->method==='GET') {
			$this->payload = (array)$_GET;
		} else {
			$this->payload = (array)json_decode(file_get_contents('php://input'),true);
		}
		
		$request = explode('?',$_SERVER['REQUEST_URI'])[0];
		if (isset($api_path)) {
			if (strpos($request,$api_path)===0) {
				$request = substr($request,strlen($api_path));
			}
		}
		if (preg_match('/^\/[A-Za-z0-9\-_\/]*\/?$/',$request) && in_array($this->method,$this->allowed_methods)) {
			$request = trim($request,'/');
			$request = explode('/',$request);
		} else {
			$this->error(405);
		}
		$this->request = $request;
	}
	
	public function route() {
		if (!isset($this->routes[$this->method])) {
			$this->error(405);
		}
		$args = array();
		$request = $this->request;
		$current = &$this->routes[$this->method];
		foreach ($request as $block) {
			if (isset($current[$block])) {
				$current = &$current[$block];
			} else if (isset($current[':']) && isset($current[':']['?'])) {
				$args[$current[':']['?']] = $block;
				$current = &$current[':'];
			} else {
				$this->error(404);
			}
		}
		if (isset($current['*'])) { //} && function_exists($current['*'])) {
			if (count($args)===0) {
				$args = null;
			}
			$req = (object)array(
				'body' => $this->payload,
				'params' => $args,
				'method' => $this->method
			);
			// $res = (object)array();
			$res = $this; // not ideal but works
			foreach ($current['*'] as $function) {
				if (!function_exists($function)) {
					$this->error(500);
				}
				call_user_func($function,$req,$res);
			}
			// call_user_func($current['*'],$req,$res);
		} else {
			$this->error(405);
		}
	}
	
	private $routes = array();
	private function assign($args) {
		if (count($args)<3) { // METHOD, PATH, FUNCTION
			return false;
		}
		// print_r($args);
		$method = $args[0];
		$path = $args[1];
		$functions = array_splice($args,2);
		if ($method!==$this->method) { // don't waste your time - remove for async
			return false;
		}
		if (!in_array($method,$this->allowed_methods)) { // same logic ^^
			echo 'Router problem: Method not allowed. ';
			return false;
		}
		if (!preg_match_all('/\/(:?[A-Za-z0-9\-_]*)/',$path,$path_array)) {
			echo 'Router problem: Invalid URL. ';
			return false;
		}
		foreach ($functions as $function) {
			if (!function_exists($function)) {
				echo 'Router problem: Nonexistent function. ';
				return false;
			}
		}
		if (!isset($this->routes[$method])) {
			$this->routes[$method] = array();
		}
		$request = $this->request;
		$current = &$this->routes[$method];
		$depth = 0;
		foreach ($path_array[1] as $block) {
			
			if (strpos($block,':')===0) {
				// if (isset($current[':'])) { return false; }
				$current[':'] = array(
					'?' => substr($block,1)
				);
				$current = &$current[':'];
				// echo ' is variable. ';
				// print_r($current);
			} else {
				if ($request[$depth]!==$block) {
					return false;
				}
				if (!isset($current[$block])) {
					$current[$block] = array();
				}
				$current = &$current[$block];
				// echo ' is static. ';
				// print_r($current);
			}
			$depth++;
			
		}
		$current['*'] = $functions;
		// print_r($this->endpoints);
	}
	
	public function get() {
		$args = func_get_args();
		array_unshift($args,'GET');
		$this->assign($args);
	}
	public function post() {
		$args = func_get_args();
		array_unshift($args,'POST');
		$this->assign($args);
	}
	public function put() {
		$args = func_get_args();
		array_unshift($args,'PUT');
		$this->assign($args);
	}
	public function delete() {
		$args = func_get_args();
		array_unshift($args,'DELETE');
		$this->assign($args);
	}
	
	public function send($data,$code=200) {
		$headers = array(
			200 => 'OK',
			201 => 'Created',
			304 => 'Not Modified',
			400 => 'Bad Request',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			409 => 'Conflict',
			500 => 'Internal Server Error'
		);
		header($this->protocol.' '.$code.' '.$headers[$code]);
		header('Content-type: application/json');
		if ($code<400 && isset($data)) {
			echo json_encode($data);
		} else if (isset($data)) {
			echo json_encode(array('error'=>$data));
		} else {
			echo json_encode(array('error'=>$headers[$code]));
		}
		die();
	}
	
	public function error($code,$message=null) {
		$this->send($message,$code);
	}
		
}
