<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;
use function count;

/**
 * @implements Rule<InClassMethodNode>
 */
class MethodConditionalReturnTypeRule implements Rule
{

	public function __construct(private ConditionalReturnTypeRuleHelper $helper)
	{
	}

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$method = $scope->getFunction();
		if ($method === null) {
			throw new ShouldNotHappenException();
		}

		$variants = $method->getVariants();
		if (count($variants) !== 1) {
			return [];
		}

		return $this->helper->check($variants[0]);
	}

}
