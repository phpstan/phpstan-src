<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\UnusedFunctionParametersCheck;

/**
 * @implements \PHPStan\Rules\Rule<InClassMethodNode>
 */
class UnusedConstructorParametersRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Rules\UnusedFunctionParametersCheck $check;

	public function __construct(UnusedFunctionParametersCheck $check)
	{
		$this->check = $check;
	}

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$method = $scope->getFunction();
		if (!$method instanceof MethodReflection) {
			return [];
		}

		$originalNode = $node->getOriginalNode();
		if (strtolower($method->getName()) !== '__construct' || $originalNode->stmts === null) {
			return [];
		}

		if (count($originalNode->params) === 0) {
			return [];
		}

		$message = sprintf(
			'Constructor of class %s has an unused parameter $%%s.',
			$scope->getClassReflection()->getDisplayName()
		);
		if ($scope->getClassReflection()->isAnonymous()) {
			$message = 'Constructor of an anonymous class has an unused parameter $%s.';
		}

		return $this->check->getUnusedParameters(
			$scope,
			array_map(static function (Param $parameter): string {
				if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
					throw new \PHPStan\ShouldNotHappenException();
				}
				return $parameter->var->name;
			}, array_values(array_filter($originalNode->params, static function (Param $parameter): bool {
				return $parameter->flags === 0;
			}))),
			$originalNode->stmts,
			$message,
			'constructor.unusedParameter',
			[]
		);
	}

}
