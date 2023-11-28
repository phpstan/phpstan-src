<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use Generator;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InArrowFunctionNode;
use PHPStan\Rules\FunctionReturnTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * @implements Rule<InArrowFunctionNode>
 */
class ArrowFunctionReturnTypeRule implements Rule
{

	public function __construct(private FunctionReturnTypeCheck $returnTypeCheck)
	{
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
		$isVoidSuperType = $returnType->isVoid();
		if ($originalNode->returnType === null && $isVoidSuperType->yes()) {
			return [];
		}

		$exprType = $scope->getType($originalNode->expr);
		if (
			$returnType instanceof NeverType
			&& $returnType->isExplicit()
			&& $exprType instanceof NeverType
			&& $exprType->isExplicit()
		) {
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
