<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function in_array;
use function sprintf;

/**
 * @implements Rule<Node\Expr\ConstFetch>
 */
class ConstantRule implements Rule
{

	/**
	 * @param list<string> $dynamicConstantNames
	 */
	public function __construct(private readonly array $dynamicConstantNames)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\ConstFetch::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->hasConstant($node->name)) {
			$constName = (string) $node->name;

			if (in_array($constName, $this->dynamicConstantNames, true)) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf(
					'Constant %s not found.',
					$constName,
				))->discoveringSymbolsTip()->build(),
			];
		}

		return [];
	}

}
