<?php

namespace Bug3171;

class HelloWorld
{
	/**
	 * @param array{
	 *          filters?:array{
	 *              keywords?:string
	 *          }
	 *        } $request
	 */
	public function find(array $request = [], bool $doCache=true) : void {
		$searchStr = $request['filters']['keywords'] ?? '';
	}
}
