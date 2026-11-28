<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\DependencyInjection\ExtensionInterface;

/**
 * This is the extension interface to implement if you want to add a tip
 * to an error reported by a rule, based on the node and scope.
 *
 * The tip is added after the tips the error already has. The extension is asked
 * only about errors that can be ignored, and that no IgnoreErrorExtension ignored.
 *
 * To register it in the configuration file use the `phpstan.errorTipExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.errorTipExtension
 * ```
 *
 * @api
 */
#[ExtensionInterface(tag: self::EXTENSION_TAG)]
interface ErrorTipExtension
{

	public const EXTENSION_TAG = 'phpstan.errorTipExtension';

	public function getTip(Error $error, Node $node, Scope $scope): ?string;

}
