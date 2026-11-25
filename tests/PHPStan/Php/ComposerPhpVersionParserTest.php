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
		yield ['>=8.3 <8.3.0.1', 80300, 80300];
		yield ['<8.5.2', null, 80501];
		yield ['<8.5.2.1', null, 80502];
		yield ['<8.5.2.00', null, 80501];
		yield ['>8.2.3', 80204, null];
		yield ['>8.2.3 <8.5.2', 80204, 80501];
		yield ['>8.2', 80201, null];
		yield ['>8.2.99', 80300, null];
		yield ['~8.2.0 || ~8.3.2', 80200, 80399];

		// PHP_VERSION_ID is the same for a release and its prereleases
		yield ['>8.5.0-RC1', 80500, null];
		yield ['<8.5.0-RC1', null, 80500];
		yield ['>8.5.0-dev', 80500, null];
		yield ['<8.5.0-dev', null, 80499];
		yield ['<8.5.2-stable', null, 80502];
		yield ['>8.5.0-p1', 80500, null];
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
