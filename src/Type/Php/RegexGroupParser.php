<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Hoa\Compiler\Llk\Llk;
use Hoa\Compiler\Llk\Parser;
use Hoa\Compiler\Llk\TreeNode;
use Hoa\Exception\Exception;
use Hoa\File\Read;
use Nette\Utils\RegexpException;
use Nette\Utils\Strings;
use PHPStan\Php\PhpVersion;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_key_exists;
use function count;
use function in_array;
use function is_int;
use function rtrim;
use function sscanf;
use function str_contains;
use function str_replace;
use function strlen;
use function substr;

final class RegexGroupParser
{

	private static ?Parser $parser = null;

	public function __construct(
		private PhpVersion $phpVersion,
		private RegexExpressionHelper $regexExpressionHelper,
	)
	{
	}

	/**
	 * @return array{array<int, RegexCapturingGroup>, array<int, array<int, int[]>>, list<string>}|null
	 */
	public function parseGroups(string $regex): ?array
	{
		if (self::$parser === null) {
			/** @throws void */
			self::$parser = Llk::load(new Read(__DIR__ . '/../../../resources/RegexGrammar.pp'));
		}

		try {
			Strings::match('', $regex);
		} catch (RegexpException) {
			// pattern is invalid, so let the RegularExpressionPatternRule report it
			return null;
		}

		$rawRegex = $this->regexExpressionHelper->removeDelimitersAndModifiers($regex);
		try {
			$ast = self::$parser->parse($rawRegex);
		} catch (Exception) {
			return null;
		}

		$captureOnlyNamed = false;
		if ($this->phpVersion->supportsPregCaptureOnlyNamedGroups()) {
			$modifiers = $this->regexExpressionHelper->getPatternModifiers($regex);
			$captureOnlyNamed = str_contains($modifiers ?? '', 'n');
		}

		$capturingGroups = [];
		$groupCombinations = [];
		$alternationId = -1;
		$captureGroupId = 100;
		$markVerbs = [];
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
			$markVerbs,
			$captureOnlyNamed,
			false,
		);

