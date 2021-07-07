<?php declare(strict_types = 1);

namespace Bug5258;

class HelloWorld
{
	/**
	 * @param array{some_key?:string, other_key:string} $params
	 */
	public function method1(array $params): void
	{
		if (!empty($params['some_key'])) $this->method2($params);

		if (!empty($params['other_key'])) $this->method2($params);
	}

	/**
	 * @param array{other_key:string} $params
	 **/
	public function method2(array$params): void
	{
	}
}
