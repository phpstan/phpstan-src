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
 * @implements Rule<Node\FunctionLike>
 */
final class InvalidPromotedPropertiesRule implements Rule
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getNodeType(): string
	{
		return Node\FunctionLike::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$hasPromotedProperties = false;

		foreach ($node->getParams() as $param) {
			if ($param->flags !== 0) {
				$hasPromotedProperties = true;
				break;
			}

			if ($param->hooks === []) {
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

		if ($node->getStmts() === null) {
			return [
				RuleErrorBuilder::message(
					'Promoted properties are not allowed in abstract constructors.',
				)->identifier('property.invalidPromoted')->nonIgnorable()->build(),
			];
		}

		$errors = [];
		foreach ($node->getParams() as $param) {
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
			)->identifier('property.invalidPromoted')->nonIgnorable()->line($param->getStartLine())->build();
		}

		return $errors;
	}

}
