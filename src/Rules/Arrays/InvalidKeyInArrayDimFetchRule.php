<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Expr\ArrayDimFetch>
 */
class InvalidKeyInArrayDimFetchRule implements Rule
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
		private bool $reportMaybes,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\ArrayDimFetch::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->dim === null) {
			return [];
		}

		$dimensionType = $scope->getType($node->dim);
		$varType = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$node->var,
			'',
			static fn (Type $varType): bool => $varType->isArray()->no() || AllowedArrayKeysTypes::getType()->isSuperTypeOf($dimensionType)->yes(),
		)->getType();

		if ($varType instanceof ErrorType || $varType->isArray()->no()) {
			return [];
		}

		$isSuperType = AllowedArrayKeysTypes::getType()->isSuperTypeOf($dimensionType);
		if ($isSuperType->no()) {
			return [
				RuleErrorBuilder::message(
					sprintf('Invalid array key type %s.', $dimensionType->describe(VerbosityLevel::typeOnly())),
				)->build(),
			];
		} elseif ($this->reportMaybes && $isSuperType->maybe() && !$dimensionType instanceof MixedType) {
			return [
				RuleErrorBuilder::message(
					sprintf('Possibly invalid array key type %s.', $dimensionType->describe(VerbosityLevel::typeOnly())),
				)->build(),
			];
		}

		return [];
	}

}
