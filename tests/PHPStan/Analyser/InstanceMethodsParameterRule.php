<?php declare(strict_types = 1); // lint >= 8.0

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<FullyQualified>
 */
class InstanceMethodsParameterRule implements Rule
{

	public function getNodeType(): string
	{
		return FullyQualified::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return [
			RuleErrorBuilder::message(sprintf('Name %s found in method %s', $node->toString(), $scope->getFunction()?->getName() ?? 'null'))->identifier('test.instanceOfMethodsParameterRule')->build(),
		];
	}

}
