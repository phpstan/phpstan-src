<?php

namespace Bug9326PhpDoc;

class OnlyCalledFromConstructor
{
	/** @readonly */
	private string $foo;

	public function __construct()
	{
		$this->init();
	}

	private function init(): void
	{
		$this->foo = 'bar';
	}
}

class AlsoCalledElsewhere
{
	/** @readonly */
	private string $foo;

	public function __construct()
	{
		$this->init();
	}

	public function reset(): void
	{
		$this->init();
	}

	private function init(): void
	{
		$this->foo = 'bar';
	}
}
