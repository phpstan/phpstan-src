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

	/** @var \PHPStan\Rules\FunctionDefinitionCheck */
	private $check;

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
			'Parameter $%s of anonymous function has invalid typehint type %s.',
			'Return typehint of anonymous function has invalid type %s.'
		);
	}

}
