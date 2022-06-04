<?php declare(strict_types = 1);

namespace Bug1396;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use function PHPStan\Testing\assertType;

class TypeSpecifier2
{
	/**
	 * @param (Expr|ConstantScalarType)[]|null $expressions
	 * @api
	 */
	public function specifyTypesInCondition(
		Expr $expr,
			 $expressions
	): SpecifiedTypes
	{
		if ($expr instanceof Node\Expr\BinaryOp\Identical) {
			if ($expressions !== null) {
				/** @var Expr $exprNode */
				$exprNode = $expressions[0];
				/** @var ConstantScalarType $constantType */
				$constantType = $expressions[1];

				if ($constantType->getValue() === null) {
					return $this->create();
				}

				if (
					$constantType instanceof ConstantStringType
				) {
					assertType('string', $constantType->getValue());
				}
			}
		}
	}

	/** @api */
	public function create(
	): SpecifiedTypes
	{
		return new SpecifiedTypes();
	}
}
