<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<InClassNode>
 */
final class ReadOnlyClassRule implements Rule
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

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
			if ($this->phpVersion->supportsReadOnlyAnonymousClasses()) {
				return [];
			}

			return [
				RuleErrorBuilder::message('Anonymous readonly classes are supported only on PHP 8.3 and later.')
					->identifier('classConstant.nativeTypeNotSupported')
					->nonIgnorable()
					->build(),
			];
		}

		if ($this->phpVersion->supportsReadOnlyClasses()) {
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
