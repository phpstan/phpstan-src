<?php declare(strict_types = 1);

namespace ResultCacheE2ERuleEdit;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<CollectedDataNode>
 */
final class CalledRule implements Rule
{

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		foreach ($node->get(CalledCollector::class) as $file => $names) {
			foreach ($names as $name) {
				if ($name !== 'foo') {
					continue;
				}
				$errors[] = RuleErrorBuilder::message('Called v1: ' . $name)->identifier('called.foo')->file($file)->line(1)->build();
			}
		}

		return $errors;
	}

}
