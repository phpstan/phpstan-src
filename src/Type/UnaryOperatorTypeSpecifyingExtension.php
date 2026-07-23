<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Broker\BrokerFactory;
use PHPStan\DependencyInjection\ExtensionInterface;

/**
 * This is the extension interface to implement if you want to describe
 * how unary operators like -, +, ~ should infer types
 * for PHP extensions that overload the behaviour, like GMP.
 *
 * To register it in the configuration file use the `phpstan.broker.unaryOperatorTypeSpecifyingExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.broker.unaryOperatorTypeSpecifyingExtension
 * ```
 *
 * @api
 */
#[ExtensionInterface(tag: BrokerFactory::UNARY_OPERATOR_TYPE_SPECIFYING_EXTENSION_TAG)]
interface UnaryOperatorTypeSpecifyingExtension
{

	public function isOperatorSupported(string $operatorSigil, Type $operand): bool;

	public function specifyType(string $operatorSigil, Type $operand): Type;

}
