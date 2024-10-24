<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use ShipMonk\PHPStan\DeadCode\Provider\SimpleMethodEntrypointProvider;

final class ApiPhpDocEntrypointProvider extends SimpleMethodEntrypointProvider
{

	public function isEntrypointMethod(\ReflectionMethod $method): bool
	{
		$reflectionClass = $method->getDeclaringClass();
		$methodName = $method->getName();

		if ($this->isApiClass($reflectionClass)) {
			return true;
		}

		do {
			if ($this->isApiMethod($reflectionClass, $methodName)) {
				return true;
			}

			foreach ($reflectionClass->getInterfaces() as $interface) {
				if ($this->isApiClass($interface)) {
					return true;
				}

				if ($this->isApiMethod($interface, $methodName)) {
					return true;
				}
			}

			$reflectionClass = $reflectionClass->getParentClass();
		} while ($reflectionClass !== false);

		return false;
    }

	private function isApiClass(\ReflectionClass $reflection): bool
	{
		$phpDoc = $reflection->getDocComment();
		if ($phpDoc !== false && str_contains($phpDoc, '@api')) {
			return true;
		}

		return false;
	}

	private function isApiMethod(\ReflectionClass $reflectionClass, string $methodName): bool
	{
		if (!$reflectionClass->hasMethod($methodName)) {
			return false;
		}

		$phpDoc = $reflectionClass->getMethod($methodName)->getDocComment();
		if ($phpDoc !== false && str_contains($phpDoc, '@api')) {
			return true;
		}

		return false;
	}
}
