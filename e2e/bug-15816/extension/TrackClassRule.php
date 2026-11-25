<?php declare(strict_types = 1);

namespace Bug15816;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;

/** @implements Rule<InClassNode> */
final class TrackClassRule implements Rule
{

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->getClassReflection()->getName() === 'App\Uses') {
			$scope->trackClassDependency('App\Tracked');
		}

		return [];
	}

}
