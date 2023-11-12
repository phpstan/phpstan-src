<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Analyser\Scope;

/**
 * @api
 *
 * This interface allows you to write custom logic that can dynamically decide
 * whether an exception is checked or unchecked type.
 *
 * Because the interface accepts a Scope, you can ask about the place in the code where
 * it's being decided - a file, a namespace or a class name.
 *
 * There can only be a single ExceptionTypeResolver per project, and you can register it
 * in your configuration file like this:
 *
 * ```
 *  services:
 *      exceptionTypeResolver!:
 *          class: PHPStan\Rules\Exceptions\ExceptionTypeResolver
 *  ```
 *
 * You can also take advantage of the `PHPStan\Rules\Exceptions\DefaultExceptionTypeResolver`
 * by injecting it into the constructor of your ExceptionTypeResolver
 * and delegate the logic of the classes and places you don't care about.
 *
 * DefaultExceptionTypeResolver decides the type of the exception based on configuration
 * parameters like `exceptions.uncheckedExceptionClasses` etc.
 *
 * Learn more: https://phpstan.org/blog/bring-your-exceptions-under-control
 */
interface ExceptionTypeResolver
{

	public function isCheckedException(string $className, Scope $scope): bool;

}
