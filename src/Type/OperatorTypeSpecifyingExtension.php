<?php declare(strict_types = 1);

namespace PHPStan\Type;

/**
 * This is the extension interface to implement if you want to describe
 * how arithmetic operators like +, -, *, ^, / should infer types
 * for PHP extensions that overload the behaviour, like GMP.
 *
 * To register it in the configuration file use the `phpstan.broker.operatorTypeSpecifyingExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.broker.operatorTypeSpecifyingExtension
 * ```
 *
 * Learn more: https://github.com/phpstan/phpstan/pull/2114
 *
 * @api
 */
interface OperatorTypeSpecifyingExtension
{

	public function isOperatorSupported(string $operatorSigil, Type $leftSide, Type $rightSide): bool;

	public function specifyType(string $operatorSigil, Type $leftSide, Type $rightSide): Type;

}
