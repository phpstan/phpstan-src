<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\Regex\RegexCapturingGroup;
use PHPStan\Type\Regex\RegexExpressionHelper;
use PHPStan\Type\Regex\RegexGroupList;
use PHPStan\Type\Regex\RegexGroupParser;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;
use function is_string;
use const PREG_OFFSET_CAPTURE;
use const PREG_PATTERN_ORDER;
use const PREG_SET_ORDER;
use const PREG_UNMATCHED_AS_NULL;

/**
 * @api
 */
#[AutowiredService]
final class RegexArrayShapeMatcher
{

	/**
	 * Pass this into $flagsType as well if the library supports emulating PREG_UNMATCHED_AS_NULL on PHP 7.2 and 7.3
	 */
	public const PREG_UNMATCHED_AS_NULL_ON_72_73 = 2048;

	public function __construct(
		private RegexGroupParser $regexGroupParser,
		private RegexExpressionHelper $regexExpressionHelper,
		private PhpVersion $phpVersion,
	)
	{
	}

	public function matchAllExpr(Expr $patternExpr, ?Type $flagsType, TrinaryLogic $wasMatched, Scope $scope): ?Type
	{
		return $this->matchPatternType($this->getPatternType($patternExpr, $scope), $flagsType, $wasMatched, true);
	}

	public function matchExpr(Expr $patternExpr, ?Type $flagsType, TrinaryLogic $wasMatched, Scope $scope): ?Type
	{
		return $this->matchPatternType($this->getPatternType($patternExpr, $scope), $flagsType, $wasMatched, false);
	}

	private function matchPatternType(Type $patternType, ?Type $flagsType, TrinaryLogic $wasMatched, bool $matchesAll): ?Type
	{
		if ($wasMatched->no()) {
			return ConstantArrayTypeBuilder::createEmpty()->getArray();
		}

		$constantStrings = $patternType->getConstantStrings();
		if (count($constantStrings) === 0) {
			return null;
		}

		$flags = null;
		if ($flagsType !== null) {
			if (!$flagsType instanceof ConstantIntegerType) {
				return null;
			}

			/** @var int-mask<PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER | PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL | self::PREG_UNMATCHED_AS_NULL_ON_72_73> $flags */
			$flags = $flagsType->getValue() & (PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER | PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL | self::PREG_UNMATCHED_AS_NULL_ON_72_73);

			// some other unsupported/unexpected flag was passed in
			if ($flags !== $flagsType->getValue()) {
				return null;
			}
		}

		$matchedTypes = [];
		foreach ($constantStrings as $constantString) {
			$matched = $this->matchRegex($constantString->getValue(), $flags, $wasMatched, $matchesAll);
			if ($matched === null) {
				return null;
			}

			$matchedTypes[] = $matched;
		}

		if (count($matchedTypes) === 1) {
			return $matchedTypes[0];
		}

		return TypeCombinator::union(...$matchedTypes);
	}

