<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<Node\Expr\StaticPropertyFetch>
 */
#[RegisteredRule(level: 2)]
final class AccessPrivatePropertyThroughStaticRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Expr\StaticPropertyFetch::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\VarLikeIdentifier) {
			return [];
		}
		if (!$node->class instanceof Name) {
			return [];
		}

		$propertyName = $node->name->name;
		$className = $node->class;
		if ($className->toLowerString() !== 'static') {
			return [];
		}

		$classType = $scope->resolveTypeByName($className);
		if (!$classType->hasStaticProperty($propertyName)->yes()) {
			return [];
		}

		$property = $classType->getStaticProperty($propertyName, $scope);
		if (!$property->isPrivate()) {
			return [];
		}

		if ($scope->isInClass() && $scope->getClassReflection()->isFinal()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Unsafe access to private property %s::$%s through static::.',
				$property->getDeclaringClass()->getDisplayName(),
				$propertyName,
			))->identifier('staticClassAccess.privateProperty')->build(),
		];
	}

}
