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
class TooWideFunctionThrowTypeRule implements Rule
{

	public function __construct(private TooWideThrowTypeCheck $check)
	{
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

		$throwType = $functionReflection->getThrowType();
		if ($throwType === null) {
			return [];
		}

		$errors = [];
		foreach ($this->check->check($throwType, $statementResult->getThrowPoints()) as $throwClass) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Function %s() has %s in PHPDoc @throws tag but it\'s not thrown.',
				$functionReflection->getName(),
				$throwClass,
			))
				->identifier('exceptions.tooWideThrowType')
				->metadata([
					'exceptionName' => $throwClass,
					'statementDepth' => $node->getAttribute('statementDepth'),
					'statementOrder' => $node->getAttribute('statementOrder'),
				])
				->build();
		}

		return $errors;
	}

}
