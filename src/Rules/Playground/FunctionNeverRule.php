<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\NeverType;
use function count;
use function sprintf;

/**
 * @implements Rule<FunctionReturnStatementsNode>
 */
class FunctionNeverRule implements Rule
{

	public function getNodeType(): string
	{
		return FunctionReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (count($node->getReturnStatements()) > 0) {
			return [];
		}

		$function = $scope->getFunction();
		if (!$function instanceof FunctionReflection) {
			throw new ShouldNotHappenException();
		}

		$returnType = ParametersAcceptorSelector::selectSingle($function->getVariants())->getReturnType();
		if ($returnType instanceof NeverType && $returnType->isExplicit()) {
			return [];
		}

		$other = [];
		foreach ($node->getExecutionEnds() as $executionEnd) {
			if ($executionEnd->getStatementResult()->isAlwaysTerminating()) {
				if (!$executionEnd->getNode() instanceof Node\Stmt\Throw_) {
					$other[] = $executionEnd->getNode();
				}

				continue;
			}

			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Function %s() always %s, it should have return type "never".',
				$function->getName(),
				count($other) === 0 ? 'throws an exception' : 'terminates script execution',
			))->identifier('phpstanPlayground.never')->build(),
		];
	}

}
