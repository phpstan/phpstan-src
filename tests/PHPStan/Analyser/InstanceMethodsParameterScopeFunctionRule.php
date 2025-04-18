<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function sprintf;

/**
 * @implements Rule<FullyQualified>
 */
class InstanceMethodsParameterScopeFunctionRule implements Rule
{

	public function getNodeType(): string
	{
		return FullyQualified::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($scope->getFunction() !== null) {
			throw new ShouldNotHappenException('All names in the tests should not have a function scope.');
		}

		return [
			RuleErrorBuilder::message(sprintf('Name %s found in function scope null', $node->toString()))->identifier('test.instanceOfMethodsParameterRule')->build(),
		];
	}

}
