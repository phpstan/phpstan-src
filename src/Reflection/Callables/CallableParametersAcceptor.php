<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Callables;

use PHPStan\Node\InvalidateExprNode;
use PHPStan\Reflection\ParametersAcceptor;

/**
 * @api
 */
interface CallableParametersAcceptor extends ParametersAcceptor
{

	/**
	 * @return SimpleThrowPoint[]
	 */
	public function getThrowPoints(): array;

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

}
