<?php declare(strict_types = 1); // lint >= 7.4

namespace Bug7292;

class MyMetadata {
	/** @var class-string */
	public string $fqcn;
}

class MyClass {
	/** @var array<class-string, array<string, mixed>> */
	private $myMap = [];

	public function doSomething(MyMetadata $class): void
	{
		unset($this->myMap[$class->fqcn]['foo']);

		if (isset($this->myMap[$class->fqcn]) && ! $this->myMap[$class->fqcn]) {
			unset($this->myMap[$class->fqcn]);
		}
	}
}
