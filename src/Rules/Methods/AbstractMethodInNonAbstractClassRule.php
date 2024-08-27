<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\ClassMethod>
 */
final class AbstractMethodInNonAbstractClassRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\ClassMethod::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			throw new ShouldNotHappenException();
		}

		$class = $scope->getClassReflection();

		if (!$class->isAbstract() && $node->isAbstract()) {
			$description = $class->getClassTypeDescription();
			return [
				RuleErrorBuilder::message(sprintf(
					'%s %s contains abstract method %s().',
					$description === 'Class' ? 'Non-abstract class' : $description,
					$class->getDisplayName(),
					$node->name->toString(),
				))
					->nonIgnorable()
					->identifier('method.abstract')
					->build(),
			];
		}

		if (!$class->isAbstract() && !$class->isInterface() && $node->getStmts() === null) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Non-abstract method %s::%s() must contain a body.',
					$class->getDisplayName(),
					$node->name->toString(),
				))
					->nonIgnorable()
					->identifier('method.nonAbstract')
					->build(),
			];
		}

		return [];
	}

}
