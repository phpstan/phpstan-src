<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<InClassNode>
 */
#[RegisteredRule(level: 0)]
final class ReadOnlyClassRule implements Rule
{

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();
		if (!$classReflection->isReadOnly()) {
			return [];
		}
		if ($classReflection->isAnonymous()) {
			if ($scope->getPhpVersion()->supportsReadOnlyAnonymousClasses()->yes()) {
				return [];
			}

			return [
				RuleErrorBuilder::message('Anonymous readonly classes are supported only on PHP 8.3 and later.')
					->identifier('classConstant.nativeTypeNotSupported')
					->nonIgnorable()
					->build(),
			];
		}

		if ($scope->getPhpVersion()->supportsReadOnlyClasses()->yes()) {
			return [];
		}

		return [
			RuleErrorBuilder::message('Readonly classes are supported only on PHP 8.2 and later.')
				->identifier('classConstant.nativeTypeNotSupported')
				->nonIgnorable()
				->build(),
		];
	}

}
