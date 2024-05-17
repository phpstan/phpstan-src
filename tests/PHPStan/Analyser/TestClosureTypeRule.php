<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\FunctionLike;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<FunctionLike>
 */
class TestClosureTypeRule implements Rule
{

	public function getNodeType(): string
	{
		return FunctionLike::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node instanceof Closure && !$node instanceof Node\Expr\ArrowFunction) {
			return [];
		}

		$type = $scope->getType($node);

		return [
			RuleErrorBuilder::message(sprintf('Closure type: %s', $type->describe(VerbosityLevel::precise())))
				->identifier('tests.closureType')
				->build(),
		];
	}

}
