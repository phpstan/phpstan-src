<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug7200;

class HelloWorld
{
	/**
	* @param class-string<Model&One&Two&Three>|null $class
	*/
	public function __construct(public ?Model $model = null, public ?string $class = null)
	{
		if ($model instanceof One && $model instanceof Two && $model instanceof Three) {
			$this->class ??= $model::class;
		}
	}
}

class Model {}
interface One {}
interface Two {}
interface Three {}
