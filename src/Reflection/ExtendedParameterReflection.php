<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

/**
 * @api
 * @api-do-not-implement
 */
interface ExtendedParameterReflection extends ParameterReflection
{

	public function getPhpDocType(): Type;

	public function hasNativeType(): bool;

	public function getNativeType(): Type;

	public function getOutType(): ?Type;

	public function isImmediatelyInvokedCallable(): TrinaryLogic;

	public function getClosureThisType(): ?Type;

	/**
	 * @return list<AttributeReflection>
	 */
	public function getAttributes(): array;

}
