<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Hoa\Compiler\Llk\Llk;
use Hoa\Compiler\Llk\Parser;
use Hoa\Compiler\Llk\TreeNode;
use Hoa\Exception\Exception;
use Hoa\File\Read;
use PHPStan\Php\PhpVersion;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_key_exists;
use function array_reverse;
use function count;
use function in_array;
use function is_string;
use function str_contains;
use const PREG_OFFSET_CAPTURE;
use const PREG_UNMATCHED_AS_NULL;

/**
 * @api
 */
final class RegexArrayShapeMatcher
{

	private static ?Parser $parser = null;

	public function __construct(
		private PhpVersion $phpVersion,
	)
	{
	}

	public function matchType(Type $patternType, ?Type $flagsType, TrinaryLogic $wasMatched): ?Type
	{
		if ($wasMatched->no()) {
			return new ConstantArrayType([], []);
		}

		$constantStrings = $patternType->getConstantStrings();
		if (count($constantStrings) === 0) {
			return null;
		}

		$flags = null;
		if ($flagsType !== null) {
			if (
				!$flagsType instanceof ConstantIntegerType
				|| !in_array($flagsType->getValue(), [PREG_OFFSET_CAPTURE, PREG_UNMATCHED_AS_NULL, PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL], true)
			) {
				return null;
			}

			$flags = $flagsType->getValue();
		}

		$matchedTypes = [];
		foreach ($constantStrings as $constantString) {
			$matched = $this->matchRegex($constantString->getValue(), $flags, $wasMatched);
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
	 * @param int-mask<PREG_OFFSET_CAPTURE|PREG_UNMATCHED_AS_NULL>|null $flags
	 */
	private function matchRegex(string $regex, ?int $flags, TrinaryLogic $wasMatched): ?Type
	{
		$parseResult = $this->parseGroups($regex);
		if ($parseResult === null) {
			// regex could not be parsed by Hoa/Regex
			return null;
		}
		[$groupList, $groupCombinations] = $parseResult;

		$trailingOptionals = 0;
		foreach (array_reverse($groupList) as $captureGroup) {
			if (!$captureGroup->isOptional()) {
				break;
			}
			$trailingOptionals++;
		}

		$valueType = $this->getValueType($flags ?? 0);
		$onlyOptionalTopLevelGroup = $this->getOnlyOptionalTopLevelGroup($groupList);
		$onlyTopLevelAlternationId = $this->getOnlyTopLevelAlternationId($groupList);

		if (
			$wasMatched->yes()
			&& $onlyOptionalTopLevelGroup !== null
		) {
			// if only one top level capturing optional group exists
			// we build a more precise constant union of a empty-match and a match with the group

			$onlyOptionalTopLevelGroup->forceNonOptional();

			$combiType = $this->buildArrayType(
				$groupList,
				$valueType,
				$wasMatched,
				$trailingOptionals,
				$flags ?? 0,
			);

			return TypeCombinator::union(
				new ConstantArrayType([new ConstantIntegerType(0)], [new StringType()], [0], [], true),
				$combiType,
			);
		} elseif (
			$wasMatched->yes()
			&& $onlyTopLevelAlternationId !== null
			&& array_key_exists($onlyTopLevelAlternationId, $groupCombinations)
		) {
			$combiTypes = [];
			$isOptionalAlternation = false;
			foreach ($groupCombinations[$onlyTopLevelAlternationId] as $groupCombo) {
				$comboList = $groupList;

				$beforeCurrentCombo = true;
				foreach ($comboList as $groupId => $group) {
					if (in_array($groupId, $groupCombo, true)) {
						$isOptionalAlternation = $group->inOptionalAlternation();
						$group->forceNonOptional();
						$beforeCurrentCombo = false;
					} elseif ($beforeCurrentCombo && !$group->resetsGroupCounter()) {
						$group->forceNonOptional();
					} elseif ($group->getAlternationId() === $onlyTopLevelAlternationId) {
						unset($comboList[$groupId]);
					}
				}

				$combiType = $this->buildArrayType(
					$comboList,
					$valueType,
					$wasMatched,
					$trailingOptionals,
					$flags ?? 0,
				);

				$combiTypes[] = $combiType;

				foreach ($groupCombo as $groupId) {
					$group = $comboList[$groupId];
					$group->restoreNonOptional();
				}
			}

			if ($isOptionalAlternation) {
				$combiTypes[] = new ConstantArrayType([new ConstantIntegerType(0)], [new StringType()], [0], [], true);
			}

			return TypeCombinator::union(...$combiTypes);
		}

		return $this->buildArrayType(
			$groupList,
			$valueType,
			$wasMatched,
			$trailingOptionals,
			$flags ?? 0,
		);
	}

	/**
	 * @param array<int, RegexCapturingGroup> $captureGroups
	 */
	private function getOnlyOptionalTopLevelGroup(array $captureGroups): ?RegexCapturingGroup
	{
		$group = null;
		foreach ($captureGroups as $captureGroup) {
			if (!$captureGroup->isTopLevel()) {
				continue;
			}

			if (!$captureGroup->isOptional()) {
				return null;
			}

			if ($group !== null) {
				return null;
			}

			$group = $captureGroup;
		}

		return $group;
	}

	/**
	 * @param array<int, RegexCapturingGroup> $captureGroups
	 */
	private function getOnlyTopLevelAlternationId(array $captureGroups): ?int
	{
		$alternationId = null;
		foreach ($captureGroups as $captureGroup) {
			if (!$captureGroup->isTopLevel()) {
				continue;
			}

			if (!$captureGroup->inAlternation()) {
				return null;
			}

			if ($alternationId === null) {
				$alternationId = $captureGroup->getAlternationId();
			} elseif ($alternationId !== $captureGroup->getAlternationId()) {
				return null;
			}
		}

		return $alternationId;
	}

	/**
	 * @param array<RegexCapturingGroup> $captureGroups
	 */
	private function buildArrayType(
		array $captureGroups,
		Type $valueType,
		TrinaryLogic $wasMatched,
		int $trailingOptionals,
		int $flags,
	): Type
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();

		// first item in matches contains the overall match.
		$builder->setOffsetValueType(
			$this->getKeyType(0),
			TypeCombinator::removeNull($valueType),
			!$wasMatched->yes(),
		);

		$countGroups = count($captureGroups);
		$i = 0;
		foreach ($captureGroups as $captureGroup) {
			if (!$wasMatched->yes()) {
				$optional = true;
			} else {
				if ($i < $countGroups - $trailingOptionals) {
					$optional = false;
				} elseif (($flags & PREG_UNMATCHED_AS_NULL) !== 0 && $this->phpVersion->supportsPregUnmatchedAsNull()) {
					$optional = false;
				} else {
					$optional = $captureGroup->isOptional();
				}
			}

			if ($captureGroup->isNamed()) {
				$builder->setOffsetValueType(
					$this->getKeyType($captureGroup->getName()),
					$valueType,
					$optional,
				);
			}

			$builder->setOffsetValueType(
				$this->getKeyType($i + 1),
				$valueType,
				$optional,
			);

			$i++;
		}

		return $builder->getArray();
	}

	private function getKeyType(int|string $key): Type
	{
		if (is_string($key)) {
			return new ConstantStringType($key);
		}

		return new ConstantIntegerType($key);
	}

	private function getValueType(int $flags): Type
	{
		$valueType = new StringType();
		$offsetType = IntegerRangeType::fromInterval(0, null);
		if (($flags & PREG_UNMATCHED_AS_NULL) !== 0 && $this->phpVersion->supportsPregUnmatchedAsNull()) {
			$valueType = TypeCombinator::addNull($valueType);
			// unmatched groups return -1 as offset
			$offsetType = IntegerRangeType::fromInterval(-1, null);
		}

		if (($flags & PREG_OFFSET_CAPTURE) !== 0) {
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

	/**
	 * @return array{array<int, RegexCapturingGroup>, array<int, array<int, int[]>>}|null
	 */
	private function parseGroups(string $regex): ?array
	{
		if (self::$parser === null) {
			/** @throws void */
			self::$parser = Llk::load(new Read('hoa://Library/Regex/Grammar.pp'));
		}

		try {
			$ast = self::$parser->parse($regex);
		} catch (Exception) {
			return null;
		}

		$capturingGroups = [];
		$groupCombinations = [];
		$alternationId = -1;
		$captureGroupId = 100;
		$this->walkRegexAst(
			$ast,
			false,
			$alternationId,
			0,
			false,
			null,
			$captureGroupId,
			$capturingGroups,
			$groupCombinations,
		);

		return [$capturingGroups, $groupCombinations];
	}

	/**
	 * @param array<int, RegexCapturingGroup> $capturingGroups
	 * @param array<int, array<int, int[]>> $groupCombinations
	 */
	private function walkRegexAst(
		TreeNode $ast,
		bool $inAlternation,
		int &$alternationId,
		int $combinationIndex,
		bool $inOptionalQuantification,
		RegexCapturingGroup|RegexNonCapturingGroup|null $parentGroup,
		int &$captureGroupId,
		array &$capturingGroups,
		array &$groupCombinations,
	): void
	{
		$group = null;
		if ($ast->getId() === '#capturing') {
			$group = new RegexCapturingGroup(
				$captureGroupId++,
				null,
				$inAlternation ? $alternationId : null,
				$inOptionalQuantification,
				$parentGroup,
			);
			$parentGroup = $group;
		} elseif ($ast->getId() === '#namedcapturing') {
			$name = $ast->getChild(0)->getValue()['value'];
			$group = new RegexCapturingGroup(
				$captureGroupId++,
				$name,
				$inAlternation ? $alternationId : null,
				$inOptionalQuantification,
				$parentGroup,
			);
			$parentGroup = $group;
		} elseif ($ast->getId() === '#noncapturing') {
			$group = new RegexNonCapturingGroup(
				$inAlternation ? $alternationId : null,
				$inOptionalQuantification,
				$parentGroup,
				false,
			);
			$parentGroup = $group;
		} elseif ($ast->getId() === '#noncapturingreset') {
			$group = new RegexNonCapturingGroup(
				$inAlternation ? $alternationId : null,
				$inOptionalQuantification,
				$parentGroup,
				true,
			);
			$parentGroup = $group;
		}

		$inOptionalQuantification = false;
		if ($ast->getId() === '#quantification') {
			$lastChild = $ast->getChild($ast->getChildrenNumber() - 1);
			$value = $lastChild->getValue();

			if ($value['token'] === 'n_to_m' && str_contains($value['value'], '{0,')) {
				$inOptionalQuantification = true;
			} elseif ($value['token'] === 'zero_or_one') {
				$inOptionalQuantification = true;
			} elseif ($value['token'] === 'zero_or_more') {
				$inOptionalQuantification = true;
			}
		}

		if ($ast->getId() === '#alternation') {
			$alternationId++;
			$inAlternation = true;
		}

		if ($group instanceof RegexCapturingGroup) {
			$capturingGroups[$group->getId()] = $group;

			if (!array_key_exists($alternationId, $groupCombinations)) {
				$groupCombinations[$alternationId] = [];
			}
			if (!array_key_exists($combinationIndex, $groupCombinations[$alternationId])) {
				$groupCombinations[$alternationId][$combinationIndex] = [];
			}
			$groupCombinations[$alternationId][$combinationIndex][] = $group->getId();
		}

		foreach ($ast->getChildren() as $child) {
			$this->walkRegexAst(
				$child,
				$inAlternation,
				$alternationId,
				$combinationIndex,
				$inOptionalQuantification,
				$parentGroup,
				$captureGroupId,
				$capturingGroups,
				$groupCombinations,
			);

			if ($ast->getId() !== '#alternation') {
				continue;
			}

			$combinationIndex++;
		}
	}

}
