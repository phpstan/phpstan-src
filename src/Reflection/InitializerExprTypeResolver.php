<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PHPStan\Node\Expr\MixedTypeExpr;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

class InitializerExprTypeResolver
{

	public function getType(Expr $expr, InitializerExprContext $context): Type
	{
		if ($expr instanceof MixedTypeExpr) {
			return new MixedType();
		}
		if ($expr instanceof LNumber) {
			return new ConstantIntegerType($expr->value);
		}
		if ($expr instanceof DNumber) {
			return new ConstantFloatType($expr->value);
		}
		if ($expr instanceof String_) {
			return new ConstantStringType($expr->value);
		}

		// todo
		/*
		- [ ] Expr\Array_
		- [ ] Expr\UnaryPlus
		- [ ] Expr\UnaryMinus
		- [ ] Expr\BooleanNot
		- [ ] Expr\BitwiseNot
		- [ ] Expr\BinaryOp
		- [ ] Expr\Ternary
		- [ ] Expr\ArrayDimFetch
		- [ ] Expr\ConstFetch
		- [ ] Expr\ClassConstFetch
		- [ ] MagicConst\Dir
		- [ ] MagicConst\File
		- [ ] MagicConst\Class_
		- [ ] MagicConst\Line
		- [ ] MagicConst\Namespace_
		- [ ] MagicConst\Method
		- [ ] MagicConst\Function_
		- [ ] MagicConst\Trait_
		- [ ] New_
		- [ ] All supported BinaryOp?
		 */

		return new MixedType();
	}

}
