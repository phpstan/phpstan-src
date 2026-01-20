<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function mt_rand;

/**
 * @implements Rule<Node\Expr\Isset_>
 */
final class Bug13983Rule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Expr\Isset_::class;
	}

	/**
	 * @param Node\Expr\Isset_ $node
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		$type = $scope->getType($node->vars[0]);
		$error = RuleErrorBuilder::message('Dumped: ' . $type->describe(VerbosityLevel::precise()))
			->identifier('dump.isset')
			->build();
		$this->analyzeThis();
		return [$error];
	}

	public function analyzeThis(): int
	{
		$a = mt_rand(0, 1) === 0 ? 1 : null;
		if (isset($a)) {
			return 1;
		}

		return 2;
	}

}
