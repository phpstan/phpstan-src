<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function is_string;
use function sprintf;

/**
 * @implements Rule<Node>
 */
class InvalidPromotedPropertiesRule implements Rule
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getNodeType(): string
	{
		return Node::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			!$node instanceof Node\Expr\ArrowFunction
			&& !$node instanceof Node\Stmt\ClassMethod
			&& !$node instanceof Node\Expr\Closure
			&& !$node instanceof Node\Stmt\Function_
		) {
			return [];
		}

		$hasPromotedProperties = false;
		foreach ($node->params as $param) {
			if ($param->flags === 0) {
				continue;
			}

			$hasPromotedProperties = true;
			break;
		}

		if (!$hasPromotedProperties) {
			return [];
		}

		if (!$this->phpVersion->supportsPromotedProperties()) {
			return [
				RuleErrorBuilder::message(
					'Promoted properties are supported only on PHP 8.0 and later.',
				)->identifier('property.promotedNotSupported')->nonIgnorable()->build(),
			];
		}

		if (
			!$node instanceof Node\Stmt\ClassMethod
			|| (
				$node->name->toLowerString() !== '__construct'
				&& $node->getAttribute('originalTraitMethodName') !== '__construct')
		) {
			return [
				RuleErrorBuilder::message(
					'Promoted properties can be in constructor only.',
				)->identifier('property.invalidPromoted')->nonIgnorable()->build(),
			];
		}

		if ($node->stmts === null) {
			return [
				RuleErrorBuilder::message(
					'Promoted properties are not allowed in abstract constructors.',
				)->identifier('property.invalidPromoted')->nonIgnorable()->build(),
			];
		}

		$errors = [];
		foreach ($node->params as $param) {
			if ($param->flags === 0) {
				continue;
			}

			if (!$param->var instanceof Node\Expr\Variable || !is_string($param->var->name)) {
				throw new ShouldNotHappenException();
			}

			if (!$param->variadic) {
				continue;
			}

			$propertyName = $param->var->name;
			$errors[] = RuleErrorBuilder::message(
				sprintf('Promoted property parameter $%s can not be variadic.', $propertyName),
			)->identifier('property.invalidPromoted')->nonIgnorable()->line($param->getLine())->build();
		}

		return $errors;
	}

}
