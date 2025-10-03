<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\Printer\Printer;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Rules\TypeCoercionRuleHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<InvalidPartOfEncapsedStringRule>
 */
class InvalidPartOfEncapsedStringRuleTest extends RuleTestCase
{

	private ?TypeCoercionRuleHelper $typeCoercionRuleHelper = null;

	protected function getRule(): Rule
	{
		return new InvalidPartOfEncapsedStringRule(
			new ExprPrinter(new Printer()),
			new RuleLevelHelper(self::createReflectionProvider(), true, false, true, false, false, false, true),
			$this->typeCoercionRuleHelper ?? new TypeCoercionRuleHelper(true, true),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-encapsed-part.php'], [
			[
				'Part $std (stdClass) of encapsed string cannot be cast to string.',
				26,
			],
			[
				'Part $array (array) of encapsed string cannot be cast to string.',
				30,
			],
			[
				'Part $std (stdClass|string) of encapsed string cannot be cast to string.',
				56,
			],
			[
				'Part $array (array|string) of encapsed string cannot be cast to string.',
				60,
			],
		]);
	}

	public function testRuleWithStrictCoercions(): void
	{
		$this->typeCoercionRuleHelper = new TypeCoercionRuleHelper(true, false);
		$this->analyse([__DIR__ . '/data/invalid-encapsed-part.php'], [
			[
				'Part $std (stdClass) of encapsed string cannot be cast to string.',
				26,
			],
			[
				'Part $bool (bool) of encapsed string cannot be cast to string.',
				27,
			],
			[
				'Part $array (array) of encapsed string cannot be cast to string.',
				30,
			],
			[
				'Part $std (stdClass|string) of encapsed string cannot be cast to string.',
				56,
			],
			[
				'Part $bool (bool|string) of encapsed string cannot be cast to string.',
				57,
			],
			[
				'Part $array (array|string) of encapsed string cannot be cast to string.',
				60,
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testRuleWithNullsafeVariant(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-encapsed-part-nullsafe.php'], [
			[
				'Part $bar?->obj (stdClass|null) of encapsed string cannot be cast to string.',
				11,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testRuleWithEnum(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-encapsed-part-enum.php'], [
			[
				'Part $unitEnum (InvalidEncapsedPartEnum\\FooUnitEnum) of encapsed string cannot be cast to string.',
				21,
			],
			[
				'Part $intEnum (InvalidEncapsedPartEnum\\IntEnum) of encapsed string cannot be cast to string.',
				22,
			],
			[
				'Part $stringEnum (InvalidEncapsedPartEnum\\StringEnum) of encapsed string cannot be cast to string.',
				23,
			],
		]);
	}

}
