<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\FunctionDefinitionCheck;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\Closure>
 */
class ExistingClassesInClosureTypehintsRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Rules\FunctionDefinitionCheck $check;

	public function __construct(FunctionDefinitionCheck $check)
	{
		$this->check = $check;
	}

	public function getNodeType(): string
	{
		return Closure::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return $this->check->checkAnonymousFunction(
			$scope,
			$node->getParams(),
			$node->getReturnType(),
			'Parameter $%s of anonymous function has invalid type %s.',
			'Anonymous function has invalid return type %s.',
			'Anonymous function uses native union types but they\'re supported only on PHP 8.0 and later.',
			'Parameter $%s of anonymous function has unresolvable native type.',
			'Anonymous function has unresolvable native return type.'
		);
	}

}