		return [$capturingGroups, $groupCombinations, $markVerbs];
	}

	/**
	 * @param array<int, RegexCapturingGroup> $capturingGroups
	 * @param array<int, array<int, int[]>> $groupCombinations
	 * @param list<string> $markVerbs
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
		array &$markVerbs,
		bool $captureOnlyNamed,
		bool $repeatedMoreThanOnce,
	): void
	{
		$group = null;
		if ($ast->getId() === '#capturing') {
			$maybeConstant = !$repeatedMoreThanOnce;
			if ($parentGroup !== null && $parentGroup->resetsGroupCounter()) {
				$maybeConstant = false;
			}

			$group = new RegexCapturingGroup(
				$captureGroupId++,
				null,
				$inAlternation ? $alternationId : null,
				$inOptionalQuantification,
				$parentGroup,
				$this->createGroupType($ast, $maybeConstant),
			);
			$parentGroup = $group;
		} elseif ($ast->getId() === '#namedcapturing') {
			$maybeConstant = !$repeatedMoreThanOnce;
			if ($parentGroup !== null && $parentGroup->resetsGroupCounter()) {
				$maybeConstant = false;
			}

			$name = $ast->getChild(0)->getValueValue();
			$group = new RegexCapturingGroup(
				$captureGroupId++,
				$name,
				$inAlternation ? $alternationId : null,
				$inOptionalQuantification,
				$parentGroup,
				$this->createGroupType($ast, $maybeConstant),
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
			[$min, $max] = $this->getQuantificationRange($ast);

			if ($min === 0) {
				$inOptionalQuantification = true;
			}

			if ($max === null || $max > 1) {
				$repeatedMoreThanOnce = true;
			}
		}

		if ($ast->getId() === '#alternation') {
			$alternationId++;
			$inAlternation = true;
		}

		if ($ast->getId() === '#mark') {
			$markVerbs[] = $ast->getChild(0)->getValueValue();
			return;
		}

		if (
			$group instanceof RegexCapturingGroup &&
			(!$captureOnlyNamed || $group->isNamed())
		) {
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
				$markVerbs,
				$captureOnlyNamed,
				$repeatedMoreThanOnce,
			);

			if ($ast->getId() !== '#alternation') {
				continue;
			}

			$combinationIndex++;
		}
	}

	/** @return array{?int, ?int} */
	private function getQuantificationRange(TreeNode $node): array
	{
		if ($node->getId() !== '#quantification') {
			throw new ShouldNotHappenException();
		}

		$min = null;
		$max = null;

		$lastChild = $node->getChild($node->getChildrenNumber() - 1);
		$value = $lastChild->getValue();

		// normalize away possessive and lazy quantifier-modifiers
		$token = str_replace(['_possessive', '_lazy'], '', $value['token']);
		$value = rtrim($value['value'], '+?');

		if ($token === 'n_to_m') {
			if (sscanf($value, '{%d,%d}', $n, $m) !== 2 || !is_int($n) || !is_int($m)) {
				throw new ShouldNotHappenException();
			}

			$min = $n;
			$max = $m;
		} elseif ($token === 'n_or_more') {
			if (sscanf($value, '{%d,}', $n) !== 1 || !is_int($n)) {
				throw new ShouldNotHappenException();
			}

			$min = $n;
		} elseif ($token === 'exactly_n') {
			if (sscanf($value, '{%d}', $n) !== 1 || !is_int($n)) {
				throw new ShouldNotHappenException();
			}

			$min = $n;
			$max = $n;
		} elseif ($token === 'zero_or_one') {
			$min = 0;
			$max = 1;
		} elseif ($token === 'zero_or_more') {
			$min = 0;
		} elseif ($token === 'one_or_more') {
			$min = 1;
		}

		return [$min, $max];
	}

	private function createGroupType(TreeNode $group, bool $maybeConstant): Type
	{
		$isNonEmpty = TrinaryLogic::createMaybe();
		$isNumeric = TrinaryLogic::createMaybe();
		$inOptionalQuantification = false;
		$onlyLiterals = [];

		$this->walkGroupAst(
			$group,
			$isNonEmpty,
			$isNumeric,
			$inOptionalQuantification,
			$onlyLiterals,
			false,
		);

		if ($maybeConstant && $onlyLiterals !== null && $onlyLiterals !== []) {
			$result = [];
			foreach ($onlyLiterals as $literal) {
				$result[] = new ConstantStringType($literal);

			}
			return TypeCombinator::union(...$result);
		}

		if ($isNumeric->yes()) {
			$result = new IntersectionType([new StringType(), new AccessoryNumericStringType()]);
			if (!$isNonEmpty->yes()) {
				return TypeCombinator::union(new ConstantStringType(''), $result);
			}
			return $result;
		} elseif ($isNonEmpty->yes()) {
			return new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]);
		}

		return new StringType();
	}

	/**
	 * @param array<string>|null $onlyLiterals
	 */
	private function walkGroupAst(
		TreeNode $ast,
		TrinaryLogic &$isNonEmpty,
		TrinaryLogic &$isNumeric,
		bool &$inOptionalQuantification,
		?array &$onlyLiterals,
		bool $inClass,
	): void
	{
		$children = $ast->getChildren();

		if (
			$ast->getId() === '#concatenation'
			&& count($children) > 0
		) {
			$isNonEmpty = TrinaryLogic::createYes();
		} elseif ($ast->getId() === '#quantification') {
			[$min] = $this->getQuantificationRange($ast);

			if ($min === 0) {
				$inOptionalQuantification = true;
			}
			if ($min >= 1) {
				$isNonEmpty = TrinaryLogic::createYes();
				$inOptionalQuantification = false;
			}

			$onlyLiterals = null;
		} elseif ($ast->getId() === '#class' && $onlyLiterals !== null) {
			$inClass = true;

			$newLiterals = [];
			foreach ($children as $child) {
				$oldLiterals = $onlyLiterals;

				if ($child->getId() === 'token') {
					$this->getLiteralValue($child, $oldLiterals, true);
				}

				foreach ($oldLiterals ?? [] as $oldLiteral) {
					$newLiterals[] = $oldLiteral;
				}
			}
			$onlyLiterals = $newLiterals;
		} elseif ($ast->getId() === 'token') {
			$literalValue = $this->getLiteralValue($ast, $onlyLiterals, !$inClass);
			if ($literalValue !== null) {
				if (Strings::match($literalValue, '/^\d+$/') === null) {
					$isNumeric = TrinaryLogic::createNo();
				} elseif ($isNumeric->maybe()) {
					$isNumeric = TrinaryLogic::createYes();
				}

				if (!$inOptionalQuantification) {
					$isNonEmpty = TrinaryLogic::createYes();
				}
			} elseif (!in_array($ast->getValueToken(), ['capturing_name'], true)) {
				$onlyLiterals = null;
			}
		} elseif (!in_array($ast->getId(), ['#capturing', '#namedcapturing'], true)) {
			$onlyLiterals = null;
		}

		// [^0-9] should not parse as numeric-string, and [^list-everything-but-numbers] is technically
		// doable but really silly compared to just \d so we can safely assume the string is not numeric
		// for negative classes
		if ($ast->getId() === '#negativeclass') {
			$isNumeric = TrinaryLogic::createNo();
		}

		foreach ($children as $child) {
			$this->walkGroupAst(
				$child,
				$isNonEmpty,
				$isNumeric,
				$inOptionalQuantification,
				$onlyLiterals,
				$inClass,
			);
		}
	}

	/**
	 * @param array<string>|null $onlyLiterals
	 */
	private function getLiteralValue(TreeNode $node, ?array &$onlyLiterals, bool $appendLiterals): ?string
	{
		if ($node->getId() !== 'token') {
			return null;
		}

		// token is the token name from grammar without the namespace so literal and class:literal are both called literal here
		$token = $node->getValueToken();
		$value = $node->getValueValue();

		if (in_array($token, ['literal', 'escaped_end_class'], true)) {
			if (strlen($value) > 1 && $value[0] === '\\') {
				return substr($value, 1);
			} elseif (
				$appendLiterals
				&& $token === 'literal'
				&& $onlyLiterals !== null
				&& !in_array($value, ['.'], true)
			) {
				if ($onlyLiterals === []) {
					$onlyLiterals = [$value];
				} else {
					foreach ($onlyLiterals as &$literal) {
						$literal .= $value;
					}
				}
			}

			return $value;
		}

		// literal "-" in front/back of a character class like '[-a-z]' or '[abc-]', not forming a range
		if ($token === 'range') {
			return $value;
		}

		// literal "[" or "]" inside character classes '[[]' or '[]]'
		if (in_array($token, ['class_', '_class_literal'], true)) {
			return $value;
		}

		// character escape sequences, just return a fixed string
		if (in_array($token, ['character', 'dynamic_character', 'character_type'], true)) {
			if ($token === 'character_type' && $value === '\d') {
				return '0';
			}

			return $value;
		}

		// [:digit:] and the like, more support coming later
		if ($token === 'posix_class') {
			if ($value === '[:digit:]') {
				return '0';
			}
			if (in_array($value, ['[:alpha:]', '[:alnum:]', '[:upper:]', '[:lower:]', '[:word:]', '[:ascii:]', '[:print:]', '[:xdigit:]', '[:graph:]'], true)) {
				return 'a';
			}
			if ($value === '[:blank:]') {
				return " \t";
			}
			if ($value === '[:cntrl:]') {
				return "\x00\x1F";
			}
			if ($value === '[:space:]') {
				return " \t\r\n\v\f";
			}
			if ($value === '[:punct:]') {
				return '!"#$%&\'()*+,\-./:;<=>?@[\]^_`{|}~';
			}
		}

		if ($token === 'anchor' || $token === 'match_point_reset') {
			return '';
		}

		return null;
	}

}
