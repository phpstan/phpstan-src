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
	 * @return ParametersAcceptorWithPhpDocs[]|null
	 */
	public function getNamedArgumentsVariants(): ?array;

	public function getAsserts(): Assertions;

	public function getSelfOutType(): ?Type;

	public function returnsByReference(): TrinaryLogic;

	public function isFinalByKeyword(): TrinaryLogic;

}
