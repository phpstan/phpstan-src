<?php // lint >= 8.4

declare(strict_types = 1);

namespace SideEffectsReceiver;

use ReflectionProperty;
use SplFileObject;
use SplFixedArray;
use function PHPStan\Testing\assertType;

class Bar
{

	public int $x = 1;

}

class Foo
{

	public function writesArgsOnly(ReflectionProperty $property, Bar $bar): void
	{
		if ($property->getName() === 'x' && $bar->x === 1) {
			$property->setRawValue($bar, 2);
			assertType('\'x\'', $property->getName());
			assertType('int', $bar->x);
		}
	}

	public function writesThis(SplFileObject $f): void
	{
		if ($f->getFilename() === 'a.txt') {
			$f->fgets();
			assertType('string', $f->getFilename());
		}
	}

	/** @param SplFixedArray<int> $a */
	public function writesThisSize(SplFixedArray $a): void
	{
		if ($a->getSize() === 3) {
			$a->setSize(4);
			assertType('int', $a->getSize());
		}
	}

}

class MyProperty extends ReflectionProperty
{

	public function writesArgsOnly(Bar $bar): void
	{
		if ($this->getName() === 'x') {
			parent::setRawValue($bar, 2);
			assertType('\'x\'', $this->getName());
		}
	}

}

class MyFile extends SplFileObject
{

	public function writesThis(): void
	{
		if ($this->getFilename() === 'a.txt') {
			parent::fgets();
			assertType('string', $this->getFilename());
		}
	}

}
