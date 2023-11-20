<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassConst;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<ClassConst> */
class FinalConstantRule implements Rule
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getNodeType(): string
	{
		return ClassConst::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->isFinal()) {
			return [];
		}

		if ($this->phpVersion->supportsFinalConstants()) {
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
