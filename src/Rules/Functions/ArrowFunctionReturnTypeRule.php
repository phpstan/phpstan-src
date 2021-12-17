<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use Generator;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InArrowFunctionNode;
use PHPStan\Rules\FunctionReturnTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VoidType;

/**
 * @implements Rule<InArrowFunctionNode>
 */
class ArrowFunctionReturnTypeRule implements Rule
{

	private FunctionReturnTypeCheck $returnTypeCheck;

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
			throw new ShouldNotHappenException();
		}

		/** @var Type $returnType */
		$returnType = $scope->getAnonymousFunctionReturnType();
		$generatorType = new ObjectType(Generator::class);

		$originalNode = $node->getOriginalNode();
		$isVoidSuperType = (new VoidType())->isSuperTypeOf($returnType);
		if ($originalNode->returnType === null && $isVoidSuperType->yes()) {
			return [];
		}

		return $this->returnTypeCheck->checkReturnType(
			$scope,
			$returnType,
			$originalNode->expr,
			$originalNode->expr,
			'Anonymous function should return %s but empty return statement found.',
			'Anonymous function with return type void returns %s but should not return anything.',
			'Anonymous function should return %s but returns %s.',
			'Anonymous function should never return but return statement found.',
			$generatorType->isSuperTypeOf($returnType)->yes(),
		);
	}

}
