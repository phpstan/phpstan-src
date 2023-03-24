<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Type;

/**
 * This is the interface type node resolver extensions implement for custom PHPDoc types.
 *
 * To register it in the configuration file use the `phpstan.phpDoc.typeNodeResolverExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.phpDoc.typeNodeResolverExtension
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/custom-phpdoc-types
 *
 * @api
 */
interface TypeNodeResolverExtension
{

	public const EXTENSION_TAG = 'phpstan.phpDoc.typeNodeResolverExtension';

	public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type;

}
