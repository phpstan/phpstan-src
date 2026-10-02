<?php declare(strict_types = 1);

namespace ResultCacheE2EValueDependency;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Reports what RegionCollector collected.
 *
 * @implements Rule<CollectedDataNode>
 */
final class RegionRule implements Rule
{

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		foreach ($node->get(RegionCollector::class) as $file => $regions) {
			foreach ($regions as $region) {
				$errors[] = RuleErrorBuilder::message(sprintf('Region %s is used.', $region))->identifier('resultCacheE2E.region')->file($file)->line(7)->build();
			}
		}

		return $errors;
	}

}
