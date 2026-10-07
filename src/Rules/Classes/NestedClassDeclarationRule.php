<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * PHP does not compile a class, interface, trait or enum declared inside a class: a method body,
 * or a closure in one. The analysis skips such a declaration, and this rule reports it.
 *
 * @implements Rule<Node\Stmt\ClassLike>
 */
#[RegisteredRule(level: 0)]
final class NestedClassDeclarationRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\ClassLike::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!isset($node->namespacedName) || !$scope->isInClass()) {
			return [];
		}

		if ($node instanceof Node\Stmt\Interface_) {
			$kind = 'Interface';
		} elseif ($node instanceof Node\Stmt\Trait_) {
			$kind = 'Trait';
		} elseif ($node instanceof Node\Stmt\Enum_) {
			$kind = 'Enum';
		} else {
			$kind = 'Class';
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'%s %s is declared in a method, but class declarations may not be nested.',
				$kind,
				$node->namespacedName->toString(),
			))
				->identifier('class.nested')
				->nonIgnorable()
				->build(),
		];
	}

}