	/**
	 * @param int-mask<PREG_OFFSET_CAPTURE|PREG_PATTERN_ORDER|PREG_SET_ORDER|PREG_UNMATCHED_AS_NULL|self::PREG_UNMATCHED_AS_NULL_ON_72_73>|null $flags
	 */
	private function matchRegex(string $regex, ?int $flags, TrinaryLogic $wasMatched, bool $matchesAll): ?Type
	{
		$astWalkResult = $this->regexGroupParser->parseGroups($regex);
		if ($astWalkResult === null) {
			// regex could not be parsed by Hoa/Regex
			return null;
		}
		$groupList = $astWalkResult->getCapturingGroups();
		$markVerbs = $astWalkResult->getMarkVerbs();
		$subjectBaseType = new StringType();
		if ($wasMatched->yes()) {
			$subjectBaseType = $astWalkResult->getSubjectBaseType();
		}

		$regexGroupList = new RegexGroupList($groupList);
		$trailingOptionals = $regexGroupList->countTrailingOptionals();
		$onlyOptionalTopLevelGroup = $regexGroupList->getOnlyOptionalTopLevelGroup();
		$onlyTopLevelAlternation = $regexGroupList->getOnlyTopLevelAlternation();
		$flags ??= 0;

		if (
			!$matchesAll
			&& $wasMatched->yes()
			&& $onlyOptionalTopLevelGroup !== null
		) {
			// if only one top level capturing optional group exists
			// we build a more precise tagged union of a empty-match and a match with the group
			$regexGroupList = $regexGroupList->forceGroupNonOptional($onlyOptionalTopLevelGroup);

			$combiType = $this->buildArrayType(
				$subjectBaseType,
				$regexGroupList,
				$wasMatched,
				$trailingOptionals,
				$flags,
				$markVerbs,
				$matchesAll,
			);

			if (!$this->containsUnmatchedAsNull($flags, $matchesAll)) {
				// positive match has a subject but not any capturing group
				$builder = ConstantArrayTypeBuilder::createEmpty();
				$builder->setOffsetValueType(new ConstantIntegerType(0), $this->createSubjectValueType($subjectBaseType, $flags, $matchesAll));

				$combiType = TypeCombinator::union(
					$builder->getArray(),
					$combiType,
				);
			}

			return $combiType;
		} elseif (
			!$matchesAll
			&& $onlyOptionalTopLevelGroup === null
			&& $onlyTopLevelAlternation !== null
			&& !$wasMatched->no()
		) {
			// if only a single top level alternation exist built a more precise tagged union

			$combiTypes = [];
			$isOptionalAlternation = false;
			foreach ($onlyTopLevelAlternation->getGroupCombinations() as $groupCombo) {
				$comboList = new RegexGroupList($groupList);

				$beforeCurrentCombo = true;
				foreach ($comboList as $group) {
					if (in_array($group->getId(), $groupCombo, true)) {
						$isOptionalAlternation = $group->inOptionalAlternation();
						$comboList = $comboList->forceGroupNonOptional($group);
						$beforeCurrentCombo = false;
					} elseif ($beforeCurrentCombo && !$group->resetsGroupCounter()) {
						$comboList = $comboList->forceGroupTypeAndNonOptional(
							$group,
							$this->containsUnmatchedAsNull($flags, $matchesAll) ? new NullType() : new ConstantStringType(''),
						);
					} elseif (
						$group->getAlternationId() === $onlyTopLevelAlternation->getId()
						&& !$this->containsUnmatchedAsNull($flags, $matchesAll)
					) {
						$comboList = $comboList->removeGroup($group);
					}
				}

				$combiType = $this->buildArrayType(
					$subjectBaseType,
					$comboList,
					$wasMatched,
					$trailingOptionals,
					$flags,
					$markVerbs,
					$matchesAll,
				);

				$combiTypes[] = $combiType;
			}

			if (
				!$this->containsUnmatchedAsNull($flags, $matchesAll)
				&& (
					$onlyTopLevelAlternation->getAlternationsCount() !== count($onlyTopLevelAlternation->getGroupCombinations())
					|| $isOptionalAlternation
				)
			) {
				// positive match has a subject but not any capturing group
				$builder = ConstantArrayTypeBuilder::createEmpty();
				$builder->setOffsetValueType(new ConstantIntegerType(0), $this->createSubjectValueType($subjectBaseType, $flags, $matchesAll));

				$combiTypes[] = $builder->getArray();
			}

			return TypeCombinator::union(...$combiTypes);
		}

		// the general case, which should work in all cases but does not yield the most
		// precise result possible in some cases
		return $this->buildArrayType(
			$subjectBaseType,
			$regexGroupList,
			$wasMatched,
			$trailingOptionals,
			$flags,
			$markVerbs,
			$matchesAll,
		);
	}

