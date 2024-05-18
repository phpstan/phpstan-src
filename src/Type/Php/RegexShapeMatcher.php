<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Hoa\Compiler\Exception\Exception;
use Hoa\Compiler\Llk\Llk;
use Hoa\Compiler\Llk\TreeNode;
use Hoa\File\Read;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_key_last;
use function array_keys;
use function is_string;
use function preg_match;
use function preg_replace;
use function str_contains;
use const PREG_OFFSET_CAPTURE;
use const PREG_UNMATCHED_AS_NULL;

final class RegexShapeMatcher
{

	/**
	 * @param int-mask<PREG_OFFSET_CAPTURE|PREG_UNMATCHED_AS_NULL>|null $flags
	 */
	public function matchType(string $regex, ?int $flags, TypeSpecifierContext $context): ?Type
	{
		// add one capturing group to the end so all capture group keys
		// are present in the $matches
		// see https://3v4l.org/sOXbn, https://3v4l.org/3SdDM
		$captureGroupsRegex = preg_replace('~.[a-z\s]*$~i', '|(?<phpstanNamedCaptureGroupLast>)$0', $regex);

		if (
			$captureGroupsRegex === null
			|| @preg_match($captureGroupsRegex, '', $matches, PREG_UNMATCHED_AS_NULL) === false
		) {
			return null;
		}
		unset($matches[array_key_last($matches)]);
		unset($matches['phpstanNamedCaptureGroupLast']);

		// XXX hoa/regex does not support named capturing groups
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
					!$context->true(),
				);

				continue;
			}

			if (!$context->true()) {
				$optional = true;
			} else {
				$optional = $remainingNonOptionalGroupCount <= 0;
				$remainingNonOptionalGroupCount--;
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
		/** @throws void */
		$parser = Llk::load(new Read('hoa://Library/Regex/Grammar.pp'));
		try {
			$ast = $parser->parse($regex);
		} catch ( Exception) { // @phpstan-ignore catch.notThrowable
			return null;
		}

		return $this->walkRegexAst($ast, 0, 0);
	}

	private function walkRegexAst(TreeNode $ast, int $inAlternation, int $inOptionalQuantification): int
	{
		if (
			$ast->getId() === '#capturing'
			&& !($inAlternation > 0 || $inOptionalQuantification > 0)
		) {
			return 1;
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

		$count = 0;
		foreach ($ast->getChildren() as $child) {
			$count += $this->walkRegexAst($child, $inAlternation, $inOptionalQuantification);
		}

		return $count;
	}

}
