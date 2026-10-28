<?php declare(strict_types = 1);

namespace PHPStan\Analyser\CollectedDataReuse;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<CollectedDataNode>
 */
final class OnlyFilesRule implements Rule
{

	public int $runs = 0;

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$this->runs++;

		// a rule that does not ask for collected data runs every time
		$node->get(FirstCollector::class);
		if ($node->isOnlyFilesAnalysis()) {
			return [];
		}

		return [
			RuleErrorBuilder::message('Full analysis.')->file(__FILE__)->line(2)->identifier('tests.full')->build(),
		];
	}

}
