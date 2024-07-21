<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Hoa\Compiler\Llk\Llk;
use Hoa\Compiler\Llk\Parser;
use Hoa\Compiler\Llk\TreeNode;
use Hoa\Exception\Exception;
use Hoa\File\Read;
use Nette\Utils\RegexpException;
use Nette\Utils\Strings;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function array_key_exists;
use function array_reverse;
use function count;
use function in_array;
use function is_int;
use function is_string;
use function rtrim;
use function sscanf;
use function str_contains;
use function str_replace;
use function strlen;
use function substr;
use const PREG_OFFSET_CAPTURE;
use const PREG_UNMATCHED_AS_NULL;

/**
 * @api
 */
final class RegexArrayShapeMatcher
{

	/**
	 * Pass this into $flagsType as well if the library supports emulating PREG_UNMATCHED_AS_NULL on PHP 7.2 and 7.3
	 */
	public const PREG_UNMATCHED_AS_NULL_ON_72_73 = 2048;

	private static ?Parser $parser = null;

	public function __construct(
		private PhpVersion $phpVersion,
		private RegexExpressionHelper $regexExpressionHelper,
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

	/**
	 * @deprecated use matchExpr() instead for a more precise result
	 */
	public function matchType(Type $patternType, ?Type $flagsType, TrinaryLogic $wasMatched): ?Type
	{
		return $this->matchPatternType($patternType, $flagsType, $wasMatched, false);
	}

	private function matchPatternType(Type $patternType, ?Type $flagsType, TrinaryLogic $wasMatched, bool $matchesAll): ?Type
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
			if (!$flagsType instanceof ConstantIntegerType) {
				return null;
			}

			/** @var int-mask<PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL | self::PREG_UNMATCHED_AS_NULL_ON_72_73> $flags */
			$flags = $flagsType->getValue() & (PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL | self::PREG_UNMATCHED_AS_NULL_ON_72_73);

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
	 * @param int-mask<PREG_OFFSET_CAPTURE|PREG_UNMATCHED_AS_NULL|self::PREG_UNMATCHED_AS_NULL_ON_72_73>|null $flags
	 */
	private function matchRegex(string $regex, ?int $flags, TrinaryLogic $wasMatched, bool $matchesAll): ?Type
	{
		$parseResult = $this->parseGroups($regex);
		if ($parseResult === null) {
			// regex could not be parsed by Hoa/Regex
			return null;
		}
		[$groupList, $groupCombinations, $markVerbs] = $parseResult;

		$trailingOptionals = 0;
		foreach (array_reverse($groupList) as $captureGroup) {
			if (!$captureGroup->isOptional()) {
				break;
			}
			$trailingOptionals++;
		}

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
				$wasMatched,
				$trailingOptionals,
				$flags ?? 0,
				$markVerbs,
				$matchesAll
			);

			if (!$this->containsUnmatchedAsNull($flags ?? 0)) {
				$combiType = TypeCombinator::union(
					new ConstantArrayType([new ConstantIntegerType(0)], [new StringType()], [0], [], true),
					$combiType,
				);
			}
			return $combiType;
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
					} elseif ($group->getAlternationId() === $onlyTopLevelAlternationId && !$this->containsUnmatchedAsNull($flags ?? 0)) {
						unset($comboList[$groupId]);
					}
				}

				$combiType = $this->buildArrayType(
					$comboList,
					$wasMatched,
					$trailingOptionals,
					$flags ?? 0,
					$markVerbs,
					$matchesAll
				);

				$combiTypes[] = $combiType;

				foreach ($groupCombo as $groupId) {
					$group = $comboList[$groupId];
					$group->restoreNonOptional();
				}
			}

			if ($isOptionalAlternation && !$this->containsUnmatchedAsNull($flags ?? 0)) {
				$combiTypes[] = new ConstantArrayType([new ConstantIntegerType(0)], [new StringType()], [0], [], true);
			}

			return TypeCombinator::union(...$combiTypes);
		}

		return $this->buildArrayType(
			$groupList,
			$wasMatched,
			$trailingOptionals,
			$flags ?? 0,
			$markVerbs,
			$matchesAll
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
	 * @param list<string> $markVerbs
	 */
	private function buildMatchArrayType(
		array $captureGroups,
		TrinaryLogic $wasMatched,
		int $trailingOptionals,
		int $flags,
	): ConstantArrayTypeBuilder
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();

		// first item in matches contains the overall match.
		$builder->setOffsetValueType(
			$this->getKeyType(0),
			TypeCombinator::removeNull($this->getValueType(new StringType(), $flags)),
			!$wasMatched->yes(),
		);

		$countGroups = count($captureGroups);
		$i = 0;
		foreach ($captureGroups as $captureGroup) {
			$isTrailingOptional = $i >= $countGroups - $trailingOptionals;
			$groupValueType = $this->createGroupValueType($captureGroup, $wasMatched, $flags, $isTrailingOptional);
			$optional = $this->isGroupOptional($captureGroup, $wasMatched, $flags, $isTrailingOptional);

			if ($captureGroup->isNamed()) {
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

		return $builder;
	}

	/**
	 * @param array<RegexCapturingGroup> $captureGroups
	 */
	private function buildMatchesAllArrayType(
		array $captureGroups,
		TrinaryLogic $wasMatched,
		int $flags,
	): ConstantArrayTypeBuilders
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();

		$subjectValueType = TypeCombinator::removeNull($this->getValueType(new StringType(), $flags));
		if (!$wasMatched->yes()) {
			$subjectValueType = TypeCombinator::union($subjectValueType, new ConstantStringType(''));
		}
		$subjectValueType = AccessoryArrayListType::intersectWith(new ArrayType(new IntegerType(), $subjectValueType));

		// first item in matches contains the overall match.
		$builder->setOffsetValueType(
			$this->getKeyType(0),
			$subjectValueType,
		);

		$i = 0;
		foreach ($captureGroups as $captureGroup) {
			$groupValueType = $this->getValueType($captureGroup->getType(), $flags);

			if (!$this->containsUnmatchedAsNull($flags) && $captureGroup->isOptional()) {
				$groupValueType = TypeCombinator::removeNull($groupValueType);
				$groupValueType = TypeCombinator::union($groupValueType, new ConstantStringType(''));
			}

			$groupValueType = AccessoryArrayListType::intersectWith(new ArrayType(new IntegerType(), $groupValueType));
			if ($captureGroup->isNamed()) {
				$builder->setOffsetValueType(
					$this->getKeyType($captureGroup->getName()),
					$groupValueType,
				);
			}

			$builder->setOffsetValueType(
				$this->getKeyType($i + 1),
				$groupValueType,
			);

			$i++;
		}

		return $builder;
	}

	/**
	 * @param array<RegexCapturingGroup> $captureGroups
	 */
	private function buildArrayType(
		array $captureGroups,
		TrinaryLogic $wasMatched,
		int $trailingOptionals,
		int $flags,
		array $markVerbs,
		bool $matchesAll,
	): Type
	{
		if ($matchesAll) {
			$builder = $this->buildMatchesAllArrayType($captureGroups, $wasMatched, $flags);
		} else {
			$builder = $this->buildMatchArrayType($captureGroups, $wasMatched, $trailingOptionals, $flags);
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

		return $builder->getArray();
	}

	private function isGroupOptional(RegexCapturingGroup $captureGroup, TrinaryLogic $wasMatched, int $flags, bool $isTrailingOptional): bool
	{
		if (!$wasMatched->yes()) {
			$optional = true;
		} else {
			if (!$isTrailingOptional) {
				$optional = false;
			} elseif ($this->containsUnmatchedAsNull($flags)) {
				$optional = false;
			} else {
				$optional = $captureGroup->isOptional();
			}
		}

		return $optional;
	}

	private function createGroupValueType(RegexCapturingGroup $captureGroup, TrinaryLogic $wasMatched, int $flags, bool $isTrailingOptional): Type
	{
		$groupValueType = $this->getValueType($captureGroup->getType(), $flags);

		if ($wasMatched->yes()) {
			if (!$isTrailingOptional && $this->containsUnmatchedAsNull($flags) && !$captureGroup->isOptional()) {
				$groupValueType = TypeCombinator::removeNull($groupValueType);
			}
		}

		if (!$isTrailingOptional && !$this->containsUnmatchedAsNull($flags) && $captureGroup->isOptional()) {
			$groupValueType = TypeCombinator::union($groupValueType, new ConstantStringType(''));
		}

		return $groupValueType;
	}

	private function containsUnmatchedAsNull(int $flags): bool
	{
		return ($flags & PREG_UNMATCHED_AS_NULL) !== 0 && (($flags & self::PREG_UNMATCHED_AS_NULL_ON_72_73) !== 0 || $this->phpVersion->supportsPregUnmatchedAsNull());
	}

	private function getKeyType(int|string $key): Type
	{
		if (is_string($key)) {
			return new ConstantStringType($key);
		}

		return new ConstantIntegerType($key);
	}

	private function getValueType(Type $baseType, int $flags): Type
	{
		$valueType = $baseType;

		$offsetType = IntegerRangeType::fromInterval(0, null);
		if ($this->containsUnmatchedAsNull($flags)) {
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
	 * @return array{array<int, RegexCapturingGroup>, array<int, array<int, int[]>>, list<string>}|null
	 */
	private function parseGroups(string $regex): ?array
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
				$this->createGroupType($ast),
			);
			$parentGroup = $group;
		} elseif ($ast->getId() === '#namedcapturing') {
			$name = $ast->getChild(0)->getValueValue();
			$group = new RegexCapturingGroup(
				$captureGroupId++,
				$name,
				$inAlternation ? $alternationId : null,
				$inOptionalQuantification,
				$parentGroup,
				$this->createGroupType($ast),
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
			[$min] = $this->getQuantificationRange($ast);

			if ($min === 0) {
				$inOptionalQuantification = true;
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

	private function createGroupType(TreeNode $group): Type
	{
		$isNonEmpty = TrinaryLogic::createMaybe();
		$isNumeric = TrinaryLogic::createMaybe();
		$inOptionalQuantification = false;

		$this->walkGroupAst($group, $isNonEmpty, $isNumeric, $inOptionalQuantification);

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

	private function walkGroupAst(TreeNode $ast, TrinaryLogic &$isNonEmpty, TrinaryLogic &$isNumeric, bool &$inOptionalQuantification): void
	{
		$children = $ast->getChildren();

		if (
			$ast->getId() === '#concatenation'
			&& count($children) > 0
		) {
			$isNonEmpty = TrinaryLogic::createYes();
		}

		if ($ast->getId() === '#quantification') {
			[$min] = $this->getQuantificationRange($ast);

			if ($min === 0) {
				$inOptionalQuantification = true;
			}
			if ($min >= 1) {
				$isNonEmpty = TrinaryLogic::createYes();
				$inOptionalQuantification = false;
			}
		}

		if ($ast->getId() === 'token') {
			$literalValue = $this->getLiteralValue($ast);
			if ($literalValue !== null) {
				if (Strings::match($literalValue, '/^\d+$/') === null) {
					$isNumeric = TrinaryLogic::createNo();
				} elseif ($isNumeric->maybe()) {
					$isNumeric = TrinaryLogic::createYes();
				}

				if (!$inOptionalQuantification) {
					$isNonEmpty = TrinaryLogic::createYes();
				}
			}
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
			);
		}
	}

	private function getLiteralValue(TreeNode $node): ?string
	{
		if ($node->getId() !== 'token') {
			return null;
		}

		// token is the token name from grammar without the namespace so literal and class:literal are both called literal here
		$token = $node->getValueToken();
		$value = $node->getValueValue();

		if (in_array($token, ['literal', 'escaped_end_class'], true)) {
			if (strlen($node->getValueValue()) > 1 && $value[0] === '\\') {
				return substr($value, 1);
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

	private function getPatternType(Expr $patternExpr, Scope $scope): Type
	{
		if ($patternExpr instanceof Expr\BinaryOp\Concat) {
			return $this->regexExpressionHelper->resolvePatternConcat($patternExpr, $scope);
		}

		return $scope->getType($patternExpr);
	}

}