	/**
	 * @param list<string> $markVerbs
	 */
	private function buildArrayType(
		Type $subjectBaseType,
		RegexGroupList $captureGroups,
		TrinaryLogic $wasMatched,
		int $trailingOptionals,
		int $flags,
		array $markVerbs,
		bool $matchesAll,
	): Type
	{
		$forceList = count($markVerbs) === 0;
		$builder = ConstantArrayTypeBuilder::createEmpty();

		// first item in matches contains the overall match.
		$builder->setOffsetValueType(
			$this->getKeyType(0),
			$this->createSubjectValueType($subjectBaseType, $flags, $matchesAll),
			$this->isSubjectOptional($wasMatched, $matchesAll),
		);

		$countGroups = count($captureGroups);
		$i = 0;
		foreach ($captureGroups as $captureGroup) {
			$isTrailingOptional = $i >= $countGroups - $trailingOptionals;
			$isLastGroup = $i === $countGroups - 1;
			$groupValueType = $this->createGroupValueType($captureGroup, $wasMatched, $flags, $isTrailingOptional, $isLastGroup, $matchesAll);
			$optional = $this->isGroupOptional($captureGroup, $wasMatched, $flags, $isTrailingOptional, $matchesAll);

			if ($captureGroup->isNamed()) {
				$forceList = false;

				$builder->setOffsetValueType(
					$this->getKeyType($captureGroup->getName()),
					$groupValueType,
					$optional,
				);
			}

			$builder->setOffsetValueType(
				$this->getKeyType($i + 1),
				$groupValueType,
				$optional,
			);

			$i++;
		}

		if (count($markVerbs) > 0) {
			$markTypes = [];
			foreach ($markVerbs as $mark) {
				$markTypes[] = new ConstantStringType($mark);
			}
			$builder->setOffsetValueType(
				$this->getKeyType('MARK'),
				TypeCombinator::union(...$markTypes),
				true,
			);
		}

		if ($matchesAll && $this->containsSetOrder($flags)) {
			$arrayType = TypeCombinator::intersect(new ArrayType(new IntegerType(), $builder->getArray()), new AccessoryArrayListType());
			if (!$wasMatched->yes()) {
				$arrayType = TypeCombinator::union(
					ConstantArrayTypeBuilder::createEmpty()->getArray(),
					$arrayType,
				);
			}
			return $arrayType;
		}

		if ($forceList) {
			return TypeCombinator::intersect($builder->getArray(), new AccessoryArrayListType());
		}

		return $builder->getArray();
	}

	private function isSubjectOptional(TrinaryLogic $wasMatched, bool $matchesAll): bool
	{
		if ($matchesAll) {
			return false;
		}

		return !$wasMatched->yes();
	}

	/**
	 * @param Type $baseType A string type (or string variant) representing the subject of the match
	 */
	private function createSubjectValueType(Type $baseType, int $flags, bool $matchesAll): Type
	{
		$subjectValueType = TypeCombinator::removeNull($this->getValueType($baseType, $flags, $matchesAll));

		if ($matchesAll) {
			$subjectValueType = TypeCombinator::removeNull($this->getValueType(new StringType(), $flags, $matchesAll));

			if ($this->containsPatternOrder($flags)) {
				$subjectValueType = TypeCombinator::intersect(
					new ArrayType(new IntegerType(), $subjectValueType),
					new AccessoryArrayListType(),
				);
			}
		}

		return $subjectValueType;
	}

	private function isGroupOptional(RegexCapturingGroup $captureGroup, TrinaryLogic $wasMatched, int $flags, bool $isTrailingOptional, bool $matchesAll): bool
	{
		if ($matchesAll) {
			if ($isTrailingOptional && !$this->containsUnmatchedAsNull($flags, $matchesAll) && $this->containsSetOrder($flags)) {
				return true;
			}

			return false;
		}

		if (!$wasMatched->yes()) {
			$optional = true;
		} else {
			if (!$isTrailingOptional) {
				$optional = false;
			} elseif ($this->containsUnmatchedAsNull($flags, $matchesAll)) {
				$optional = false;
			} else {
				$optional = $captureGroup->isOptional();
			}
		}

		return $optional;
	}

