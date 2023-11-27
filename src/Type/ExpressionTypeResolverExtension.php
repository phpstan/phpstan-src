<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;

/**
 * To register it in the configuration file use the `phpstan.broker.expressionTypeResolverExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.broker.expressionTypeResolverExtension
 * ```
 *
 * You should return null in your extension if you don't care about given Expr.
 *
 * @api
 */
interface ExpressionTypeResolverExtension
{

	public function getType(Expr $expr, Scope $scope): ?Type;

}
