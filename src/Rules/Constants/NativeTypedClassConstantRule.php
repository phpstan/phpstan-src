<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Stmt\ClassConst>
 */
final class NativeTypedClassConstantRule implements Rule
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\ClassConst::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->type === null) {
			return [];
		}

		if ($this->phpVersion->supportsNativeTypesInClassConstants()) {
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
