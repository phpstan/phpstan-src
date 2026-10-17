<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * @implements Collector<never, array{string}>
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/ImpossibleCheckTypeReportedCollector.cpp')]
final class ImpossibleCheckTypeReportedCollector implements Collector
{

	public function getNodeType(): string
	{
		throw new ShouldNotHappenException();
	}

	public function processNode(Node $node, Scope $scope)
	{
		throw new ShouldNotHappenException();
	}

}
