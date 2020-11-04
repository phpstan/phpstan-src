<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InArrowFunctionNode;
use PHPStan\Rules\FunctionReturnTypeCheck;
use PHPStan\Type\ObjectType;

/**
 * @implements \PHPStan\Rules\Rule<\PHPStan\Node\InArrowFunctionNode>
 */
class ArrowFunctionReturnTypeRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Rules\FunctionReturnTypeCheck $returnTypeCheck;

	public function __construct(FunctionReturnTypeCheck $returnTypeCheck)
	{
		$this->returnTypeCheck = $returnTypeCheck;
	}

	public function getNodeType(): string
	{
		return InArrowFunctionNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInAnonymousFunction()) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		/** @var \PHPStan\Type\Type $returnType */
		$returnType = $scope->getAnonymousFunctionReturnType();
		$generatorType = new ObjectType(\Generator::class);

		return $this->returnTypeCheck->checkReturnType(
			$scope,
			$returnType,
			$node->getOriginalNode()->expr,
			'Anonymous function should return %s but empty return statement found.',
			'Anonymous function with return type void returns %s but should not return anything.',
			'Anonymous function should return %s but returns %s.',
			'Anonymous function should never return but return statement found.',
			$generatorType->isSuperTypeOf($returnType)->yes()
		);
	}

}
