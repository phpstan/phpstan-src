<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\MixedType;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\ArrayDimFetch>
 */
class InvalidKeyInArrayDimFetchRule implements \PHPStan\Rules\Rule
{

	/** @var RuleLevelHelper */
	private $ruleLevelHelper;

	/** @var bool */
	private $reportMaybes;

	public function __construct(RuleLevelHelper $ruleLevelHelper, bool $reportMaybes)
	{
		$this->ruleLevelHelper = $ruleLevelHelper;
		$this->reportMaybes = $reportMaybes;
	}

	public function getNodeType(): string
	{
		return \PhpParser\Node\Expr\ArrayDimFetch::class;
	}

	public function processNode(\PhpParser\Node $node, Scope $scope): array
	{
		if ($node->dim === null) {
			return [];
		}

		$varType = $scope->getType($node->var);
		if (count(TypeUtils::getArrays($varType)) === 0) {
			return [];
		}

		$dimensionType = $scope->getType($node->dim);
		$isSuperType = AllowedArrayKeysTypes::getType()->isSuperTypeOf($dimensionType);
		if ($isSuperType->no()) {
			return [
				RuleErrorBuilder::message(
					sprintf('Invalid array key type %s.', $dimensionType->describe(VerbosityLevel::typeOnly()))
				)->build(),
			];
		} elseif (
			$this->reportMaybes
			&& $isSuperType->maybe()
			&& (!$dimensionType instanceof MixedType || $this->ruleLevelHelper->shouldCheckMixed($dimensionType))
		) {
			return [
				RuleErrorBuilder::message(
					sprintf('Possibly invalid array key type %s.', $dimensionType->describe(VerbosityLevel::typeOnly()))
				)->build(),
			];
		}

		return [];
	}

}
