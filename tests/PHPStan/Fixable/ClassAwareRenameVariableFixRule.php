<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function is_string;
use function sprintf;

/**
 * @implements Rule<Variable>
 */
final class ClassAwareRenameVariableFixRule implements Rule
{

	#[Override]
	public function getNodeType(): string
	{
		return Variable::class;
	}

	#[Override]
	public function processNode(Node $node, Scope $scope): array
	{
		if (!is_string($node->name)) {
			return [];
		}
		if (!$scope->isInClass()) {
			return [];
		}

		$shortName = $scope->getClassReflection()->getNativeReflection()->getShortName();
		$newName = $node->name . $shortName;

		return [
			RuleErrorBuilder::message(sprintf('Renaming $%s to $%s.', $node->name, $newName))
				->identifier('tests.batchFixerBench.classAwareRenameVar')
				->fixNode($node, static function (Variable $v) use ($newName): Variable {
					$v->name = $newName;
					return $v;
				})
				->build(),
		];
	}

}
