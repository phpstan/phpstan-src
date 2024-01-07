<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Throw_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use function implode;
use function sprintf;

/** @implements Rule<Throw_> */
class ScopeFunctionCallStackRule implements Rule
{

	public function getNodeType(): string
	{
		return Throw_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$messages = [];
		foreach ($scope->getFunctionCallStack() as $reflection) {
			if ($reflection instanceof FunctionReflection) {
				$messages[] = $reflection->getName();
				continue;
			}

			$messages[] = sprintf('%s::%s', $reflection->getDeclaringClass()->getDisplayName(), $reflection->getName());
		}

		return [
			RuleErrorBuilder::message(implode("\n", $messages))->identifier('dummy')->build(),
		];
	}

}
