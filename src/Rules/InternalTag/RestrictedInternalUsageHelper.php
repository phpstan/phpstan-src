<?php declare(strict_types = 1);

namespace PHPStan\Rules\InternalTag;

use PHPStan\Analyser\Scope;
use function array_slice;
use function explode;
use function str_starts_with;

final class RestrictedInternalUsageHelper
{

	public function shouldBeReported(Scope $scope, string $name): bool
	{
		$currentNamespace = $scope->getNamespace();
		if ($currentNamespace === null) {
			$classReflection = $scope->getClassReflection();
			if ($classReflection === null) {
				return true;
			}

			return $classReflection->getName() !== $name;
		}

		$currentNamespace = explode('\\', $currentNamespace)[0];
		$namespace = array_slice(explode('\\', $name), 0, -1)[0] ?? null;

		return !str_starts_with($namespace . '\\', $currentNamespace . '\\');
	}

}
