<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function sprintf;

/**
 * @implements Rule<FunctionReturnStatementsNode>
 */
final class FunctionNeverRule implements Rule
{

	public function __construct(private NeverRuleHelper $helper)
	{
	}

	public function getNodeType(): string
	{
		return FunctionReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (count($node->getReturnStatements()) > 0) {
			return [];
		}

		$function = $node->getFunctionReflection();

		$returnType = $function->getReturnType();
		$helperResult = $this->helper->shouldReturnNever($node, $returnType);
		if ($helperResult === false) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Function %s() always %s, it should have return type "never".',
				$function->getName(),
				count($helperResult) === 0 ? 'throws an exception' : 'terminates script execution',
			))->identifier('phpstanPlayground.never')->build(),
		];
	}

}
