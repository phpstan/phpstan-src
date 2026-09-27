<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\VarTagUsagesNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * A `@var` tag over `$x = null`, `$x = []`, a scalar, a `new` of a generic
 * class or `static $x` declares what the variable holds in the rest of the
 * function. A write that makes the variable hold something the tag does not
 * accept - `$x[] = <element of another type>`, `$x .= ...`, `$x++` - and a
 * template argument the body infers differently are reported.
 *
 * @implements Rule<VarTagUsagesNode>
 */
final class VarTagReflectsUsagesRule implements Rule
{

	public function __construct(private RuleLevelHelper $ruleLevelHelper)
	{
	}

	public function getNodeType(): string
	{
		return VarTagUsagesNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$varTagType = $node->getVarTagType();
		$rejected = $this->getRejectedTypes($varTagType, $node->getAssignedType());
		// a tag more precise than the generalized inferred template arguments
		// (Collection<true>) is fine when it accepts the precise ones
		if ($rejected === [] || $this->getRejectedTypes($varTagType, $node->getPreciseAssignedType()) === []) {
			return [];
		}
		$usagesType = TypeCombinator::union(...$rejected);

		$verbosity = VerbosityLevel::getRecommendedLevelByType($varTagType, $usagesType);

		return [
			RuleErrorBuilder::message(sprintf(
				'PHPDoc tag @var with type %s does not accept type %s assigned to $%s.',
				$varTagType->describe($verbosity),
				$usagesType->describe($verbosity),
				$node->getVariableName(),
			))->identifier('varTag.usages')->build(),
		];
	}

	/**
	 * @return list<Type>
	 */
	private function getRejectedTypes(Type $varTagType, Type $assignedType): array
	{
		$rejected = [];
		foreach (TypeUtils::flattenTypes($assignedType) as $type) {
			if ($this->ruleLevelHelper->isSuperTypeOf($varTagType, $type)->result) {
				continue;
			}
			$rejected[] = $type;
		}

		return $rejected;
	}

}
