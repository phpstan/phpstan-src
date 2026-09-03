<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;

/**
 * Marks a template type that a call left unresolved on purpose: every received type was
 * absorbed by another member of a union parameter type, so `T|null` receiving `null` says
 * nothing about `T`.
 *
 * It behaves exactly like an unresolved template everywhere a type gets materialized -
 * it is an ErrorType, so the template falls back to its default or bound. The distinct
 * class only tells FunctionCallParametersCheck not to ask the caller to resolve a template
 * the signature already declared as optional.
 */
final class AbsorbedTemplateArgumentType extends ErrorType
{

	public function equals(Type $type): bool
	{
		return $type instanceof ErrorType;
	}

}
