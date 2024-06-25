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
		$captureGroups = $this->parseGroups($regex);
		if ($captureGroups === null) {
			// regex could not be parsed by Hoa/Regex
			return null;
		}

		$builder = ConstantArrayTypeBuilder::createEmpty();
		$valueType = $this->getValueType($flags ?? 0);

		// first item in matches contains the overall match.
		$builder->setOffsetValueType(
			$this->getKeyType(0),
			TypeCombinator::removeNull($valueType),
			!$wasMatched->yes(),
		);

		$trailingOptionals = 0;
		foreach (array_reverse($captureGroups) as $captureGroup) {
			if (!$captureGroup->isOptional()) {
				break;
			}
			$trailingOptionals++;
		}

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

		// when all groups are optional return a more precise union, instead of a shape with optional offsets
		if ($countGroups === $trailingOptionals && $wasMatched->yes()) {
			$overallType = $builder->getArray();
			return TypeCombinator::union(
				new ConstantArrayType([new ConstantIntegerType(0)], [new StringType()]),
				// same shape, but without optional keys
				new ConstantArrayType($overallType->getKeyTypes(), $overallType->getValueTypes()),
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

		$capturings = [];
		$this->walkRegexAst($ast, 0, 0, $capturings);

		return $capturings;
	}

	/**
	 * @param list<RegexCapturingGroup> $capturings
	 */
	private function walkRegexAst(TreeNode $ast, int $inAlternation, int $inOptionalQuantification, array &$capturings): void
	{
		if ($ast->getId() === '#capturing') {
			$capturings[] = RegexCapturingGroup::unnamed($inAlternation > 0 || $inOptionalQuantification > 0);
		} elseif ($ast->getId() === '#namedcapturing') {
			$name = $ast->getChild(0)->getValue()['value'];
			$capturings[] = RegexCapturingGroup::named(
				$name,
				$inAlternation > 0 || $inOptionalQuantification > 0,
			);
		}

		if ($ast->getId() === '#alternation') {
			$inAlternation++;
		}

		if ($ast->getId() === '#quantification') {
			$lastChild = $ast->getChild($ast->getChildrenNumber() - 1);
			$value = $lastChild->getValue();

			if ($value['token'] === 'n_to_m' && str_contains($value['value'], '{0,')) {
				$inOptionalQuantification++;
			} elseif ($value['token'] === 'zero_or_one') {
				$inOptionalQuantification++;
			} elseif ($value['token'] === 'zero_or_more') {
				$inOptionalQuantification++;
			}
		}

		foreach ($ast->getChildren() as $child) {
			$this->walkRegexAst($child, $inAlternation, $inOptionalQuantification, $capturings);
		}
	}

}
