<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\UnusedFunctionParametersCheck;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\ClassMethod>
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
		return ClassMethod::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		if ($node->name->name !== '__construct' || $node->stmts === null) {
			return [];
		}

		if (count($node->params) === 0) {
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
			}, $node->params),
			$node->stmts,
			$message,
			'constructor.unusedParameter',
			[]
		);
	}

}
