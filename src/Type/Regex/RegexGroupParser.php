<?php declare(strict_types = 1);

namespace PHPStan\Type\Regex;

use Hoa\Compiler\Llk\Llk;
use Hoa\Compiler\Llk\Parser;
use Hoa\Compiler\Llk\TreeNode;
use Hoa\Exception\Exception;
use Hoa\File\Read;
use Nette\Utils\RegexpException;
use Nette\Utils\Strings;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryDecimalIntegerStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function array_key_exists;
use function array_values;
use function count;
use function ctype_digit;
use function in_array;
use function is_int;
use function preg_replace;
use function rtrim;
use function sscanf;
use function str_contains;
use function str_replace;
use function strlen;
use function substr;
use function trim;

#[AutowiredService]
final class RegexGroupParser
{

	private const NOT_SUPPORTED_MODIFIERS = [
		'J', // rare modifier too complicated to support
	];

	private static ?Parser $parser = null;

	/** @var array<string, ?TreeNode> */
	private static array $parsedAst = [];

	public function __construct(
		private PhpVersion $phpVersion,
		private RegexExpressionHelper $regexExpressionHelper,
	)
	{
	}

	public function parseGroups(string $regex): ?RegexAstWalkResult
	{
		/** @throws void */
		self::$parser ??= Llk::load(new Read(__DIR__ . '/../../../resources/RegexGrammar.pp'));

		if (array_key_exists($regex, self::$parsedAst)) {
			$ast = self::$parsedAst[$regex];
			if ($ast === null) {
				return null;
			}

			$modifiers = $this->regexExpressionHelper->getPatternModifiers($regex) ?? '';
		} else {
			try {
				Strings::match('', $regex);
			} catch (RegexpException) {
				// pattern is invalid, so let the RegularExpressionPatternRule report it
				return self::$parsedAst[$regex] = null;
			}

			$modifiers = $this->regexExpressionHelper->getPatternModifiers($regex) ?? '';
			foreach (self::NOT_SUPPORTED_MODIFIERS as $notSupportedModifier) {
				if (str_contains($modifiers, $notSupportedModifier)) {
					return self::$parsedAst[$regex] = null;
				}
			}

			if (str_contains($modifiers, 'x')) {
				// in freespacing mode the # character starts a comment and runs until the end of the line
				// but \# is an escaped literal hash, and (?#...) is an inline comment - neither starts a line comment
				$regex = preg_replace('/(?<!\\\\)(?<!\(\?)#.*/', '', $regex) ?? '';
			}

			$rawRegex = $this->regexExpressionHelper->removeDelimitersAndModifiers($regex);
			try {
				$ast = self::$parsedAst[$regex] = self::$parser->parse($rawRegex);
			} catch (Exception) {
				return self::$parsedAst[$regex] = null;
			}
		}

		$this->updateAlternationAstRemoveVerticalBarsAndAddEmptyToken($ast);
		$this->updateCapturingAstAddEmptyToken($ast);

		$captureOnlyNamed = false;
		if ($this->phpVersion->supportsPregCaptureOnlyNamedGroups()) {
			$captureOnlyNamed = str_contains($modifiers, 'n');
		}

		$astWalkResult = $this->walkRegexAst(
			$ast,
			null,
			0,
			false,
			null,
			$captureOnlyNamed,
			false,
			$modifiers,
			RegexAstWalkResult::createEmpty(),
		);

		$subjectAsGroupResult = $this->walkGroupAst(
			$ast,
			false,
			$modifiers,
			RegexGroupWalkResult::createEmpty(),
		);

		if (!$subjectAsGroupResult->mightContainEmptyStringLiteral() && !$this->containsEscapeK($ast)) {
			if (
				$subjectAsGroupResult->isDecimalInteger()->yes()
				&& $this->regexExpressionHelper->isAnchoredPattern($regex)
			) {
				$accessory = $subjectAsGroupResult->isDecimalIntegerLeadingZeroSafe()
					? new AccessoryDecimalIntegerStringType()
					: new AccessoryNumericStringType();
				$astWalkResult = $astWalkResult->withSubjectBaseType(
					new IntersectionType([new StringType(), $accessory]),
				);
			} elseif ($subjectAsGroupResult->isNonFalsy()->yes()) {
				$astWalkResult = $astWalkResult->withSubjectBaseType(
					new IntersectionType([new StringType(), new AccessoryNonFalsyStringType()]),
				);
			} elseif ($subjectAsGroupResult->isNonEmpty()->yes()) {
				$astWalkResult = $astWalkResult->withSubjectBaseType(
					new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]),
				);
			}
		}

		return $astWalkResult;
	}

	private function createEmptyTokenTreeNode(TreeNode $parentAst): TreeNode
	{
		return new TreeNode('token', ['token' => 'literal', 'value' => '', 'namespace' => 'default'], parent: $parentAst);
	}

	private function updateAlternationAstRemoveVerticalBarsAndAddEmptyToken(TreeNode $ast): void
	{
		$children = $ast->getChildren();

		foreach ($children as $i => $child) {
			$this->updateAlternationAstRemoveVerticalBarsAndAddEmptyToken($child);

			if ($ast->getId() !== '#alternation' || $child->getValueToken() !== 'alternation') {
				continue;
			}

			unset($children[$i]);

			if ($i !== 0
				&& isset($children[$i + 1])
				&& $children[$i + 1]->getValueToken() !== 'alternation') {
				continue;
			}

			$children[$i] = $this->createEmptyTokenTreeNode($ast);
		}

		$ast->setChildren(array_values($children));
	}

	private function updateCapturingAstAddEmptyToken(TreeNode $ast): void
	{
		foreach ($ast->getChildren() as $child) {
			$this->updateCapturingAstAddEmptyToken($child);
		}

		if ($ast->getId() !== '#capturing' || $ast->getChildren() !== []) {
			return;
		}

		$emptyAlternationAst = new TreeNode('#alternation', parent: $ast);
		$emptyAlternationAst->setChildren([$this->createEmptyTokenTreeNode($emptyAlternationAst)]);
		$ast->setChildren([$emptyAlternationAst]);
	}

	private function containsEscapeK(TreeNode $ast): bool
	{
		if ($ast->getId() === 'token' && $ast->getValueToken() === 'match_point_reset') {
			return true;
		}

		foreach ($ast->getChildren() as $child) {
			if ($this->containsEscapeK($child)) {
				return true;
			}
		}

		return false;
	}

	private function walkRegexAst(
		TreeNode $ast,
		?RegexAlternation $alternation,
		int $combinationIndex,
		bool $inOptionalQuantification,
		RegexCapturingGroup|RegexNonCapturingGroup|null $parentGroup,
		bool $captureOnlyNamed,
		bool $repeatedMoreThanOnce,
		string $patternModifiers,
		RegexAstWalkResult $astWalkResult,
	): RegexAstWalkResult
	{
		$group = null;
		if ($ast->getId() === '#capturing') {
			$astWalkResult = $astWalkResult->nextCaptureGroupId();

			$group = new RegexCapturingGroup(
				$astWalkResult->getCaptureGroupId(),
				null,
				$alternation,
				$inOptionalQuantification,
				$parentGroup,
				$this->createGroupType(
					$ast,
					$this->allowConstantTypes($patternModifiers, $repeatedMoreThanOnce, $parentGroup),
					$patternModifiers,
				),
			);
			$parentGroup = $group;
		} elseif ($ast->getId() === '#namedcapturing') {
			$astWalkResult = $astWalkResult->nextCaptureGroupId();

			$name = $ast->getChild(0)->getValueValue();
			$group = new RegexCapturingGroup(
				$astWalkResult->getCaptureGroupId(),
				$name,
				$alternation,
				$inOptionalQuantification,
				$parentGroup,
				$this->createGroupType(
					$ast,
					$this->allowConstantTypes($patternModifiers, $repeatedMoreThanOnce, $parentGroup),
					$patternModifiers,
				),
			);
			$parentGroup = $group;
		} elseif ($ast->getId() === '#noncapturing') {
			$group = new RegexNonCapturingGroup(
				$alternation,
				$inOptionalQuantification,
				$parentGroup,
				false,
			);
			$parentGroup = $group;
		} elseif ($ast->getId() === '#noncapturingreset') {
			$group = new RegexNonCapturingGroup(
				$alternation,
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
			$astWalkResult = $astWalkResult->nextAlternationId();
			$alternation = new RegexAlternation($astWalkResult->getAlternationId(), count($ast->getChildren()));
		}

		if ($ast->getId() === '#mark') {
			return $astWalkResult->markVerb($ast->getChild(0)->getValueValue());
		}

		if (
			$group instanceof RegexCapturingGroup &&
			(!$captureOnlyNamed || $group->isNamed())
		) {
			$astWalkResult = $astWalkResult->addCapturingGroup($group);

			if ($alternation !== null) {
				$alternation->pushGroup($combinationIndex, $group);
			}
		}

		foreach ($ast->getChildren() as $child) {
			$astWalkResult = $this->walkRegexAst(
				$child,
				$alternation,
				$combinationIndex,
				$inOptionalQuantification,
				$parentGroup,
				$captureOnlyNamed,
				$repeatedMoreThanOnce,
				$patternModifiers,
				$astWalkResult,
			);

			if ($ast->getId() !== '#alternation') {
				continue;
			}

			$combinationIndex++;
		}

		return $astWalkResult;
	}

	private function allowConstantTypes(
		string $patternModifiers,
		bool $repeatedMoreThanOnce,
		RegexCapturingGroup|RegexNonCapturingGroup|null $parentGroup,
	): bool
	{
		if (str_contains($patternModifiers, 'i')) {
			// if caseless, we don't use constant types
			// because it likely yields too many combinations
			return false;
		}

		if ($repeatedMoreThanOnce) {
			return false;
		}

		if ($parentGroup !== null && $parentGroup->resetsGroupCounter()) {
			return false;
		}

		return true;
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

	private function createGroupType(TreeNode $group, bool $maybeConstant, string $patternModifiers): Type
	{
		$rootAlternation = $this->getRootAlternation($group);
		if ($rootAlternation !== null) {
			$types = [];
			foreach ($rootAlternation->getChildren() as $alternative) {
				$types[] = $this->createGroupType($alternative, $maybeConstant, $patternModifiers);
			}

			return TypeCombinator::union(...$types);
		}

		$walkResult = $this->walkGroupAst(
			$group,
			false,
			$patternModifiers,
			RegexGroupWalkResult::createEmpty(),
		);

		if ($maybeConstant && $walkResult->getOnlyLiterals() !== null && $walkResult->getOnlyLiterals() !== []) {
			$result = [];
			foreach ($walkResult->getOnlyLiterals() as $literal) {
				$result[] = new ConstantStringType($literal);

			}
			return TypeCombinator::union(...$result);
		}

		if ($walkResult->isDecimalInteger()->yes()) {
			// a series of digits beginning with "0" (e.g. "007") or a "-0" is not a canonical
			// decimal integer string, but it is still a numeric string
			$accessory = $walkResult->isDecimalIntegerLeadingZeroSafe()
				? new AccessoryDecimalIntegerStringType()
				: new AccessoryNumericStringType();

			if ($walkResult->isNonFalsy()->yes()) {
				return new IntersectionType([
					new StringType(),
					$accessory,
					new AccessoryNonFalsyStringType(),
				]);
			}

			$result = new IntersectionType([new StringType(), $accessory]);
			if (!$walkResult->isNonEmpty()->yes()) {
				return new UnionType([new ConstantStringType(''), $result]);
			}
			return $result;
		} elseif ($walkResult->isNonFalsy()->yes()) {
			return new IntersectionType([new StringType(), new AccessoryNonFalsyStringType()]);
		} elseif ($walkResult->isNonEmpty()->yes()) {
			return new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]);
		}

		return new StringType();
	}

	private function getRootAlternation(TreeNode $group): ?TreeNode
	{
		if (
			$group->getId() === '#capturing'
			&& count($group->getChildren()) === 1
			&& $group->getChild(0)->getId() === '#alternation'
		) {
			return $group->getChild(0);
		}

		// 1st token within a named capturing group is a token holding the group-name
		if (
			$group->getId() === '#namedcapturing'
			&& count($group->getChildren()) === 2
			&& $group->getChild(1)->getId() === '#alternation'
		) {
			return $group->getChild(1);
		}

		return null;
	}

	private function walkGroupAst(
		TreeNode $ast,
		bool $inClass,
		string $patternModifiers,
		RegexGroupWalkResult $walkResult,
	): RegexGroupWalkResult
	{
		$children = $ast->getChildren();

		if (
			$ast->getId() === '#concatenation'
			&& count($children) > 0
			&& !$walkResult->isInOptionalQuantification()
		) {
			$meaningfulTokens = 0;
			foreach ($children as $child) {
				$nonFalsy = false;
				$isNonDecimal = false;
				if ($this->isMaybeEmptyNode($child, $patternModifiers, $nonFalsy, $isNonDecimal)) {
					continue;
				}

				$meaningfulTokens++;

				if ($isNonDecimal) {
					$walkResult = $walkResult->decimalInteger(TrinaryLogic::createNo());
				}

				if (!$nonFalsy) {
					continue;
				}

				// a single token non-falsy on its own
				$walkResult = $walkResult->nonFalsy(TrinaryLogic::createYes());
				break;
			}

			if ($meaningfulTokens > 0) {
				$walkResult = $walkResult->nonEmpty(TrinaryLogic::createYes());

				// two non-empty tokens concatenated results in a non-falsy string
				if ($meaningfulTokens > 1) {
					$walkResult = $walkResult->nonFalsy(TrinaryLogic::createYes());
				}
			}
		} elseif ($ast->getId() === '#quantification') {
			[$min, $max] = $this->getQuantificationRange($ast);

			if ($min === 0) {
				$walkResult = $walkResult->inOptionalQuantification(true);
			}

			if (!$walkResult->isInOptionalQuantification()) {
				if ($min >= 1) {
					$walkResult = $walkResult->nonEmpty(TrinaryLogic::createYes());
				}
				if ($min >= 2) {
					$walkResult = $walkResult->nonFalsy(TrinaryLogic::createYes());
				}
			}

			// signal the quantified atom whether it may appear more than once
			// (so a leading zero may be followed by more digits) and whether it
			// is optional, which is consumed when the atom is processed.
			$walkResult = $walkResult
				->decimalAtomRepeats($max === null || $max >= 2)
				->decimalAtomOptional($min === 0)
				->onlyLiterals(null);
		} elseif (in_array($ast->getId(), ['#class', '#negativeclass'], true)) {
			$inClass = true;

			[$atomRepeats, $atomOptional, $walkResult] = $this->consumeDecimalAtomQuantification($walkResult);

			[$classAllDigit, $classCanBeZero] = $ast->getId() === '#class'
				? $this->getClassDecimalInfo($ast)
				: [false, false];

			if ($classAllDigit) {
				if ($walkResult->isDecimalInteger()->maybe()) {
					$walkResult = $walkResult->decimalInteger(TrinaryLogic::createYes());
				}
				$walkResult = $this->applyDecimalDigitPosition($walkResult, $classCanBeZero, !$atomOptional, $atomRepeats);
			} else {
				// [^0-9] should not parse as decimal-int-string, and [^list-everything-but-numbers] is
				// technically doable but really silly compared to just \d so we can safely assume the string
				// is not a decimal integer for negative classes (and classes containing non-digits).
				$walkResult = $walkResult->decimalInteger(TrinaryLogic::createNo());
			}

			if ($ast->getId() === '#class' && $walkResult->getOnlyLiterals() !== null) {
				$newLiterals = [];
				foreach ($children as $child) {
					$oldLiterals = $walkResult->getOnlyLiterals();

					$this->getLiteralValue($child, $oldLiterals, true, $patternModifiers, true);
					foreach ($oldLiterals ?? [] as $oldLiteral) {
						$newLiterals[] = $oldLiteral;
					}
				}
				$walkResult = $walkResult->onlyLiterals($newLiterals);
			} else {
				$walkResult = $walkResult->onlyLiterals(null);
			}
		} elseif ($ast->getId() === 'token') {
			$onlyLiterals = $walkResult->getOnlyLiterals();
			$literalValue = $this->getLiteralValue($ast, $onlyLiterals, !$inClass, $patternModifiers, false);
			$walkResult = $walkResult->onlyLiterals($onlyLiterals);

			if ($literalValue !== null) {
				if (!$inClass && $literalValue !== '') {
					[$atomRepeats, $atomOptional, $walkResult] = $this->consumeDecimalAtomQuantification($walkResult);

					if (Strings::match($literalValue, '/^\d+$/') !== null) {
						if ($walkResult->isDecimalInteger()->maybe()) {
							$walkResult = $walkResult->decimalInteger(TrinaryLogic::createYes());
						}
						$walkResult = $this->applyDecimalDigitPosition(
							$walkResult,
							$literalValue[0] === '0',
							!$atomOptional,
							$atomRepeats || strlen($literalValue) > 1,
						);
					} elseif (
						$literalValue === '-'
						&& $walkResult->isDecimalInteger()->maybe()
						&& !$walkResult->hasSeenDecimalIntegerSign()
					) {
						// a single leading minus sign keeps the string a decimal integer (e.g. "-1")
						$walkResult = $walkResult->seenDecimalIntegerSign(true);
					} else {
						$walkResult = $walkResult->decimalInteger(TrinaryLogic::createNo());
					}
				}

				if (!$walkResult->isInOptionalQuantification() && $literalValue !== '') {
					$walkResult = $walkResult->nonEmpty(TrinaryLogic::createYes());
				}
			}
		} elseif (!in_array($ast->getId(), ['#capturing', '#namedcapturing', '#alternation'], true)) {
			$walkResult = $walkResult->onlyLiterals(null);
		}

		if ($ast->getId() === '#alternation') {
			if (count($children) === 0) {
				return $walkResult;
			}

			$newLiterals = [];
			$nonEmpty = TrinaryLogic::createYes();
			$nonFalsy = TrinaryLogic::createYes();
			$decimalInteger = TrinaryLogic::createYes();
			$branchBad = false;
			$branchLeadCanBeZero = false;
			$branchResolved = true;
			$branchSeenDigit = false;
			foreach ($children as $child) {
				$childResult = $this->walkGroupAst(
					$child,
					$inClass,
					$patternModifiers,
					$walkResult->onlyLiterals([])
						->nonEmpty(TrinaryLogic::createMaybe())
						->nonFalsy(TrinaryLogic::createMaybe())
						->decimalInteger(TrinaryLogic::createMaybe())
						->seenDecimalIntegerSign(false)
						->decimalLeadingResolved(false)
						->decimalSeenDigit(false)
						->decimalLeadCanBeZero(false)
						->decimalBad(false),
				);

				$nonEmpty = $nonEmpty->and($childResult->isNonEmpty());
				$nonFalsy = $nonFalsy->and($childResult->isNonFalsy());
				$decimalInteger = $decimalInteger->and($childResult->isDecimalInteger());
				$branchBad = $branchBad || !$childResult->isDecimalIntegerLeadingZeroSafe();
				$branchLeadCanBeZero = $branchLeadCanBeZero || $childResult->isDecimalLeadCanBeZero();
				$branchResolved = $branchResolved && $childResult->isDecimalLeadingResolved();
				$branchSeenDigit = $branchSeenDigit || $childResult->hasDecimalSeenDigit();

				if ($newLiterals === null) {
					continue;
				}

				if (count($childResult->getOnlyLiterals() ?? []) > 0) {
					foreach ($childResult->getOnlyLiterals() as $alternationLiterals) {
						$newLiterals[] = $alternationLiterals;
					}
				} else {
					$newLiterals = null;
				}
			}

			// the alternation is a single conceptual digit position: it is unsafe if any
			// branch is internally unsafe, or if a preceding zero-able lead now gets more digits
			$mergedBad = $walkResult->isDecimalBad()
				|| $branchBad
				|| ($walkResult->hasDecimalSeenDigit() && $walkResult->isDecimalLeadCanBeZero() && $branchSeenDigit);
			$mergedLeadCanBeZero = $walkResult->isDecimalLeadingResolved()
				? $walkResult->isDecimalLeadCanBeZero()
				: ($walkResult->isDecimalLeadCanBeZero() || $branchLeadCanBeZero);

			return $walkResult
				->onlyLiterals($newLiterals)
				->nonEmpty($walkResult->isNonEmpty()->or($nonEmpty))
				->nonFalsy($walkResult->isNonFalsy()->or($nonFalsy))
				->decimalInteger(TrinaryLogic::maxMin($walkResult->isDecimalInteger(), $decimalInteger))
				->decimalLeadingResolved($walkResult->isDecimalLeadingResolved() || $branchResolved)
				->decimalSeenDigit($walkResult->hasDecimalSeenDigit() || $branchSeenDigit)
				->decimalLeadCanBeZero($mergedLeadCanBeZero)
				->decimalBad($mergedBad)
				->decimalAtomRepeats(false)
				->decimalAtomOptional(false);
		}

		foreach ($children as $child) {
			$walkResult = $this->walkGroupAst(
				$child,
				$inClass,
				$patternModifiers,
				$walkResult,
			);
		}

		return $walkResult;
	}

	/**
	 * Reads and clears the transient quantification flags set on the walk result
	 * for the next digit-producing atom.
	 *
	 * @return array{bool, bool, RegexGroupWalkResult} [repeats, optional, walkResult]
	 */
	private function consumeDecimalAtomQuantification(RegexGroupWalkResult $walkResult): array
	{
		return [
			$walkResult->isDecimalAtomRepeats(),
			$walkResult->isDecimalAtomOptional(),
			$walkResult->decimalAtomRepeats(false)->decimalAtomOptional(false),
		];
	}

	/**
	 * Tracks one digit character position to detect whether a leading zero can be
	 * followed by more digits (which would not be a canonical decimal integer).
	 *
	 * @param bool $canBeZero whether this digit can be "0"
	 * @param bool $mandatory whether this digit is always present (not optional)
	 * @param bool $repeats whether this digit may appear more than once in a row
	 */
	private function applyDecimalDigitPosition(RegexGroupWalkResult $walkResult, bool $canBeZero, bool $mandatory, bool $repeats): RegexGroupWalkResult
	{
		$leadingResolved = $walkResult->isDecimalLeadingResolved();
		$leadCanBeZero = $walkResult->isDecimalLeadCanBeZero();
		$bad = $walkResult->isDecimalBad();

		// a digit appears after another digit position: if the lead can be a zero,
		// the value is a leading-zero string like "00"
		if ($walkResult->hasDecimalSeenDigit() && $leadCanBeZero) {
			$bad = true;
		}

		// while the leading digit is not pinned down yet (only optional digits seen
		// so far), this digit may be the leading one
		if (!$leadingResolved && $canBeZero) {
			$leadCanBeZero = true;
		}

		// a single quantified digit repeated produces a leading-zero string like "00"
		if ($repeats && $leadCanBeZero) {
			$bad = true;
		}

		if ($mandatory) {
			$leadingResolved = true;
		}

		return $walkResult
			->decimalSeenDigit(true)
			->decimalLeadingResolved($leadingResolved)
			->decimalLeadCanBeZero($leadCanBeZero)
			->decimalBad($bad);
	}

	/**
	 * @return array{bool, bool} [allDigit, canBeZero]
	 */
	private function getClassDecimalInfo(TreeNode $classNode): array
	{
		$allDigit = true;
		$canBeZero = false;

		foreach ($classNode->getChildren() as $child) {
			if ($child->getId() === '#range') {
				$bounds = $child->getChildren();
				$from = $this->getClassBoundChar($bounds[0] ?? null);
				$to = $this->getClassBoundChar($bounds[1] ?? null);

				if ($from === null || $to === null || $from > $to || ctype_digit($from) === false || ctype_digit($to) === false) {
					$allDigit = false;
				} elseif ($from <= '0' && '0' <= $to) {
					$canBeZero = true;
				}

				continue;
			}

			if ($child->getId() === 'token') {
				$token = $child->getValueToken();
				$value = $child->getValueValue();

				if ($token === 'character_type' && $value === '\d') {
					$canBeZero = true;
					continue;
				}

				if ($token === 'posix_class' && $value === '[:digit:]') {
					$canBeZero = true;
					continue;
				}

				if (
					in_array($token, ['literal', 'range', 'class_', '_class'], true)
					&& strlen($value) === 1
					&& ctype_digit($value)
				) {
					if ($value === '0') {
						$canBeZero = true;
					}
					continue;
				}
			}

			$allDigit = false;
		}

		return [$allDigit, $canBeZero];
	}

	private function getClassBoundChar(?TreeNode $node): ?string
	{
		if ($node === null || $node->getId() !== 'token') {
			return null;
		}

		$value = $node->getValueValue();
		if (strlen($value) > 1 && $value[0] === '\\') {
			$value = substr($value, 1) ?: '';
		}

		return strlen($value) === 1 ? $value : null;
	}

	private function isMaybeEmptyNode(TreeNode $node, string $patternModifiers, bool &$isNonFalsy, bool &$isNonDecimal): bool
	{
		if ($node->getId() === '#quantification') {
			[$min] = $this->getQuantificationRange($node);

			if ($min > 0) {
				return false;
			}

			if ($min === 0) {
				return true;
			}
		}

		$literal = $this->getLiteralValue($node, $onlyLiterals, false, $patternModifiers, false);
		if ($literal !== null) {
			if ($literal !== '' && $literal !== '0') {
				$isNonFalsy = true;
			}
			if (Strings::match($literal, '/^\d+$/') === null) {
				$isNonDecimal = true;
			}
			return $literal === '';
		}

		foreach ($node->getChildren() as $child) {
			if (!$this->isMaybeEmptyNode($child, $patternModifiers, $isNonFalsy, $isNonDecimal)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param array<string>|null $onlyLiterals
	 */
	private function getLiteralValue(TreeNode $node, ?array &$onlyLiterals, bool $appendLiterals, string $patternModifiers, bool $inCharacterClass): ?string
	{
		if ($node->getId() !== 'token') {
			return null;
		}

		// token is the token name from grammar without the namespace so literal and class:literal are both called literal here
		$token = $node->getValueToken();
		$value = $node->getValueValue();

		if (
			in_array($token, [
				'literal',
				// literal "-" in front/back of a character class like '[-a-z]' or '[abc-]', not forming a range
				'range',
				// literal "[" or "]" inside character classes '[[]' or '[]]'
				'class_', '_class',
			], true)
		) {
			if (str_contains($patternModifiers, 'x') && trim($value) === '') {
				return null;
			}

			$isEscaped = false;
			if (strlen($value) > 1 && $value[0] === '\\') {
				$value = substr($value, 1) ?: '';
				$isEscaped = true;
			}

			if (
				$appendLiterals
				&& $onlyLiterals !== null
			) {
				if (
					in_array($value, ['.'], true)
					&& !($isEscaped || $inCharacterClass)
				) {
					$onlyLiterals = null;
				} else {
					if ($onlyLiterals === []) {
						$onlyLiterals = [$value];
					} else {
						foreach ($onlyLiterals as &$literal) {
							$literal .= $value;
						}
					}
				}
			}

			return $value;
		}

		if (!in_array($token, ['capturing_name'], true)) {
			$onlyLiterals = null;
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

		if (in_array($token, ['anchor', 'match_point_reset'], true)) {
			return '';
		}

		return null;
	}

}
