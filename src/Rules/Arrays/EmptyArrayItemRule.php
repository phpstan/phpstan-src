<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\LiteralArrayNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @deprecated Since PHP-Parser 5.0 this is a parse error.
 * @implements Rule<LiteralArrayNode>
 */
class EmptyArrayItemRule implements Rule
{

	public function getNodeType(): string
	{
		return LiteralArrayNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		foreach ($node->getItemNodes() as $itemNode) {
			$item = $itemNode->getArrayItem();
			if ($item !== null) {
				continue;
			}

			return [
				RuleErrorBuilder::message('Literal array contains empty item.')
					->nonIgnorable()
					->identifier('array.emptyItem')
					->build(),
			];
		}

		return [];
	}

}
