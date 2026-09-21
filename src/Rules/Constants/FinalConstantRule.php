<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassConst;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<ClassConst> */
#[RegisteredRule(level: 0)]
final class FinalConstantRule implements Rule
{

	public function getNodeType(): string
	{
		return ClassConst::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->isFinal()) {
			return [];
		}

		if ($scope->getPhpVersion()->supportsFinalConstants()->yes()) {
			return [];
		}

		return [
			RuleErrorBuilder::message('Final class constants are supported only on PHP 8.1 and later.')
				->identifier('classConstant.finalNotSupported')
				->nonIgnorable()
				->build(),
		];
	}

}
