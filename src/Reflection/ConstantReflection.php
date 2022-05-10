<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node\Expr;

/** @api */
interface ConstantReflection extends ClassMemberReflection, GlobalConstantReflection
{

	/**
	 * @deprecated Use getValueExpr()
	 * @return mixed
	 */
	public function getValue();

	public function getValueExpr(): Expr;

}
