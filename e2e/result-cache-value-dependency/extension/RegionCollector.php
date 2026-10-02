<?php declare(strict_types = 1);

namespace ResultCacheE2EValueDependency;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Collects the "region" parameter at the calls to region().
 *
 * @implements Collector<FuncCall, string>
 */
final class RegionCollector implements Collector
{

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	/**
	 * @param Scope&DependencyTracker $scope
	 */
	public function processNode(Node $node, Scope $scope)
	{
		if (!$node->name instanceof Name || $node->name->getLast() !== 'region') {
			return null;
		}

		$scope->trackValueDependency(ParameterValueExtension::class, 'region');

		return Container::getParameter('region') ?? 'missing';
	}

}
