<?php

namespace Bug3566;

class HelloWorld
{

	/**
	 * @phpstan-template TMemberType
	 * @phpstan-param array<mixed, TMemberType> $array
	 * @phpstan-param \Closure(TMemberType) : void $validator
	 */
	public static function validateArrayValueType(array $array, \Closure $validator) : void{
		foreach($array as $k => $v){
			try{
				$validator($v);
			}catch(\TypeError $e){
				throw new \TypeError("Incorrect type of element at \"$k\": " . $e->getMessage(), 0, $e);
			}
		}
	}

	/**
	 * @phpstan-template TMemberType
	 * @phpstan-param TMemberType $t
	 * @phpstan-param \Closure(int) : void $validator
	 */
	public static function doFoo($t, \Closure $validator) : void{
		$validator($t);
	}

	/**
	 * @phpstan-template TMemberType
	 * @phpstan-param TMemberType $t
	 * @phpstan-param \Closure(mixed) : void $validator
	 */
	public static function doFoo2($t, \Closure $validator) : void{
		$validator($t);
	}
}
