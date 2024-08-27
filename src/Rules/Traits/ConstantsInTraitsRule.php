<?php declare(strict_types = 1);

namespace PHPStan\Rules\Traits;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Stmt\ClassConst>
 */
final class ConstantsInTraitsRule implements Rule
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\ClassConst::class;
	}

	/**
	 * @param Node\Stmt\ClassConst $node
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		if ($this->phpVersion->supportsConstantsInTraits()) {
			return [];
		}

		if (!$scope->isInTrait()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(
				'Constant is declared inside a trait but is only supported on PHP 8.2 and later.',
			)->identifier('classConstant.inTrait')->nonIgnorable()->build(),
		];
	}

}
