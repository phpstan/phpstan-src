<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\ClassConst>
 */
final class ValueAssignedToClassConstantRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\ClassConst::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			throw new ShouldNotHappenException();
		}

		$nativeType = null;
		if ($node->type !== null) {
			$nativeType = ParserNodeTypeToPHPStanType::resolve($node->type, $scope->getClassReflection());
		}

		$errors = [];
		foreach ($node->consts as $const) {
			$constantName = $const->name->toString();
			$errors = array_merge($errors, $this->processSingleConstant(
				$scope->getClassReflection(),
				$constantName,
				$scope->getType($const->value),
				$nativeType,
			));
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function processSingleConstant(ClassReflection $classReflection, string $constantName, Type $valueExprType, ?Type $nativeType): array
	{
		$constantReflection = $classReflection->getConstant($constantName);
		$phpDocType = $constantReflection->getPhpDocType();
		if ($phpDocType === null) {
			if ($nativeType === null) {
				return [];
			}

			$accepts = $nativeType->acceptsWithReason($valueExprType, true);
			if ($accepts->yes()) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf(
					'Constant %s::%s (%s) does not accept value %s.',
					$constantReflection->getDeclaringClass()->getDisplayName(),
					$constantName,
					$nativeType->describe(VerbosityLevel::typeOnly()),
					$valueExprType->describe(VerbosityLevel::value()),
				))->acceptsReasonsTip($accepts->reasons)->nonIgnorable()->identifier('classConstant.value')->build(),
			];
		} elseif ($nativeType === null) {
			$isSuperType = $phpDocType->isSuperTypeOf($valueExprType);
			$verbosity = VerbosityLevel::getRecommendedLevelByType($phpDocType, $valueExprType);
			if ($isSuperType->no()) {
				return [
					RuleErrorBuilder::message(sprintf(
						'PHPDoc tag @var for constant %s::%s with type %s is incompatible with value %s.',
						$constantReflection->getDeclaringClass()->getDisplayName(),
						$constantName,
						$phpDocType->describe($verbosity),
						$valueExprType->describe(VerbosityLevel::value()),
					))->identifier('classConstant.phpDocType')->build(),
				];

			} elseif ($isSuperType->maybe()) {
				return [
					RuleErrorBuilder::message(sprintf(
						'PHPDoc tag @var for constant %s::%s with type %s is not subtype of value %s.',
						$constantReflection->getDeclaringClass()->getDisplayName(),
						$constantName,
						$phpDocType->describe($verbosity),
						$valueExprType->describe(VerbosityLevel::value()),
					))->identifier('classConstant.phpDocType')->build(),
				];
			}

			return [];
		}

		$type = $constantReflection->getValueType();
		$accepts = $type->acceptsWithReason($valueExprType, true);
		if ($accepts->yes()) {
			return [];
		}

		$verbosity = VerbosityLevel::getRecommendedLevelByType($type, $valueExprType);

		return [
			RuleErrorBuilder::message(sprintf(
				'Constant %s::%s (%s) does not accept value %s.',
				$constantReflection->getDeclaringClass()->getDisplayName(),
				$constantName,
				$type->describe(VerbosityLevel::typeOnly()),
				$valueExprType->describe($verbosity),
			))->acceptsReasonsTip($accepts->reasons)->identifier('classConstant.value')->build(),
		];
	}

}
