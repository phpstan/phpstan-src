<?php declare(strict_types = 1);

namespace PHPStan\Php;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ComposerPhpVersionParserTest extends TestCase
{

	public static function dataParse(): iterable
	{
		yield ['^8.2', 80200, 89999];
		yield ['>=8.1, <=8.2.99', 80100, 80299];
		yield ['<=8.3', null, 80300];
		yield ['>=7.2 <8.0', 70200, 79999];

		// exclusive bounds
		yield ['8.4.*', 80400, 80499];
		yield ['~8.3.0', 80300, 80399];
		yield ['>=8.2 <8.4', 80200, 80399];
		yield ['<8.5.2', null, 80501];
		yield ['>8.2.3', 80204, null];
		yield ['>8.2.3 <8.5.2', 80204, 80501];
		yield ['>8.2', 80201, null];
	}

	#[DataProvider('dataParse')]
	public function testParse(string $constraint, ?int $expectedMinVersionId, ?int $expectedMaxVersionId): void
	{
		$parser = new ComposerPhpVersionParser();
		[$minVersion, $maxVersion] = $parser->parse(
			$constraint,
			static fn (string $version, int $versionId, bool $isMaxVersion): PhpVersion => new PhpVersion($versionId),
		);

		$this->assertSame($expectedMinVersionId, $minVersion?->getVersionId());
		$this->assertSame($expectedMaxVersionId, $maxVersion?->getVersionId());
	}

}
