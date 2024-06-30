<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Hoa\Compiler\Llk\Llk;
use Hoa\Compiler\Llk\Parser;
use Hoa\Compiler\Llk\TreeNode;
use Hoa\Exception\Exception;
use Hoa\File\Read;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
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

		return TypeCombinator::union(...$matchedTypes);
	}

	/**
	 * @param int-mask<PREG_OFFSET_CAPTURE|PREG_UNMATCHED_AS_NULL>|null $flags
	 */
	private function matchRegex(string $regex, ?int $flags, TrinaryLogic $wasMatched): ?Type
	{
		$groupList = $this->parseGroups($regex);
		if ($groupList === null) {
			// regex could not be parsed by Hoa/Regex
			return null;
		}

		$trailingOptionals = 0;
		foreach (array_reverse($groupList) as $captureGroup) {
			if (!$captureGroup->isOptional()) {
				break;
			}
			$trailingOptionals++;
		}

		$valueType = $this->getValueType($flags ?? 0);
		$onlyOptionalTopLevelGroup = $this->getOnlyOptionalTopLevelGroup($groupList);
		if (
			$wasMatched->yes()
			&& $onlyOptionalTopLevelGroup !== null
		) {
			// if only one top level capturing optional group exists
			// we build a more precise constant union of a empty-match and a match with the group

			$onlyOptionalTopLevelGroup->removeOptionalQualification();

			$combiType = $this->buildArrayType(
				$groupList,
				$valueType,
				$wasMatched,
				$trailingOptionals,
			);

			return TypeCombinator::union(
				new ConstantArrayType([new ConstantIntegerType(0)], [new StringType()]),
				$combiType,
			);
		}

		return $this->buildArrayType(
			$groupList,
			$valueType,
			$wasMatched,
			$trailingOptionals,
		);
	}

	/**
	 * @param list<RegexCapturingGroup> $captureGroups
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
	 * @param list<RegexCapturingGroup> $captureGroups
	 */
	private function buildArrayType(
		array $captureGroups,
		Type $valueType,
		TrinaryLogic $wasMatched,
		int $trailingOptionals,
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
		for ($i = 0; $i < $countGroups; $i++) {
			$captureGroup = $captureGroups[$i];

			if (!$wasMatched->yes()) {
				$optional = true;
			} else {
				if ($i < $countGroups - $trailingOptionals) {
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
		if (($flags & PREG_UNMATCHED_AS_NULL) !== 0) {
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
	 * @return list<RegexCapturingGroup>|null
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
		$this->walkRegexAst(
			$ast,
			false,
			false,
			null,
			$capturingGroups,
		);

		return $capturingGroups;
	}

	/**
	 * @param list<RegexCapturingGroup> $capturingGroups
	 */
	private function walkRegexAst(
		TreeNode $ast,
		bool $inAlternation,
		bool $inOptionalQuantification,
		?RegexCapturingGroup $inCapturing,
		array &$capturingGroups,
	): void
	{
		$group = null;
		if ($ast->getId() === '#capturing') {
			$group = RegexCapturingGroup::unnamed(
				$inAlternation,
				$inOptionalQuantification,
				$inCapturing,
			);
			$inCapturing = $group;
		} elseif ($ast->getId() === '#namedcapturing') {
			$name = $ast->getChild(0)->getValue()['value'];
			$group = RegexCapturingGroup::named(
				$name,
				$inAlternation,
				$inOptionalQuantification,
				$inCapturing,
			);
			$inCapturing = $group;
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
			$inAlternation = true;
		}

		if ($group !== null) {
			$capturingGroups[] = $group;
		}

		foreach ($ast->getChildren() as $child) {
			$this->walkRegexAst(
				$child,
				$inAlternation,
				$inOptionalQuantification,
				$inCapturing,
				$capturingGroups,
			);
		}
	}

}