	private function createGroupValueType(RegexCapturingGroup $captureGroup, TrinaryLogic $wasMatched, int $flags, bool $isTrailingOptional, bool $isLastGroup, bool $matchesAll): Type
	{
		if ($matchesAll) {
			if (
				(
					!$this->containsSetOrder($flags)
					&& !$this->containsUnmatchedAsNull($flags, $matchesAll)
					&& $captureGroup->isOptional()
				)
				||
				(
					$this->containsSetOrder($flags)
					&& !$this->containsUnmatchedAsNull($flags, $matchesAll)
					&& $captureGroup->isOptional()
					&& !$isTrailingOptional
				)
			) {
				$groupValueType = $this->getValueType(
					TypeCombinator::union($captureGroup->getType(), new ConstantStringType('')),
					$flags,
					$matchesAll,
				);
				$groupValueType = TypeCombinator::removeNull($groupValueType);
			} else {
				$groupValueType = $this->getValueType($captureGroup->getType(), $flags, $matchesAll);
			}

			if (!$isTrailingOptional && $this->containsUnmatchedAsNull($flags, $matchesAll) && !$captureGroup->isOptional()) {
				$groupValueType = TypeCombinator::removeNull($groupValueType);
			}

			if ($this->containsPatternOrder($flags)) {
				$groupValueType = TypeCombinator::intersect(new ArrayType(new IntegerType(), $groupValueType), new AccessoryArrayListType());
			}

			return $groupValueType;
		}

		if (!$isLastGroup && !$this->containsUnmatchedAsNull($flags, $matchesAll) && $captureGroup->isOptional()) {
			$groupValueType = $this->getValueType(
				TypeCombinator::union($captureGroup->getType(), new ConstantStringType('')),
				$flags,
				$matchesAll,
			);
		} else {
			$groupValueType = $this->getValueType($captureGroup->getType(), $flags, $matchesAll);
		}

		if ($wasMatched->yes()) {
			if (!$isTrailingOptional && $this->containsUnmatchedAsNull($flags, $matchesAll) && !$captureGroup->isOptional()) {
				$groupValueType = TypeCombinator::removeNull($groupValueType);
			}
		}

		return $groupValueType;
	}

	private function containsOffsetCapture(int $flags): bool
	{
		return ($flags & PREG_OFFSET_CAPTURE) !== 0;
	}

	private function containsPatternOrder(int $flags): bool
	{
		// If no order flag is given, PREG_PATTERN_ORDER is assumed.
		return !$this->containsSetOrder($flags);
	}

	private function containsSetOrder(int $flags): bool
	{
		return ($flags & PREG_SET_ORDER) !== 0;
	}

	private function containsUnmatchedAsNull(int $flags, bool $matchesAll): bool
	{
		if ($matchesAll) {
			// preg_match_all() with PREG_UNMATCHED_AS_NULL works consistently across php-versions
			// https://3v4l.org/tKmPn
			return ($flags & PREG_UNMATCHED_AS_NULL) !== 0;
		}

		return ($flags & PREG_UNMATCHED_AS_NULL) !== 0 && (($flags & self::PREG_UNMATCHED_AS_NULL_ON_72_73) !== 0 || $this->phpVersion->supportsPregUnmatchedAsNull());
	}

	private function getKeyType(int|string $key): Type
	{
		if (is_string($key)) {
			return new ConstantStringType($key);
		}

		return new ConstantIntegerType($key);
	}

	private function getValueType(Type $baseType, int $flags, bool $matchesAll): Type
	{
		$valueType = $baseType;

		// unmatched groups return -1 as offset
		$offsetType = IntegerRangeType::fromInterval(-1, null);
		if ($this->containsUnmatchedAsNull($flags, $matchesAll)) {
			$valueType = TypeCombinator::addNull($valueType);
		}

		if ($this->containsOffsetCapture($flags)) {
			$builder = ConstantArrayTypeBuilder::createEmpty();

			$builder->setOffsetValueType(
				new ConstantIntegerType(0),
				$valueType,
			);
			$builder->setOffsetValueType(
				new ConstantIntegerType(1),
				$offsetType,
			);

			return $builder->getArray();
		}

		return $valueType;
	}

	private function getPatternType(Expr $patternExpr, Scope $scope): Type
	{
		if ($patternExpr instanceof Expr\BinaryOp\Concat) {
			return $this->regexExpressionHelper->resolvePatternConcat($patternExpr, $scope);
		}

		return $scope->getType($patternExpr);
	}

}
