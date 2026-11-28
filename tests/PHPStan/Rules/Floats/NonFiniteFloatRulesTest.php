<?php declare(strict_types = 1);

namespace PHPStan\Rules\Floats;

use PHPStan\Rules\Rule;
use PHPStan\Testing\CompositeRule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\Php\PrintfFormatParser;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<CompositeRule>
 */
class NonFiniteFloatRulesTest extends RuleTestCase
{

	private bool $checkNonFiniteFloats = false;

	protected function getRule(): Rule
	{
		$helper = new NonFiniteFloatHelper($this->checkNonFiniteFloats);
		$reflectionProvider = $this->createReflectionProvider();

		// @phpstan-ignore argument.type
		return new CompositeRule([
			new JsonEncodeNonFiniteFloatRule($reflectionProvider, $helper),
			new EchoNanRule($helper),
			new PrintNanRule($helper),
			new ConcatNanRule($helper),
			new AssignConcatNanRule($helper),
			new InterpolatedStringNanRule($helper),
			new StringCastNanRule($helper),
			new IntCastNonFiniteFloatRule($helper),
			new IntvalNonFiniteFloatRule($reflectionProvider, $helper),
			new FormatNonFiniteFloatRule($reflectionProvider, self::getContainer()->getByType(PrintfFormatParser::class), $helper),
		]);
	}

	public function testDefinite(): void
	{
		$this->analyse([__DIR__ . '/data/non-finite-float.php'], self::errors([
			['Parameter #1 $value of function json_encode() is INF or NAN, which cannot be JSON encoded: NAN given.', 11, false],
			['Echo converts NAN to string, which emits a warning.', 12, true],
			['Casting to int converts INF to int, which emits a warning.', 13, true],
			['Placeholder "%f" of function sprintf() formats NAN, which prints "NaN", "INF" or "-INF".', 14, false],
			['Echo converts NAN to string, which emits a warning.', 18, true],
			['Parameter #1 $value of function json_encode() contains INF or NAN, which cannot be JSON encoded: array{a: NAN} given.', 38, false],
			['Print converts NAN to string, which emits a warning.', 50, true],
			['Concatenation converts NAN to string, which emits a warning.', 51, true],
			['String interpolation converts NAN to string, which emits a warning.', 53, true],
			['Casting to string converts NAN to string, which emits a warning.', 54, true],
			['Casting to int converts NAN to int, which emits a warning.', 68, true],
			['Function intval() converts NAN to int, which emits a warning.', 69, true],
			['Parameter #1 $num of function number_format() formats NAN, which prints "nan" or "inf".', 83, false],
			['Placeholder "%.2f" of function sprintf() formats INF, which prints "NaN", "INF" or "-INF".', 85, false],
			['Placeholder "%d" of function sprintf() formats INF, which prints an unspecified number.', 85, false],
			['Placeholder "%e" of function sprintf() formats NAN, which prints "NaN", "INF" or "-INF".', 86, false],
			['Placeholder "%s" of function sprintf() formats NAN, which prints "NAN" with a warning.', 86, true],
		]));
	}

	public function testCheckNonFiniteFloats(): void
	{
		$this->checkNonFiniteFloats = true;
		$this->analyse([__DIR__ . '/data/non-finite-float.php'], self::errors([
			['Parameter #1 $value of function json_encode() is INF or NAN, which cannot be JSON encoded: NAN given.', 11, false],
			['Echo converts NAN to string, which emits a warning.', 12, true],
			['Casting to int converts INF to int, which emits a warning.', 13, true],
			['Placeholder "%f" of function sprintf() formats NAN, which prints "NaN", "INF" or "-INF".', 14, false],
			['Echo converts float to string, which emits a warning when it is NAN.', 16, true],
			['Echo converts NAN to string, which emits a warning.', 18, true],
			['Parameter #1 $value of function json_encode() can contain INF or NAN, which cannot be JSON encoded: float<0.0, max> given.', 34, false],
			['Parameter #1 $value of function json_encode() can contain INF or NAN, which cannot be JSON encoded: NAN|float<0.0, 1.0> given.', 35, false],
			['Parameter #1 $value of function json_encode() can contain INF or NAN, which cannot be JSON encoded: list<float> given.', 36, false],
			['Parameter #1 $value of function json_encode() contains INF or NAN, which cannot be JSON encoded: array{a: NAN} given.', 38, false],
			['Print converts NAN to string, which emits a warning.', 50, true],
			['Concatenation converts NAN to string, which emits a warning.', 51, true],
			['Concatenation converts float to string, which emits a warning when it is NAN.', 52, true],
			['String interpolation converts NAN to string, which emits a warning.', 53, true],
			['Casting to string converts NAN to string, which emits a warning.', 54, true],
			['Casting to int converts NAN to int, which emits a warning.', 68, true],
			['Function intval() converts NAN to int, which emits a warning.', 69, true],
			['Casting to int converts float<-1.7976931348623157E+308, 1.7976931348623157E+308> to int, which emits a warning when it is NAN, INF or out of the int range.', 71, true],
			['Casting to int converts float to int, which emits a warning when it is NAN, INF or out of the int range.', 72, true],
			['Parameter #1 $num of function number_format() formats NAN, which prints "nan" or "inf".', 83, false],
			['Placeholder "%.2f" of function sprintf() formats INF, which prints "NaN", "INF" or "-INF".', 85, false],
			['Placeholder "%d" of function sprintf() formats INF, which prints an unspecified number.', 85, false],
			['Placeholder "%e" of function sprintf() formats NAN, which prints "NaN", "INF" or "-INF".', 86, false],
			['Placeholder "%s" of function sprintf() formats NAN, which prints "NAN" with a warning.', 86, true],
			['Placeholder "%1$f" of function sprintf() formats float, which prints "NaN", "INF" or "-INF" when it is not a finite float.', 87, false],
			['Placeholder "%d" of function printf() formats float<-1.7976931348623157E+308, 1.7976931348623157E+308>, which prints an unspecified number when it is NAN, INF or out of the int range.', 88, false],
			['Placeholder "%F" of function fprintf() formats float, which prints "NaN", "INF" or "-INF" when it is not a finite float.', 89, false],
		]));
	}

	/**
	 * Conversions to string and to int warn only on PHP 8.5+.
	 *
	 * @param list<array{string, int, bool}> $errors message, line, PHP 8.5+ only
	 * @return list<array{string, int}>
	 */
	private static function errors(array $errors): array
	{
		$result = [];
		foreach ($errors as [$message, $line, $php85Only]) {
			if ($php85Only && PHP_VERSION_ID < 80500) {
				continue;
			}
			$result[] = [$message, $line];
		}

		return $result;
	}

}
