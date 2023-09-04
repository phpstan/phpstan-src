<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PhpParser\Node\Stmt\Catch_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Throwable;
use function array_merge;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Catch_>
 */
class CaughtExceptionExistenceRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		private bool $checkClassCaseSensitivity,
	)
	{
	}

	public function getNodeType(): string
	{
		return Catch_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		foreach ($node->types as $class) {
			$className = (string) $class;
			if (!$this->reflectionProvider->hasClass($className)) {
				if ($scope->isInClassExists($className)) {
					continue;
				}
				$errors[] = RuleErrorBuilder::message(sprintf('Caught class %s not found.', $className))
					->line($class->getLine())
					->identifier('class.notFound')
					->discoveringSymbolsTip($className)
					->build();
				continue;
			}

			$classReflection = $this->reflectionProvider->getClass($className);
			if (!$classReflection->isInterface() && !$classReflection->implementsInterface(Throwable::class)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Caught class %s is not an exception.', $classReflection->getDisplayName()))
					->line($class->getLine())
					->identifier('catch.notThrowable')
					->build();
			}

			if (!$this->checkClassCaseSensitivity) {
				continue;
			}

			$errors = array_merge(
				$errors,
				$this->classCaseSensitivityCheck->checkClassNames([new ClassNameNodePair($className, $class)]),
			);
		}

		return $errors;
	}

}
