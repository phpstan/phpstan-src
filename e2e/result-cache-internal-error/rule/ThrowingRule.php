<?php declare(strict_types = 1);

namespace ResultCacheE2EInternalError;

use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use RuntimeException;

/**
 * @implements Rule<Function_>
 */
final class ThrowingRule implements Rule
{

	public function getNodeType(): string
	{
		return Function_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		throw new RuntimeException('The rule crashed.');
	}

}
