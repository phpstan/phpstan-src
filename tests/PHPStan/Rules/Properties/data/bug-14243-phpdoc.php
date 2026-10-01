<?php

namespace Bug14243PhpDoc;

use ArrayAccess;

class PhpDocReadonly
{

	/**
	 * @var array<string, int>
	 * @readonly
	 */
	public $params;

	/**
	 * @var array<string, int>
	 * @readonly
	 */
	public static $staticParams = [];

	/**
	 * @var ArrayAccess<string, int>
	 * @readonly
	 */
	public $collection;

	/** @param ArrayAccess<string, int> $collection */
	public function __construct(ArrayAccess $collection)
	{
		$this->params = ['x' => 1];
		$this->collection = $collection;
	}

	public function assignElementsByReference(): void
	{
		$a = &$this->params['x'];
		$b = &self::$staticParams['x'];
		$c = &$this->collection['x'];
	}

}
