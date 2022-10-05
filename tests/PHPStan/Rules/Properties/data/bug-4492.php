<?php

namespace Bug4492;

class A
{
	/** @var string */
	protected $prop;

	public function setProp(string $prop): void
	{
		$this->prop = $prop;
	}

	public function getProp(): string
	{
		return $this->prop;
	}
}

trait PropMangler
{
	/** @var string */
	protected $prop;

	public function mangleProp(): void
	{
		$this->prop = 'Improved ' . $this->prop;
	}
}

class B extends A
{
	use PropMangler;
}

class C extends A
{
	/** @var B b */
	public $b;

	public function __construct()
	{
		$this->b = new B;
	}

	public function accessesBProp(): void
	{
		$this->b->prop = "This works";
	}
}
