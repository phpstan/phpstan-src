<?php

declare(strict_types=1);

namespace App;

use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;

// This extension will ignore "missingType.iterableValue" errors for public Action methods inside Controller classes.
final class ControllerActionReturnTypeIgnoreExtension implements IgnoreErrorExtension
{
	public function shouldIgnore(Error $error, Node $node, Scope $scope) : bool
	{
		if ($error->getIdentifier() !== 'missingType.iterableValue') {
			return false;
		}

		// @phpstan-ignore phpstanApi.instanceofAssumption
		if (! $node instanceof InClassMethodNode) {
			return false;
		}

		if (! str_ends_with($node->getClassReflection()->getName(), 'Controller')) {
			return false;
		}

		if (! str_ends_with($node->getMethodReflection()->getName(), 'Action')) {
			return false;
		}

		if (! $node->getMethodReflection()->isPublic()) {
			return false;
		}

		return true;
	}
}
