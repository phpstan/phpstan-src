<?php

namespace Bug4842;

class A
{

	/**
	 * @var array{21021200?:string,222?:int}
	 */
	private $mappings;

	/**
	 * @param array{21021200?:string,222?:int} $mappings
	 */
	public function __construct(array $mappings){
		$this->mappings = $mappings;
	}

	/**
	 * @param "21021200"|"asd" $code
	 */
	function foo(string $code): string
	{
		if (isset($this->mappings[$code])) {
			return (string)$this->mappings[$code];
		}

		throw new \RuntimeException();
	}
}
