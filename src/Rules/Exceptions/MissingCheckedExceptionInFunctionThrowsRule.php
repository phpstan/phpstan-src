<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function sprintf;

/**
 * @implements Rule<FunctionReturnStatementsNode>
 */
class MissingCheckedExceptionInFunctionThrowsRule implements Rule
{

	private MissingCheckedExceptionInThrowsCheck $check;

	public function __construct(MissingCheckedExceptionInThrowsCheck $check)
	{
		$this->check = $check;
	}

	public function getNodeType(): string
	{
		return FunctionReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$statementResult = $node->getStatementResult();
		$functionReflection = $scope->getFunction();
		if (!$functionReflection instanceof FunctionReflection) {
			throw new ShouldNotHappenException();
		}

		$errors = [];
		foreach ($this->check->check($functionReflection->getThrowType(), $statementResult->getThrowPoints()) as [$className, $throwPointNode, $newCatchPosition]) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Function %s() throws checked exception %s but it\'s missing from the PHPDoc @throws tag.',
				$functionReflection->getName(),
				$className
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
