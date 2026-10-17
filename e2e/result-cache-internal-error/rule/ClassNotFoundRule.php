<?php declare(strict_types = 1);

namespace ResultCacheE2EInternalError;

use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Function_>
 */
final class ClassNotFoundRule implements Rule
{

	public function getNodeType(): string
	{
		return Function_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		throw new ClassNotFoundException('ResultCacheE2EInternalError\Missing');
	}

}
