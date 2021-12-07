<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Expr\ArrayDimFetch>
 */
class NonexistentOffsetInArrayDimFetchRule implements Rule
{

	private RuleLevelHelper $ruleLevelHelper;

	private NonexistentOffsetInArrayDimFetchCheck $nonexistentOffsetInArrayDimFetchCheck;

	private bool $reportMaybes;

	public function __construct(
		RuleLevelHelper $ruleLevelHelper,
		NonexistentOffsetInArrayDimFetchCheck $nonexistentOffsetInArrayDimFetchCheck,
		bool $reportMaybes
	)
	{
		$this->ruleLevelHelper = $ruleLevelHelper;
		$this->nonexistentOffsetInArrayDimFetchCheck = $nonexistentOffsetInArrayDimFetchCheck;
		$this->reportMaybes = $reportMaybes;
	}

	public function getNodeType(): string
	{
		return Node\Expr\ArrayDimFetch::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->dim !== null) {
			$dimType = $scope->getType($node->dim);
			$unknownClassPattern = sprintf('Access to offset %s on an unknown class %%s.', SprintfHelper::escapeFormatString($dimType->describe(VerbosityLevel::value())));
		} else {
			$dimType = null;
			$unknownClassPattern = 'Access to an offset on an unknown class %s.';
		}

		$isOffsetAccessibleTypeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $node->var),
			$unknownClassPattern,
			static function (Type $type): bool {
				return $type->isOffsetAccessible()->yes();
			}
		);
		$isOffsetAccessibleType = $isOffsetAccessibleTypeResult->getType();
		if ($isOffsetAccessibleType instanceof ErrorType) {
			return $isOffsetAccessibleTypeResult->getUnknownClassErrors();
		}

		$isOffsetAccessible = $isOffsetAccessibleType->isOffsetAccessible();

		if ($scope->isInExpressionAssign($node) && $isOffsetAccessible->yes()) {
			return [];
		}

		if (!$isOffsetAccessible->yes()) {
			if ($isOffsetAccessible->no() || $this->reportMaybes) {
				if ($dimType !== null) {
					return [
						RuleErrorBuilder::message(sprintf(
							'Cannot access offset %s on %s.',
							$dimType->describe(VerbosityLevel::value()),
							$isOffsetAccessibleType->describe(VerbosityLevel::value())
						))->build(),
					];
				}

				return [
					RuleErrorBuilder::message(sprintf(
						'Cannot access an offset on %s.',
						$isOffsetAccessibleType->describe(VerbosityLevel::typeOnly())
					))->build(),
				];
			}

			return [];
		}

		if ($dimType === null) {
			return [];
		}

		return $this->nonexistentOffsetInArrayDimFetchCheck->check(
			$scope,
			$node->var,
			$unknownClassPattern,
			$dimType
		);
	}

}
