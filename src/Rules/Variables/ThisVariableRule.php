<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\Variable>
 */
class ThisVariableRule implements \PHPStan\Rules\Rule
{

	public function getNodeType(): string
	{
		return Variable::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!is_string($node->name) || $node->name !== 'this') {
			return [];
		}

		if ($scope->isInClosureBind()) {
			return [];
		}

		if (!$scope->isInClass()) {
			return [
				RuleErrorBuilder::message('Using $this outside a class.')->build(),
			];
		}

		$function = $scope->getFunction();
		if (!$function instanceof MethodReflection) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		if ($function->isStatic()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Using $this in static method %s::%s().',
					$scope->getClassReflection()->getDisplayName(),
					$function->getName()
				))->build(),
			];
		}

		return [];
	}

}
