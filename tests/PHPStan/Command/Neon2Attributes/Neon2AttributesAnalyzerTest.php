<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

use PHPStan\File\FileHelper;
use PHPUnit\Framework\TestCase;
use function array_map;
use function dirname;

class Neon2AttributesAnalyzerTest extends TestCase
{

	public function testAnalyze(): void
	{
		$repoRoot = dirname(__DIR__, 4);
		$analyzer = new Neon2AttributesAnalyzer(new FileHelper($repoRoot), $repoRoot);
		$plan = $analyzer->analyze(__DIR__ . '/data/convert.neon');

		$this->assertSame([
			['rules', 0, 'Neon2AttributesFixtures\ConvFixtureRule', '#[RegisteredRule(level: 0)]'],
			['services', 0, 'Neon2AttributesFixtures\ConvFixtureService', '#[AutowiredService]'],
			['services', 1, 'Neon2AttributesFixtures\ConvFixtureExtension', '#[AutowiredService]'],
			['services', 2, 'Neon2AttributesFixtures\ConvFixtureUntaggedExtension', '#[AutowiredService(autoTag: false)]'],
		], array_map(
			static fn (ServiceConversion $conversion): array => [$conversion->section, $conversion->entryIndex, $conversion->className, $conversion->attributeCode],
			$plan->conversions,
		));

		$this->assertSame(
			[
				'tmpDir' => '#[AutowiredParameter]',
				'level' => "#[AutowiredParameter(ref: '%usedLevel%')]",
			],
			$plan->conversions[1]->parameterAttributes,
		);

		$this->assertSame([
			['PHPStan\File\FileHelper', 'The class already carries the PHPStan\DependencyInjection\AutowiredService attribute.'],
			['Nette\Neon\Neon', 'The class is not part of this project.'],
			['Neon2AttributesFixtures\ConvFixtureService', 'The service definition uses `setup` which cannot be expressed with an attribute.'],
		], array_map(
			static fn (SkippedEntry $skipped): array => [$skipped->description, $skipped->reason],
			$plan->skipped,
		));

		// the fixtures are autoloaded through the autoload-dev classmap rule covering tests/PHPStan
		$this->assertSame(['../../..'], $plan->directoriesToDeclare);
	}

}
