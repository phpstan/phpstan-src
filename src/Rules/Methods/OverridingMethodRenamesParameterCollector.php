<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * @implements Collector<never, array{class-string, string, class-string, string, string}>
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/OverridingMethodRenamesParameterCollector.cpp')]
final class OverridingMethodRenamesParameterCollector implements Collector
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
