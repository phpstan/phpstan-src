<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;

/**
 * The interface NodeCallbackInvoker can be typehinted in 2nd parameter of Rule::processNode():
 *
 * ```php
 * public function processNode(Node $node, Scope&NodeCallbackInvoker $scope): array
 * ```
 *
 * It can be used to invoke rules for virtual made-up nodes.
 *
 * For example: You're writing a rule for a method with declaration like:
 *
 * ```php
 * public static create(string $class, mixed ...$args)
 * ```
 *
 * And you'd like to check `Factory::create(Foo::class, 1, 2, 3)` as if it were
 * `new Foo(1, 2, 3)`.
 *
 * You can call `$scope->invokeNodeCallback(new New_(new Name($className), $args))`
 *
 * And PHPStan will call all the registered rules for New_, checking as if the instantiation
 * is actually in the code.
 *
 * @api
 */
interface NodeCallbackInvoker
{

	public function invokeNodeCallback(Node $node): void;

}
