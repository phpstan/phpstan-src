<?php declare(strict_types = 1);

namespace Analyzer\Bug6698;

use function PHPStan\Testing\assertType;

interface X {
	/**
	 * @return iterable<class-string>
	 */
	public function getClasses(): iterable;
}

class Y
{
	/** @var X */
	public $x;

	/**
	 * @template T of object
	 *
	 * @param class-string<T> $type
	 * @return iterable<class-string<T>>
	 */
	public function findImplementations(string $type): iterable
	{
		 foreach ($this->x->getClasses() as $class) {
			if (is_subclass_of($class, $type)) {
				assertType('class-string<T of object (method Analyzer\Bug6698\Y::findImplementations(), argument)>', $class);
				yield $class;
			}
		}
	}
}
