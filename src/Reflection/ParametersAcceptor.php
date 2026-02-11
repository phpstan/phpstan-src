<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;

/**
 * Describes one signature variant of a function or method.
 *
 * A function/method may have multiple ParametersAcceptor variants — for example,
 * the built-in `strtok` function has different signatures depending on argument count.
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

	public const VARIADIC_FUNCTIONS = [
		'func_get_args',
		'func_get_arg',
		'func_num_args',
	];

	public function getTemplateTypeMap(): TemplateTypeMap;

	/**
	 * After template type inference at a call site, this map contains the
	 * concrete types inferred for each template parameter.
	 */
	public function getResolvedTemplateTypeMap(): TemplateTypeMap;

	/** @return list<ParameterReflection> */
	public function getParameters(): array;

	public function isVariadic(): bool;

	public function getReturnType(): Type;

}
