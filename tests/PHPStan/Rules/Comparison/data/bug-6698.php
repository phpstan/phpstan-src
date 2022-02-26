<?php declare(strict_types = 1);

namespace Comparison\Bug6698;

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
				yield $class;
			}
		}
	}
}
