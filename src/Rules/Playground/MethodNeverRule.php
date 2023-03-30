<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\NeverType;
use function count;
use function sprintf;

/**
 * @implements Rule<MethodReturnStatementsNode>
 */
class MethodNeverRule implements Rule
{

	public function getNodeType(): string
	{
		return MethodReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (count($node->getReturnStatements()) > 0) {
			return [];
		}

		$method = $scope->getFunction();
		if (!$method instanceof MethodReflection) {
			throw new ShouldNotHappenException();
		}

		$returnType = ParametersAcceptorSelector::selectSingle($method->getVariants())->getReturnType();
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
				'Method %s::%s() always %s, it should have return type "never".',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
				count($other) === 0 ? 'throws an exception' : 'terminates script execution',
			))->identifier('phpstanPlayground.never')->build(),
		];
	}

}
