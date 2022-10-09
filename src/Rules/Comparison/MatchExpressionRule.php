<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MatchExpressionNode;
use PHPStan\Parser\TryCatchTypeVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\SubtractableType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use UnhandledMatchError;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function sprintf;

/**
 * @implements Rule<MatchExpressionNode>
 */
class MatchExpressionRule implements Rule
{

	public function __construct(private bool $checkAlwaysTrueStrictComparison, private bool $treatPhpDocTypesAsCertain)
	{
	}

	public function getNodeType(): string
	{
		return MatchExpressionNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = $this->processMatchErrors($node, $scope, true);
		if ($errors === []) {
			return $errors;
		}
		if (!$this->treatPhpDocTypesAsCertain) {
			return $this->processMatchErrors($node, $scope, false);
		}
		return $errors;
	}

	/**
	 * @return (string|RuleError)[] errors
	 */
	public function processMatchErrors(MatchExpressionNode $node, Scope $scope, bool $treatPhpDocTypesAsCertain): array
	{
		if ($treatPhpDocTypesAsCertain) {
			$scope = $scope->doNotTreatPhpDocTypesAsCertain();
		}
		$matchCondition = $node->getCondition();
		$nextArmIsDead = false;
		$errors = [];
		$armsCount = count($node->getArms());
		$hasDefault = false;
		foreach ($node->getArms() as $i => $arm) {
			if ($nextArmIsDead) {
				$errors[] = RuleErrorBuilder::message('Match arm is unreachable because previous comparison is always true.')->line($arm->getLine())->build();
				continue;
			}
			$armConditions = $arm->getConditions();
			if (count($armConditions) === 0) {
				$hasDefault = true;
			}
			foreach ($armConditions as $armCondition) {
				$armConditionScope = $treatPhpDocTypesAsCertain ? $armCondition->getScope() : $armCondition->getScope()->doNotTreatPhpDocTypesAsCertain();
				$armConditionExpr = new Node\Expr\BinaryOp\Identical(
					$matchCondition,
					$armCondition->getCondition(),
				);
				$armConditionResult = $armConditionScope->getType($armConditionExpr);
				if (!$armConditionResult instanceof ConstantBooleanType) {
					continue;
				}

				$armLine = $armCondition->getLine();
				if (!$armConditionResult->getValue()) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Match arm comparison between %s and %s is always false.',
						$armConditionScope->getType($matchCondition)->describe(VerbosityLevel::value()),
						$armConditionScope->getType($armCondition->getCondition())->describe(VerbosityLevel::value()),
					))->line($armLine)->build();
				} else {
					$nextArmIsDead = true;
					if (
						$this->checkAlwaysTrueStrictComparison
						&& ($i !== $armsCount - 1 || $i === 0)
					) {
						$errors[] = RuleErrorBuilder::message(sprintf(
							'Match arm comparison between %s and %s is always true.',
							$armConditionScope->getType($matchCondition)->describe(VerbosityLevel::value()),
							$armConditionScope->getType($armCondition->getCondition())->describe(VerbosityLevel::value()),
						))->line($armLine)->build();
					}
				}
			}
		}

		if (!$hasDefault && !$nextArmIsDead) {
			$remainingType = $treatPhpDocTypesAsCertain ? $node->getEndScope()->getType($matchCondition) : $node->getEndScope()->doNotTreatPhpDocTypesAsCertain()->getType($matchCondition);
			if ($remainingType instanceof TypeWithClassName && $remainingType instanceof SubtractableType) {
				$subtractedType = $remainingType->getSubtractedType();
				if ($subtractedType !== null && $remainingType->getClassReflection() !== null) {
					$classReflection = $remainingType->getClassReflection();
					if ($classReflection->isEnum()) {
						$cases = [];
						foreach (array_keys($classReflection->getEnumCases()) as $name) {
							$cases[$name] = new EnumCaseObjectType($classReflection->getName(), $name);
						}

						$subtractedTypes = TypeUtils::flattenTypes($subtractedType);
						$set = true;
						foreach ($subtractedTypes as $subType) {
							if (!$subType instanceof EnumCaseObjectType) {
								$set = false;
								break;
							}

							if ($subType->getClassName() !== $classReflection->getName()) {
								$set = false;
								break;
							}

							unset($cases[$subType->getEnumCaseName()]);
						}

						$cases = array_values($cases);
						$casesCount = count($cases);
						if ($set) {
							if ($casesCount > 1) {
								$remainingType = new UnionType($cases);
							}
							if ($casesCount === 1) {
								$remainingType = $cases[0];
							}
						}
					}
				}
			}
			if (
				!$remainingType instanceof NeverType
				&& !$this->isUnhandledMatchErrorCaught($node)
				&& !$this->hasUnhandledMatchErrorThrowsTag($scope)
			) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Match expression does not handle remaining %s: %s',
					$remainingType instanceof UnionType ? 'values' : 'value',
					$remainingType->describe(VerbosityLevel::value()),
				))->build();
			}
		}

		return $errors;
	}

	private function isUnhandledMatchErrorCaught(Node $node): bool
	{
		$tryCatchTypes = $node->getAttribute(TryCatchTypeVisitor::ATTRIBUTE_NAME);
		if ($tryCatchTypes === null) {
			return false;
		}

		$tryCatchType = TypeCombinator::union(...array_map(static fn (string $class) => new ObjectType($class), $tryCatchTypes));

		return $tryCatchType->isSuperTypeOf(new ObjectType(UnhandledMatchError::class))->yes();
	}

	private function hasUnhandledMatchErrorThrowsTag(Scope $scope): bool
	{
		$function = $scope->getFunction();
		if ($function === null) {
			return false;
		}

		$throwsType = $function->getThrowType();
		if ($throwsType === null) {
			return false;
		}

		return $throwsType->isSuperTypeOf(new ObjectType(UnhandledMatchError::class))->yes();
	}

}
