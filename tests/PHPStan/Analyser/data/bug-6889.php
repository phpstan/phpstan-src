<?php

namespace Bug6889;

use ReflectionMethod;
use function PHPStan\Testing\assertType;

class MethodWrapper
{
	private ReflectionMethod $reflection;

	public function __construct(ReflectionMethod $reflection) {
		$this->reflection = $reflection;
	}

	/**
	 * @return class-string
	 */
	public function getClassName(): string {
		assertType('class-string', $this->reflection->class);
		return $this->reflection->class;
	}
}
