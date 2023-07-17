<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function sprintf;

/**
 * @implements Rule<MethodReturnStatementsNode>
 */
class MethodNeverRule implements Rule
{

	public function __construct(private NeverRuleHelper $helper)
	{
	}

	public function getNodeType(): string
	{
		return MethodReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (count($node->getReturnStatements()) > 0) {
			return [];
		}

		$method = $node->getMethodReflection();

		$returnType = ParametersAcceptorSelector::selectSingle($method->getVariants())->getReturnType();
		$helperResult = $this->helper->shouldReturnNever($node, $returnType);
		if ($helperResult === false) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Method %s::%s() always %s, it should have return type "never".',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
				count($helperResult) === 0 ? 'throws an exception' : 'terminates script execution',
			))->identifier('phpstanPlayground.never')->build(),
		];
	}

}
