<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;

/**
 * @implements Collector<never, array{class-string<Rule<covariant Node>>, trait-string, string, null}|array{class-string<Rule<covariant Node>>, trait-string, string, bool, Error|array<mixed>}>
 */
final class ConstantConditionInTraitCollector implements Collector
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
