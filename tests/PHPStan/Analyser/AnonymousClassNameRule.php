<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Class_>
 */
class AnonymousClassNameRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function getNodeType(): string
	{
		return Class_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$className = isset($node->namespacedName)
			? (string) $node->namespacedName
			: (string) $node->name;
		try {
			$this->reflectionProvider->getClass($className);
		} catch (ClassNotFoundException) {
			return [
				RuleErrorBuilder::message('not found')
					->identifier('tests.anonymousClassName')
					->build(),
			];
		}

		return [
			RuleErrorBuilder::message('found')
				->identifier('tests.anonymousClassName')
				->build(),
		];
	}

}
