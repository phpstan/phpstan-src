<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use function implode;
use function sprintf;

/**
 * @implements Rule<CollectedDataNode>
 */
class DummyCollectorRule implements Rule
{

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$data = $node->get(DummyCollector::class);
		$methods = [];
		foreach ($data as $methodNames) {
			foreach ($methodNames as $methodName) {
				if (!isset($methods[$methodName])) {
					$methods[$methodName] = 0;
				}

				$methods[$methodName]++;
			}
		}

		$parts = [];
		foreach ($methods as $methodName => $count) {
			$parts[] = sprintf('%d× %s', $count, $methodName);
		}

		return [
			RuleErrorBuilder::message(implode(', ', $parts))
				->file(__DIR__ . '/data/dummy-collector.php')
				->line(5)
				->identifier('tests.dummyCollector')
				->build(),
		];
	}

}
