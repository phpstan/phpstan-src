<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Stmt\ClassConst>
 */
final class ClassAsClassConstantRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\ClassConst::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];

		foreach ($node->consts as $const) {
			if ($const->name->toLowerString() !== 'class') {
				continue;
			}

			$errors[] = RuleErrorBuilder::message('A class constant must not be called \'class\'; it is reserved for class name fetching.')
				->line($const->getStartLine())
				->identifier('classConstant.class')
				->nonIgnorable()
				->build();
		}

		return $errors;
	}

}
