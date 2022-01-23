<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function sprintf;

/**
 * @implements Rule<MethodReturnStatementsNode>
 */
class MissingCheckedExceptionInMethodThrowsRule implements Rule
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
		$methodReflection = $scope->getFunction();
		if (!$methodReflection instanceof MethodReflection) {
			throw new ShouldNotHappenException();
		}

		$errors = [];
		foreach ($this->check->check($methodReflection->getThrowType(), $statementResult->getThrowPoints()) as [$className, $throwPointNode, $newCatchPosition]) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() throws checked exception %s but it\'s missing from the PHPDoc @throws tag.',
				$methodReflection->getDeclaringClass()->getDisplayName(),
				$methodReflection->getName(),
				$className,
			))
				->line($throwPointNode->getLine())
				->identifier('exceptions.missingThrowsTag')
				->metadata([
					'exceptionName' => $className,
					'newCatchPosition' => $newCatchPosition,
					'statementDepth' => $throwPointNode->getAttribute('statementDepth'),
					'statementOrder' => $throwPointNode->getAttribute('statementOrder'),
				])
				->build();
		}

		return $errors;
	}

}
