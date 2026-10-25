<?php declare(strict_types = 1);

namespace PHPStan\Analyser\CollectedDataReuse;

use PhpParser\Node;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Declares a value of an extension that is not registered.
 *
 * @implements Rule<CollectedDataNode>
 */
final class UnregisteredValueRule implements Rule
{

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	/**
	 * @param Scope&DependencyTracker $scope
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		$scope->trackValueDependency(UnregisteredValueExtension::class, 'key');

		return [
			RuleErrorBuilder::message('Unregistered value.')
				->file(__FILE__)
				->line(1)
				->identifier('tests.unregistered')
				->build(),
		];
	}

}
