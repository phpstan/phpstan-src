<?php

namespace Bug4865;

use Closure;

class HelloWorld
{
	/**
	 * @param object $instance
	 * @param array<string,mixed> $fieldToValue
	 *
	 * @return mixed
	 */
	public function create($instance, array $fieldToValue) {
		(Closure::bind($this->hydrate(), $instance, get_class($instance)))($fieldToValue);

		return $instance;
	}

	public function hydrate(): Closure {
		return function ($map) {
			foreach ($map as $field => $value) {
				$this->$field = $value;
			}
		};
	}
}
