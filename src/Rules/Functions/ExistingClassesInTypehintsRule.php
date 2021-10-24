<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\InFunctionNode;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Rules\FunctionDefinitionCheck;

/**
 * @implements \PHPStan\Rules\Rule<InFunctionNode>
 */
class ExistingClassesInTypehintsRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Rules\FunctionDefinitionCheck $check;

	public function __construct(FunctionDefinitionCheck $check)
	{
		$this->check = $check;
	}

	public function getNodeType(): string
	{
		return InFunctionNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->getFunction() instanceof PhpFunctionFromParserNodeReflection) {
			return [];
		}

		$functionName = SprintfHelper::escapeFormatString($scope->getFunction()->getName());

		return $this->check->checkFunction(
			$node->getOriginalNode(),
			$scope->getFunction(),
			sprintf(
				'Parameter $%%s of function %s() has invalid type %%s.',
				$functionName
			),
			sprintf(
				'Function %s() has invalid return type %%s.',
				$functionName
			),
			sprintf('Function %s() uses native union types but they\'re supported only on PHP 8.0 and later.', $functionName),
			sprintf('Template type %%s of function %s() is not referenced in a parameter.', $functionName)
		);
	}

}
