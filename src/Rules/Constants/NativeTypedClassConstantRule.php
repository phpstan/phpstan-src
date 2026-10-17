<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * @implements Rule<Node\Stmt\ClassConst>
 */
#[RegisteredRule(level: 0)]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/NativeTypedClassConstantRule.cpp')]
final class NativeTypedClassConstantRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\ClassConst::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->type === null) {
			return [];
		}

		if ($scope->getPhpVersion()->supportsNativeTypesInClassConstants()->yes()) {
			return [];
		}

		return [
			RuleErrorBuilder::message('Class constants with native types are supported only on PHP 8.3 and later.')
				->identifier('classConstant.nativeTypeNotSupported')
				->nonIgnorable()
				->build(),
		];
	}

}
