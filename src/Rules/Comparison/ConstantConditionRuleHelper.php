<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\BooleanType;

#[AutowiredService]
final class ConstantConditionRuleHelper
{

	public function __construct(
		#[AutowiredParameter]
		private bool $treatPhpDocTypesAsCertain,
	)
	{
	}

	private function shouldSkip(Expr $expr): bool
	{
		if (
			$expr instanceof Expr\BinaryOp\Equal
			|| $expr instanceof Expr\BinaryOp\NotEqual
		) {
			return true;
		}

		if (
			$expr instanceof Expr\Instanceof_
			|| $expr instanceof Expr\BinaryOp\Identical
			|| $expr instanceof Expr\BinaryOp\NotIdentical
			|| $expr instanceof Expr\BooleanNot
			|| $expr instanceof Expr\BinaryOp\BooleanOr
			|| $expr instanceof Expr\BinaryOp\BooleanAnd
			|| $expr instanceof Expr\Ternary
			|| $expr instanceof Expr\Isset_
			|| $expr instanceof Expr\Empty_
			|| $expr instanceof Expr\BinaryOp\Greater
			|| $expr instanceof Expr\BinaryOp\GreaterOrEqual
			|| $expr instanceof Expr\BinaryOp\Smaller
			|| $expr instanceof Expr\BinaryOp\SmallerOrEqual
		) {
			// already checked by different rules
			return true;
		}

		return false;
	}

	public function getBooleanType(Scope $scope, Expr $expr): BooleanType
	{
		if ($this->shouldSkip($expr)) {
			return new BooleanType();
		}

		if ($this->treatPhpDocTypesAsCertain) {
			return $scope->getType($expr)->toBoolean();
		}

		return $scope->getNativeType($expr)->toBoolean();
	}

	public function getNativeBooleanType(Scope $scope, Expr $expr): BooleanType
	{
		if ($this->shouldSkip($expr)) {
			return new BooleanType();
		}

		return $scope->getNativeType($expr)->toBoolean();
	}

}
