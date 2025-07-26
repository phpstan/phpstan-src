<?php

namespace Bug2730;

class A{
	/** @var string */
	public $a = "hi";
	/** @var int */
	public $b = 0;

	public function dummy() : void{
		foreach((array) $this as $k => $v){
			if(is_string($v)){
				echo "string\n";
			}elseif(is_object($v)){
				echo "object\n";
			}else{
				echo gettype($v) . "\n";
			}
		}
	}
}

class B extends A{
	/** @var \stdClass */
	public $obj;

	public function __construct(){
		$this->obj = new \stdClass;
	}
}

final class C{
	/** @var string */
	public $a = "hi";
	/** @var int */
	public $b = 0;

	public function dummy() : void{
		foreach((array) $this as $k => $v){
			if(is_string($v)){
				echo "string\n";
			}elseif(is_object($v)){
				echo "object\n";
			}else{
				echo gettype($v) . "\n";
			}
		}
	}
}
