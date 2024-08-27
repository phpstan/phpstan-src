<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InFunctionNode;
use PHPStan\Rules\Rule;
use function count;

/**
 * @implements Rule<InFunctionNode>
 */
final class FunctionAssertRule implements Rule
{

	public function __construct(private AssertRuleHelper $helper)
	{
	}

	public function getNodeType(): string
	{
		return InFunctionNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$function = $node->getFunctionReflection();
		$variants = $function->getVariants();
		if (count($variants) !== 1) {
			return [];
		}

		return $this->helper->check($function, $variants[0]);
	}

}
