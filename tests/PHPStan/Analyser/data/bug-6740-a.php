<?php

namespace Bug6740;

trait BlockTemplate
{
	public function __construct()
	{
		parent::__construct();

		$this->StdClassSetup(get_class());
	}
}

class A
{
	/** @var string[] */

	private $classList = [];

	/**
	 * @returns $this
	 */

	public function __construct()
	{
	}

	/**
	 * Apply all the standard configuration needs for a sub-class
	 *
	 * @param string $baseClass
	 */

	public function StdClassSetup($baseClass): void
	{
		$this->classList[] = $baseClass;
	}

	/**
	 * @return string[]
	 */

	public function GetClassList()
	{
		return $this->classList;
	}
}

class Box extends A
{
	use BlockTemplate;
}
