<?php

namespace PR4374;

class Foo {}

final class AnnotationsMethodsClassReflectionExtension
{

	/** @var Foo[][] */
	private array $methods = [];

	public function hasMethod(string $cacheKey, string $methodName): bool
	{
		if (!isset($this->methods[$cacheKey][$methodName])) {
			$method = $this->findClassReflectionWithMethod();
			if ($method === null) {
				return false;
			}
			$this->methods[$cacheKey][$methodName] = $method;
		}

		return isset($this->methods[$cacheKey][$methodName]);
	}

	private function findClassReflectionWithMethod(
	): ?Foo
	{
		if (rand(0,1)) {
			return new Foo();
		}
		return null;
	}
}
