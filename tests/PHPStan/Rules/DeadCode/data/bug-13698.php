<?php declare(strict_types = 1);

namespace Bug13698;

class ImpureConstructorClass
{
	public function __construct()
	{
		echo 'foo';
	}
}

class ThrowingConstructorClass
{
	public function __construct()
	{
		if (rand(0, 1) === 0) {
			throw new \RuntimeException('Error in constructor');
		}
	}
}

class NoConstructorClass {}

function test(): void
{
	new class() extends ImpureConstructorClass {
	};

	new class() extends ImpureConstructorClass {
		public function __construct()
		{
			parent::__construct();
		}
	};

	new class() extends ThrowingConstructorClass {
	};

	new class() extends ThrowingConstructorClass {
		public function __construct()
		{
			parent::__construct();
		}
	};

	new class() extends NoConstructorClass {
	};

	new class() {
	};
}
