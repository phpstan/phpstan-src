<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

class PrintfFormatStringHelperTest extends \PHPUnit\Framework\TestCase
{

	/**
	 * @dataProvider dataGetPlaceholders
	 * @param array<int,array{string,int,string}> $expected
	 */
	public function testGetPlaceholders(array $expected, string $input): void
	{
		self::assertSame($expected, (new PrintfFormatStringHelper($input))->getPlaceholders());
	}

	public function dataGetPlaceholders(): iterable
	{
		yield [
			[],
			'',
		];

		yield [
			[['E', 1, '%.E']],
			'%.E',
		];

		yield [
			[['s', 1, '%s']],
			'%s',
		];

		yield [
			[['s', 1, '%s'], ['s', 2, '%s']],
			'Hello %s and %s!',
		];

		yield [
			[],
			'%%',
		];

		yield [
			[['_', 1, '%']],
			'%%%',
		];

		yield [
			[['_', 1, '% %']],
			'%%% %',
		];

		yield [
			[['_', 1, '% %']],
			'%%% %s',
		];

		yield [
			[['_', 1, '% %'], ['u', 2, '%u']],
			'%%% %s%u',
		];

		yield [
			[['_', 1, '% %'], ['_', 2, '%']],
			'%%% %%%%',
		];

		yield [
			[],
			'%%%% %%%%',
		];

		yield [
			[['f', 1, '%f']],
			'How many %%%f percent',
		];

		yield [
			[['d', 1, '%041d'], ['d', 2, '% 25d'], ['d', 3, '%\'x39d']],
			'%041d % 25d %\'x39d',
		];
		yield [
			[['s', 1, '%s']],
			'foo%sbar',
		];
		yield [
			[['d', 1, '%1$\'y-10d']],
			's%1$\'y-10d bar',
		];
		yield [
			[['s', 1, '%s'], ['d', 2, '%d'], ['c', 6, '%06$\'c10.2c']],
			'%s %d foo %06$\'c10.2c foo',
		];
		yield [
			[['s', 1, '%s'], ['d', 1, '%1$\'x242d'], ['s', 2, '%2$s'], ['s', 10, '%10$s']],
			'this %% is my %s and %1$\'x242d here %2$s is %10$s',
		];
		yield [
			[['g', 1, '%g'], ['s', 2, '%2$s'], ['f', 1, '%1$f'], ['u', 2, '%u']],
			'%g %2$s-%1$f %u',
		];
		yield [
			[['_', 1, '%']],
			'1000%',
		];

		yield [
			[['_', 19, '%19$']],
			'%19$',
		];

		yield [
			[['s', 5, '%5$s'], ['u', 1, '%u']],
			'%5$s%u',
		];
	}

	/**
	 * @dataProvider dataGetNumberOfRequiredArguments
	 */
	public function testGetNumberOfRequiredArguments(int $expected, string $input): void
	{
		self::assertSame($expected, self::howManyArgs($input)); // Check that the expected amount matches what PHP thinks
		self::assertSame($expected, (new PrintfFormatStringHelper($input))->getNumberOfRequiredArguments());
	}

	public function dataGetNumberOfRequiredArguments(): iterable
	{
		foreach ($this->dataGetPlaceholders() as $data) {
			$args = array_column($data[0], 1);

			yield [$args !== [] ? max($args) : 0, $data[1]];
		}
	}

	/**
	 * Count the number of arguments PHP requires for a format string.
	 */
	private static function howManyArgs(string $format): int
	{
		// @todo PHP 8 can parse the exception which says how many arguments are required.
		$num = 0;

		while (self::isEnoughArgs($format, $num) === false) {
			$num++;
		}

		return $num;
	}

	private static function isEnoughArgs(string $format, int $numArgs): bool
	{
		return @sprintf($format, ...array_fill(0, $numArgs, '')) !== false;
	}

}
