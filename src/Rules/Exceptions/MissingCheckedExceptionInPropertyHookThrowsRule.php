<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\PropertyHookReturnStatementsNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function sprintf;
use function ucfirst;

/**
 * @implements Rule<PropertyHookReturnStatementsNode>
 */
final class MissingCheckedExceptionInPropertyHookThrowsRule implements Rule
{

	public function __construct(private MissingCheckedExceptionInThrowsCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return PropertyHookReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$statementResult = $node->getStatementResult();
		$hookReflection = $node->getHookReflection();

		if (!$hookReflection->isPropertyHook()) {
			throw new ShouldNotHappenException();
		}

		$errors = [];
		foreach ($this->check->check($hookReflection->getThrowType(), $statementResult->getThrowPoints()) as [$className, $throwPointNode]) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'%s hook for property %s::$%s throws checked exception %s but it\'s missing from the PHPDoc @throws tag.',
				ucfirst($hookReflection->getPropertyHookName()),
				$hookReflection->getDeclaringClass()->getDisplayName(),
				$hookReflection->getHookedPropertyName(),
				$className,
			))
				->line($throwPointNode->getStartLine())
				->identifier('missingType.checkedException')
				->build();
		}

		return $errors;
	}

}
