<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<Node\Expr\ClassConstFetch>
 */
final class AccessPrivateConstantThroughStaticRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Expr\ClassConstFetch::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Identifier) {
			return [];
		}
		if (!$node->class instanceof Name) {
			return [];
		}

		$constantName = $node->name->name;
		$className = $node->class;
		if ($className->toLowerString() !== 'static') {
			return [];
		}

		$classType = $scope->resolveTypeByName($className);
		if (!$classType->hasConstant($constantName)->yes()) {
			return [];
		}

		$constant = $classType->getConstant($constantName);
		if (!$constant->isPrivate()) {
			return [];
		}

		if ($scope->isInClass() && $scope->getClassReflection()->isFinal()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Unsafe access to private constant %s::%s through static::.',
				$constant->getDeclaringClass()->getDisplayName(),
				$constantName,
			))->identifier('staticClassAccess.privateConstant')->build(),
		];
	}

}
