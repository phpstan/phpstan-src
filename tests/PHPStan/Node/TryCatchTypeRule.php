<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use function array_map;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Echo_>
 */
class TryCatchTypeRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\Echo_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$tryCatchTypes = $node->getAttribute('tryCatchTypes');
		$type = null;
		if ($tryCatchTypes !== null) {
			$type = TypeCombinator::union(...array_map(static fn (string $name) => new ObjectType($name), $tryCatchTypes));
		}
		return [
			RuleErrorBuilder::message(sprintf(
				'Try catch type: %s',
				$type !== null ? $type->describe(VerbosityLevel::precise()) : 'nothing',
			))->identifier('tests.tryCatchType')->build(),
		];
	}

}
