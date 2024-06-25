<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Hoa\Compiler\Llk\Llk;
use Hoa\Compiler\Llk\Parser;
use Hoa\Compiler\Llk\TreeNode;
use Hoa\Exception\Exception;
use Hoa\File\Read;
use Nette\Utils\RegexpException;
use Nette\Utils\Strings;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_key_last;
use function array_keys;
use function count;
use function in_array;
use function is_int;
use function is_string;
use function str_contains;
use const PHP_VERSION_ID;
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

		if (PHP_VERSION_ID < 70400) {
			// see https://www.php.net/manual/en/migration74.incompatible.php#migration74.incompatible.pcre
			// When PREG_UNMATCHED_AS_NULL mode is used, trailing unmatched capturing groups will now also be set to null (or [null, -1] if offset capture is enabled).
			// This means that the size of the $matches will always be the same.
			return null;
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
		// add one capturing group to the end so all capture group keys
		// are present in the $matches
		// see https://3v4l.org/sOXbn, https://3v4l.org/3SdDM
		$captureGroupsRegex = Strings::replace($regex, '~.[a-z\s]*$~i', '|(?<phpstanNamedCaptureGroupLast>)$0');

		try {
			$matches = Strings::match('', $captureGroupsRegex, PREG_UNMATCHED_AS_NULL);
			if ($matches === null) {
				return null;
			}
		} catch (RegexpException) {
			return null;
		}

		unset($matches[array_key_last($matches)]);
		unset($matches['phpstanNamedCaptureGroupLast']);

		$remainingNonOptionalGroupCount = $this->countNonOptionalGroups($regex);
		if ($remainingNonOptionalGroupCount === null) {
			// regex could not be parsed by Hoa/Regex
			return null;
		}

		$builder = ConstantArrayTypeBuilder::createEmpty();
		$valueType = $this->getValueType($flags ?? 0);

		foreach (array_keys($matches) as $key) {
			if ($key === 0) {
				// first item in matches contains the overall match.
				$builder->setOffsetValueType(
					$this->getKeyType($key),
					TypeCombinator::removeNull($valueType),
					!$wasMatched->yes(),
				);

				continue;
			}

			if (!$wasMatched->yes()) {
				$optional = true;
			} else {
				$optional = $remainingNonOptionalGroupCount <= 0;

				if (is_int($key)) {
					$remainingNonOptionalGroupCount--;
				}
			}

			$builder->setOffsetValueType(
				$this->getKeyType($key),
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

	private function countNonOptionalGroups(string $regex): ?int
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

		return $this->walkRegexAst($ast, 0, 0);
	}

	private function walkRegexAst(TreeNode $ast, int $inAlternation, int $inOptionalQuantification): int
	{
		$count = 0;
		if (
			in_array($ast->getId(), ['#capturing', '#namedcapturing'], true)
			&& !($inAlternation > 0 || $inOptionalQuantification > 0)
		) {
			$count++;
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
			$count += $this->walkRegexAst($child, $inAlternation, $inOptionalQuantification);
		}

		return $count;
	}

}
