<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<MethodReturnStatementsNode>
 */
final class MissingCheckedExceptionInMethodThrowsRule implements Rule
{

	public function __construct(private MissingCheckedExceptionInThrowsCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return MethodReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$statementResult = $node->getStatementResult();
		$methodReflection = $node->getMethodReflection();

		$errors = [];
		foreach ($this->check->check($methodReflection->getThrowType(), $statementResult->getThrowPoints()) as [$className, $throwPointNode]) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() throws checked exception %s but it\'s missing from the PHPDoc @throws tag.',
				$methodReflection->getDeclaringClass()->getDisplayName(),
				$methodReflection->getName(),
				$className,
			))
				->line($throwPointNode->getStartLine())
				->identifier('missingType.checkedException')
				->build();
		}

		return $errors;
	}

}
