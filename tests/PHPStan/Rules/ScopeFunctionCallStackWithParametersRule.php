<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Throw_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\ShouldNotHappenException;
use function implode;
use function sprintf;

/** @implements Rule<Throw_> */
class ScopeFunctionCallStackWithParametersRule implements Rule
{

	public function getNodeType(): string
	{
		return Throw_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$messages = [];
		foreach ($scope->getFunctionCallStackWithParameters() as [$reflection, $parameter]) {
			if ($parameter === null) {
				throw new ShouldNotHappenException();
			}
			if ($reflection instanceof FunctionReflection) {
				$messages[] = sprintf('%s ($%s)', $reflection->getName(), $parameter->getName());
				continue;
			}

			$messages[] = sprintf('%s::%s ($%s)', $reflection->getDeclaringClass()->getDisplayName(), $reflection->getName(), $parameter->getName());
		}

		return [
			RuleErrorBuilder::message(implode("\n", $messages))->identifier('dummy')->build(),
		];
	}

}
