<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;

/**
 * Describes one signature variant of a function or method.
 *
 * A function/method may have multiple ParametersAcceptor variants — for example,
 * the built-in `array_map` function has different signatures depending on argument count.
 * Each variant describes the template type parameters, positional parameters, variadicity,
 * and return type.
 *
 * This is the base interface. ExtendedParametersAcceptor adds separate PHPDoc/native
 * return types and extended parameter reflection. CallableParametersAcceptor adds
 * throw points, impure points, and purity information.
 *
 * Use ParametersAcceptorSelector to choose the best variant for a given call site.
 *
 * @api
 */
interface ParametersAcceptor
{

	/**
	 * Functions that access variadic arguments implicitly.
	 * Used by PHPStan to detect implicit variadic behavior.
	 */
	public const VARIADIC_FUNCTIONS = [
		'func_get_args',
		'func_get_arg',
		'func_num_args',
	];

	/**
	 * Returns the template type parameters declared on this signature.
	 *
	 * Maps template names to their bound types (e.g. @template T of object).
	 */
	public function getTemplateTypeMap(): TemplateTypeMap;

	/**
	 * Returns the template type map with types resolved from the call site.
	 *
	 * After template type inference at a call site, this map contains the
	 * concrete types inferred for each template parameter.
	 */
	public function getResolvedTemplateTypeMap(): TemplateTypeMap;

	/**
	 * Returns the list of parameters in this signature.
	 *
	 * @return list<ParameterReflection>
	 */
	public function getParameters(): array;

	/** Whether this signature accepts additional arguments (is variadic). */
	public function isVariadic(): bool;

	/**
	 * Returns the return type of this signature (combined PHPDoc + native type).
	 */
	public function getReturnType(): Type;

}
