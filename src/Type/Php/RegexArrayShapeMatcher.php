<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Hoa\Compiler\Llk\Llk;
use Hoa\Compiler\Llk\Parser;
use Hoa\Compiler\Llk\TreeNode;
use Hoa\Exception\Exception;
use Hoa\File\Read;
use Nette\Utils\Strings;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_key_exists;
use function array_reverse;
use function count;
use function in_array;
use function is_int;
use function is_string;
use function sscanf;
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
	)
	{
	}

	public function matchExpr(Expr $patternExpr, ?Type $flagsType, TrinaryLogic $wasMatched, Scope $scope): ?Type
	{
		return $this->matchPatternType($this->getPatternType($patternExpr, $scope), $flagsType, $wasMatched);
	}

	/**
	 * @deprecated use matchExpr() instead for a more precise result
	 */
	public function matchType(Type $patternType, ?Type $flagsType, TrinaryLogic $wasMatched): ?Type
	{
		return $this->matchPatternType($patternType, $flagsType, $wasMatched);
	}

	private function matchPatternType(Type $patternType, ?Type $flagsType, TrinaryLogic $wasMatched): ?Type
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
	 * @param int-mask<PREG_OFFSET_CAPTURE|PREG_UNMATCHED_AS_NULL|self::PREG_UNMATCHED_AS_NULL_ON_72_73>|null $flags
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
		TrinaryLogic $wasMatched,
		int $trailingOptionals,
		int $flags,
	): Type
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
			$groupValueType = $this->getValueType($captureGroup->getType(), $flags);

			if (!$wasMatched->yes()) {
				$optional = true;
			} else {
				if ($i < $countGroups - $trailingOptionals) {
					$optional = false;
					if ($this->containsUnmatchedAsNull($flags) && !$captureGroup->isOptional()) {
						$groupValueType = TypeCombinator::removeNull($groupValueType);
					}
				} elseif ($this->containsUnmatchedAsNull($flags)) {
					$optional = false;
				} else {
					$optional = $captureGroup->isOptional();
				}
			}

			if (!$optional && $captureGroup->isOptional() && !$this->containsUnmatchedAsNull($flags)) {
				$groupValueType = TypeCombinator::union($groupValueType, new ConstantStringType(''));
			}

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

		return $builder->getArray();
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
				$this->createGroupType($ast),
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

		if ($value['token'] === 'n_to_m') {
			if (sscanf($value['value'], '{%d,%d}', $n, $m) !== 2 || !is_int($n) || !is_int($m)) {
				throw new ShouldNotHappenException();
			}

			$min = $n;
			$max = $m;
		} elseif ($value['token'] === 'exactly_n') {
			if (sscanf($value['value'], '{%d}', $n) !== 1 || !is_int($n)) {
				throw new ShouldNotHappenException();
			}

			$min = $n;
			$max = $n;
		} elseif ($value['token'] === 'zero_or_one') {
			$min = 0;
			$max = 1;
		} elseif ($value['token'] === 'zero_or_more') {
			$min = 0;
		}

		return [$min, $max];
	}

	private function createGroupType(TreeNode $group): Type
	{
		$isNonEmpty = TrinaryLogic::createMaybe();
		$isNumeric = TrinaryLogic::createMaybe();
		$inOptionalQuantification = false;

		$this->walkGroupAst($group, $isNonEmpty, $isNumeric, $inOptionalQuantification);

		$accessories = [];
		if ($isNumeric->yes()) {
			$accessories[] = new AccessoryNumericStringType();
		} elseif ($isNonEmpty->yes()) {
			$accessories[] = new AccessoryNonEmptyStringType();
		}

		if ($accessories !== []) {
			$accessories[] = new StringType();
			return new IntersectionType($accessories);
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
				}

				if (!$inOptionalQuantification) {
					$isNonEmpty = TrinaryLogic::createYes();
				}
			}

			if ($ast->getValueToken() === 'character_type') {
				if ($ast->getValueValue() === '\d') {
					if ($isNumeric->maybe()) {
						$isNumeric = TrinaryLogic::createYes();
					}
				} else {
					$isNumeric = TrinaryLogic::createNo();
				}

				if (!$inOptionalQuantification) {
					$isNonEmpty = TrinaryLogic::createYes();
				}
			}
		}

		if ($ast->getId() === '#range' || $ast->getId() === '#class') {
			if ($isNumeric->maybe()) {
				$allNumeric = null;
				foreach ($children as $child) {
					$literalValue = $this->getLiteralValue($child);

					if ($literalValue === null) {
						break;
					}

					if (Strings::match($literalValue, '/^\d+$/') === null) {
						$allNumeric = false;
						break;
					}

					$allNumeric = true;
				}

				if ($allNumeric === true) {
					$isNumeric = TrinaryLogic::createYes();
				}
			}

			if (!$inOptionalQuantification) {
				$isNonEmpty = TrinaryLogic::createYes();
			}
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
		if ($node->getId() === 'token' && $node->getValueToken() === 'literal') {
			return $node->getValueValue();
		}

		// literal "-" outside of a character class like '~^((\\d{1,6})-)$~'
		if ($node->getId() === 'token' && $node->getValueToken() === 'range') {
			return $node->getValueValue();
		}

		return null;
	}

	private function getPatternType(Expr $patternExpr, Scope $scope): Type
	{
		if ($patternExpr instanceof Expr\BinaryOp\Concat) {
			return $this->resolvePatternConcat($patternExpr, $scope);
		}

		return $scope->getType($patternExpr);
	}

	/**
	 * Ignores preg_quote() calls in the concatenation as these are not relevant for array-shape matching.
	 *
	 * This assumption only works for the ArrayShapeMatcher therefore it is not implemented for the common case in Scope.
	 *
	 * see https://github.com/phpstan/phpstan-src/pull/3233#discussion_r1676938085
	 */
	private function resolvePatternConcat(Expr\BinaryOp\Concat $concat, Scope $scope): Type
	{
		if (
			$concat->left instanceof Expr\FuncCall
			&& $concat->left->name instanceof Name
			&& $concat->left->name->toLowerString() === 'preg_quote'
		) {
			$left = new ConstantStringType('');
		} elseif ($concat->left instanceof Expr\BinaryOp\Concat) {
			$left = $this->resolvePatternConcat($concat->left, $scope);
		} else {
			$left = $scope->getType($concat->left);
		}

		if (
			$concat->right instanceof Expr\FuncCall
			&& $concat->right->name instanceof Name
			&& $concat->right->name->toLowerString() === 'preg_quote'
		) {
			$right = new ConstantStringType('');
		} elseif ($concat->right instanceof Expr\BinaryOp\Concat) {
			$right = $this->resolvePatternConcat($concat->right, $scope);
		} else {
			$right = $scope->getType($concat->right);
		}

		$strings = [];
		foreach ($left->getConstantStrings() as $leftString) {
			foreach ($right->getConstantStrings() as $rightString) {
				$strings[] = new ConstantStringType($leftString->getValue() . $rightString->getValue());
			}
		}

		return TypeCombinator::union(...$strings);
	}

}
