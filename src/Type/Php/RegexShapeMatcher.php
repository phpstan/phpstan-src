<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_key_last;
use function array_keys;
use function is_string;
use function preg_match;
use function preg_replace;
use const PREG_OFFSET_CAPTURE;
use const PREG_UNMATCHED_AS_NULL;

final class RegexShapeMatcher
{

	/**
	 * @param int-mask<PREG_OFFSET_CAPTURE|PREG_UNMATCHED_AS_NULL>|null $flags
	 */
	public function matchType(string $regex, ?int $flags, TypeSpecifierContext $context): Type
	{
		if ($flags !== null) {
			$trickFlags = PREG_UNMATCHED_AS_NULL | $flags;
		} else {
			$trickFlags = PREG_UNMATCHED_AS_NULL;
		}

		// add one capturing group to the end so all capture group keys
		// are present in the $matches
		// see https://3v4l.org/sOXbn, https://3v4l.org/3SdDM
		$regex = preg_replace('~.[a-z\s]*$~i', '|(?<phpstanNamedCaptureGroupLast>)$0', $regex);

		if (
			$regex === null
			|| @preg_match($regex, '', $matches, $trickFlags) === false
		) {
			return new ArrayType(new MixedType(), new StringType());
		}
		unset($matches[array_key_last($matches)]);
		unset($matches['phpstanNamedCaptureGroupLast']);

		$builder = ConstantArrayTypeBuilder::createEmpty();
		foreach (array_keys($matches) as $key) {
			// atm we can't differentiate optional from mandatory groups based on the pattern.
			// So we assume all are optional
			$optional = true;

			$keyType = $this->getKeyType($key);
			$valueType = $this->getValueType($flags ?? 0);

			if ($context->true() && $key === 0) {
				$optional = false;
			}

			$builder->setOffsetValueType(
				$keyType,
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

	private function countNonOptionalGroups(string $regex):int {
// 1. Read the grammar.
		$grammar  = new Hoa\File\Read(__DIR__.'/conf/RegexGrammar.pp');

// 2. Load the compiler.
		$compiler = Hoa\Compiler\Llk\Llk::load($grammar);

// 3. Lex, parse and produce the AST.
		$ast      = $compiler->parse($regex);

		echo "-------------------\n\n\n";

		var_dump($regex);

		// 4. Dump the result.
		$dump     = new Hoa\Compiler\Visitor\Dump();
		echo $dump->visit($ast);

		$groups = [];
		return $this->walk($ast, $groups, 0, 0);
	}

	private function walk(\Hoa\Compiler\Llk\TreeNode $ast, int $inAlternation, int $inOptionalQuantification): int
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
			$count += $this->walk($child, $inAlternation, $inOptionalQuantification);
		}

		return $count;

	}

}
