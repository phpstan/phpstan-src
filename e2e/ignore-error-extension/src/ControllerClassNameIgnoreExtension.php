<?php

declare(strict_types=1);

namespace App;

use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;

// This extension will ignore "class.name" errors for classes with names ending with "Controller".
// These errors are reported by the ClassRule which triggers on CollectedDataNode coming from ClassCollector.
final class ControllerClassNameIgnoreExtension implements IgnoreErrorExtension
{
	public function shouldIgnore(Error $error, Node $node, Scope $scope) : bool
	{
		if ($error->getIdentifier() !== 'class.name') {
			return false;
		}

		// @phpstan-ignore phpstanApi.instanceofAssumption
		if (!$node instanceof CollectedDataNode) {
			return false;
		}

		if (!str_ends_with($error->getFile(), 'Controller.php')) {
			return false;
		}

		return true;
	}
}
