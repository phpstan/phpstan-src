<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

/**
 * The purpose of this interface is to be able to
 * answer more questions about methods
 * without breaking backward compatibility
 * with existing MethodsClassReflectionExtension.
 *
 * Developers are meant to only implement MethodReflection
 * and its methods in their code.
 *
 * New methods on ExtendedMethodReflection will be added
 * in minor versions.
 *
 * @api
 */
interface ExtendedMethodReflection extends MethodReflection
{

	/**
	 * @return ParametersAcceptorWithPhpDocs[]
	 */
	public function getVariants(): array;

	/**
	 * @internal
	 */
	public function getOnlyVariant(): ParametersAcceptorWithPhpDocs;

	/**
	 * @return ParametersAcceptorWithPhpDocs[]|null
	 */
	public function getNamedArgumentsVariants(): ?array;

	public function acceptsNamedArguments(): bool;

	public function getAsserts(): Assertions;

	public function getSelfOutType(): ?Type;

	public function returnsByReference(): TrinaryLogic;

	public function isFinalByKeyword(): TrinaryLogic;

	public function isAbstract(): TrinaryLogic|bool;

	/**
	 * This indicates whether the method has phpstan-pure
	 * or phpstan-impure annotation above it.
	 *
	 * In most cases asking hasSideEffects() is much more practical
	 * as it also accounts for void return type (method being always impure).
	 */
	public function isPure(): TrinaryLogic;

}
