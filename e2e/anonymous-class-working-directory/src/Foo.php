<?php declare(strict_types = 1);

namespace AnonymousClassWorkingDirectory;

$foo = new class {

	/** @return never */
	public function fail(): void
	{
		throw new \Exception();
	}

	public function foo(): string
	{
		$this->fail();
	}

};
