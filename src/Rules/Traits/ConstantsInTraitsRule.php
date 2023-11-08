<?php declare(strict_types = 1);

namespace PHPStan\Rules\Traits;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use function array_map;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\ClassConst>
 */
class ConstantsInTraitsRule implements Rule
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

		return array_map(
			static fn (Node\Const_ $const): RuleError => RuleErrorBuilder::message(sprintf(
				'Constant %s::%s is declared inside a trait but is only supported on PHP 8.2 and later.',
				$scope->getTraitReflection()->getDisplayName(),
				$const->name->toString(),
			))->identifier('classConstant.inTrait')->nonIgnorable()->build(),
			$node->consts,
		);
	}

}
