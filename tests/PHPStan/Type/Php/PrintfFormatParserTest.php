<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PrintfFormatParserTest extends TestCase
{

	public static function dataRequiredArgumentsCount(): iterable
	{
		yield ['', 0];
		yield ['no placeholders', 0];
		yield ['100%%', 0];
		yield ['%%%s', 1];
		yield ['%%%%s', 0];
		yield ['Product: %d, Image: %s', 2];
		yield ['%1$s %1$s', 1];
		yield ['%2$s %1$s', 2];
		yield ['%1$s %s %s', 2];
		yield ['%01$s', 1];
		yield ['%2147483646$s', 2147483646];
		yield ['%5%', 1];
		yield ['%1$%', 1];
		yield ['% %', 1];
		yield ['%ls', 1];
		yield ['%lX', 1];
		yield ["%'.10d", 1];
		yield ["%'%5d", 1];
		yield ['%-+5d', 1];
		yield ['%+ -5d', 1];
		yield ['%05.2f', 1];
		yield ['%.f', 1];
		yield ['%5.d', 1];
		yield ['%.2147483646f', 1];
		yield ['%*d', 2];
		yield ['%*.*f', 3];
		yield ['%1$*d', 1];
		yield ['%1$*2$d', 2];
		yield ['%.*3$f', 3];
		yield ['%e %E %g %G %h %H %c %o %x %X %b %u %F', 13];

		yield ['%', null];
		yield ['abc%', null];
		yield ['%%%', null];
		yield ['%-', null];
		yield ['%.', null];
		yield ['%5.2', null];
		yield ['%1$', null];
		yield ['%l', null];
		yield ["%'", null];
		yield ["%'x", null];
		yield ['%y', null];
		yield ['%i', null];
		yield ['%D', null];
		yield ['%lld', null];
		yield ['%5.2.3f', null];
		yield ['%5-3d', null];
		yield ['%05 d', null];
		yield ['%-1$s', null];
		yield ['%1$1$s', null];
		yield ["%\0", null];
		yield ['%0$s', null];
		yield ['%00$s', null];
		yield ['%$s', null];
		yield ['%2147483647$s', null];
		yield ['%99999999999999999999$s', null];
		yield ['%*0$d', null];
		yield ['%*$d', null];
		yield ['%*5d', null];
		yield ['%*-d', null];
		yield ['%.*0$f', null];
		yield ['%2147483647d', null];
		yield ['%.2147483647f', null];
	}

	#[DataProvider('dataRequiredArgumentsCount')]
	public function testRequiredArgumentsCount(string $format, ?int $expectedCount): void
	{
		$parser = new PrintfFormatParser();
		$uses = $parser->parse($format);
		if ($expectedCount === null) {
			$this->assertNull($uses);
			return;
		}

		$this->assertNotNull($uses);
		$this->assertSame($expectedCount, $parser->getRequiredArgumentsCount($uses));
	}

	public function testUses(): void
	{
		$parser = new PrintfFormatParser();
		$this->assertSame([
			['index' => 0, 'kind' => 'width', 'specifier' => 'g'],
			['index' => 1, 'kind' => 'precision', 'specifier' => 'g'],
			['index' => 2, 'kind' => 'value', 'specifier' => 'g'],
			['index' => 0, 'kind' => 'value', 'specifier' => 's'],
			['index' => 4, 'kind' => 'width', 'specifier' => 'd'],
			['index' => 3, 'kind' => 'value', 'specifier' => 'd'],
		], $parser->parse('%*.*g %1$s %*5$d'));
	}

}
