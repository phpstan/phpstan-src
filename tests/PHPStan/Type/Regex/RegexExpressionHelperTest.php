<?php declare(strict_types = 1);

namespace PHPStan\Type\Regex;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class RegexExpressionHelperTest extends PHPStanTestCase
{

	public static function dataRemoveDelimitersAndModifiers(): array
	{
		return [
			[
				'/x/',
				'x',
			],
			[
				'~x~',
				'x',
			],
			[
				'~~',
				'',
			],
			[
				'~x~is',
				'x',
			],
			[
				' ~x~',
				'x',
			],
			[
				'  ~x~  ',
				'x',
			],
			[
				'[x]',
				'x',
			],
			[
				'{x}mx',
				'x',
			],
		];
	}

	#[DataProvider('dataRemoveDelimitersAndModifiers')]
	public function testRemoveDelimitersAndModifiers(string $inputPattern, string $expectedPatternWithoutDelimiter): void
	{
		$regexExpressionHelper = self::getContainer()->getByType(RegexExpressionHelper::class);

		$this->assertSame(
			$expectedPatternWithoutDelimiter,
			$regexExpressionHelper->removeDelimitersAndModifiers($inputPattern),
		);
	}

}
