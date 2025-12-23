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
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_key_exists;
use function array_values;
use function count;
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
				$regex = preg_replace('/(?<!\?)#.*/', '', $regex) ?? '';
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
			false,
			$modifiers,
			RegexGroupWalkResult::createEmpty(),
		);

		if (!$subjectAsGroupResult->mightContainEmptyStringLiteral() && !$this->containsEscapeK($ast)) {
			// we could handle numeric-string, in case we know the regex is delimited by ^ and $
			if ($subjectAsGroupResult->isNonFalsy()->yes()) {
				$astWalkResult = $astWalkResult->withSubjectBaseType(
					TypeCombinator::intersect(new StringType(), new AccessoryNonFalsyStringType()),
				);
			} elseif ($subjectAsGroupResult->isNonEmpty()->yes()) {
				$astWalkResult = $astWalkResult->withSubjectBaseType(
					TypeCombinator::intersect(new StringType(), new AccessoryNonEmptyStringType()),
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

		if ($walkResult->isNumeric()->yes()) {
			if ($walkResult->isNonFalsy()->yes()) {
				return new IntersectionType([
					new StringType(),
					new AccessoryNumericStringType(),
					new AccessoryNonFalsyStringType(),
				]);
			}

			$result = new IntersectionType([new StringType(), new AccessoryNumericStringType()]);
			if (!$walkResult->isNonEmpty()->yes()) {
				return TypeCombinator::union(new ConstantStringType(''), $result);
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
		bool $inAlternation,
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
				if ($this->isMaybeEmptyNode($child, $patternModifiers, $nonFalsy)) {
					continue;
				}

				$meaningfulTokens++;

				if (!$nonFalsy || $inAlternation) {
					continue;
				}

				// a single token non-falsy on its own
				$walkResult = $walkResult->nonFalsy(TrinaryLogic::createYes());
				break;
			}

			if ($meaningfulTokens > 0) {
				$walkResult = $walkResult->nonEmpty(TrinaryLogic::createYes());

				// two non-empty tokens concatenated results in a non-falsy string
				if ($meaningfulTokens > 1 && !$inAlternation) {
					$walkResult = $walkResult->nonFalsy(TrinaryLogic::createYes());
				}
			}
		} elseif ($ast->getId() === '#quantification') {
			[$min] = $this->getQuantificationRange($ast);

			if ($min === 0) {
				$walkResult = $walkResult->inOptionalQuantification(true);
			}

			if (!$walkResult->isInOptionalQuantification()) {
				if ($min >= 1) {
					$walkResult = $walkResult->nonEmpty(TrinaryLogic::createYes());
				}
				if ($min >= 2 && !$inAlternation) {
					$walkResult = $walkResult->nonFalsy(TrinaryLogic::createYes());
				}
			}

			$walkResult = $walkResult->onlyLiterals(null);
		} elseif ($ast->getId() === '#class' && $walkResult->getOnlyLiterals() !== null) {
			$inClass = true;

			$newLiterals = [];
			foreach ($children as $child) {
				$oldLiterals = $walkResult->getOnlyLiterals();

				$this->getLiteralValue($child, $oldLiterals, true, $patternModifiers, true);
				foreach ($oldLiterals ?? [] as $oldLiteral) {
					$newLiterals[] = $oldLiteral;
				}
			}
			$walkResult = $walkResult->onlyLiterals($newLiterals);
		} elseif ($ast->getId() === 'token') {
			$onlyLiterals = $walkResult->getOnlyLiterals();
			$literalValue = $this->getLiteralValue($ast, $onlyLiterals, !$inClass, $patternModifiers, false);
			$walkResult = $walkResult->onlyLiterals($onlyLiterals);

			if ($literalValue !== null) {
				if (Strings::match($literalValue, '/^\d+$/') === null) {
					$walkResult = $walkResult->numeric(TrinaryLogic::createNo());
				} elseif ($walkResult->isNumeric()->maybe()) {
					$walkResult = $walkResult->numeric(TrinaryLogic::createYes());
				}

				if (!$walkResult->isInOptionalQuantification() && $literalValue !== '') {
					$walkResult = $walkResult->nonEmpty(TrinaryLogic::createYes());
				}
			}
		} elseif (!in_array($ast->getId(), ['#capturing', '#namedcapturing', '#alternation'], true)) {
			$walkResult = $walkResult->onlyLiterals(null);
		}

		if ($ast->getId() === '#alternation') {
			$newLiterals = [];
			foreach ($children as $child) {
				$walkResult = $this->walkGroupAst(
					$child,
					true,
					$inClass,
					$patternModifiers,
					$walkResult->onlyLiterals([]),
				);

				if ($newLiterals === null) {
					continue;
				}

				if (count($walkResult->getOnlyLiterals() ?? []) > 0) {
					foreach ($walkResult->getOnlyLiterals() as $alternationLiterals) {
						$newLiterals[] = $alternationLiterals;
					}
				} else {
					$newLiterals = null;
				}
			}

			return $walkResult->onlyLiterals($newLiterals);
		}

		// [^0-9] should not parse as numeric-string, and [^list-everything-but-numbers] is technically
		// doable but really silly compared to just \d so we can safely assume the string is not numeric
		// for negative classes
		if ($ast->getId() === '#negativeclass') {
			$walkResult = $walkResult->numeric(TrinaryLogic::createNo());
		}

		foreach ($children as $child) {
			$walkResult = $this->walkGroupAst(
				$child,
				$inAlternation,
				$inClass,
				$patternModifiers,
				$walkResult,
			);
		}

		return $walkResult;
	}

	private function isMaybeEmptyNode(TreeNode $node, string $patternModifiers, bool &$isNonFalsy): bool
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
			return $literal === '';
		}

		foreach ($node->getChildren() as $child) {
			if (!$this->isMaybeEmptyNode($child, $patternModifiers, $isNonFalsy)) {
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
