<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\DeclarePositionVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<Stmt\Declare_>
 */
class DeclareStrictPositionRule implements Rule
{

	public function getNodeType(): string
	{
		return Stmt\Declare_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$declaresStrictTypes = false;
		foreach ($node->declares as $declare) {
			if (
				$declare->key->name === 'strict_types'
			) {
				$declaresStrictTypes = true;
				break;
			}
		}

		if ($declaresStrictTypes === false) {
			return [];
		}

		if (!$node->hasAttribute(DeclarePositionVisitor::ATTRIBUTE_NAME)) {
			return [];
		}

		$isFirstStatement = (bool) $node->getAttribute(DeclarePositionVisitor::ATTRIBUTE_NAME);
		if ($isFirstStatement) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Declare strict_types must be the very first statement.',
			))->nonIgnorable()->build(),
		];
	}

}
