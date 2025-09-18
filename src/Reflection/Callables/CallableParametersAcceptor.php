<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Callables;

use PHPStan\Node\InvalidateExprNode;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;

/**
 * @api
 */
interface CallableParametersAcceptor extends ParametersAcceptor
{

	/**
	 * @return SimpleThrowPoint[]
	 */
	public function getThrowPoints(): array;

	public function isPure(): TrinaryLogic;

	public function acceptsNamedArguments(): TrinaryLogic;

	/**
	 * @return SimpleImpurePoint[]
	 */
	public function getImpurePoints(): array;

	/**
	 * @return InvalidateExprNode[]
	 */
	public function getInvalidateExpressions(): array;

	/**
	 * @return string[]
	 */
	public function getUsedVariables(): array;

	/**
	 * Has the #[\NoDiscard] attribute - on PHP 8.5+ if the function's return
	 * value is unused at runtime a warning is emitted, PHPStan will emit the
	 * warning during analysis and on older PHP versions too
	 */
	public function mustUseReturnValue(): TrinaryLogic;

}
