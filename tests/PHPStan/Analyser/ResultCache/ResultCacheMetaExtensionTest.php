<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Command\AnalyseApplication;
use PHPStan\Command\Symfony\SymfonyOutput;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use function fopen;

class ResultCacheMetaExtensionTest extends PHPStanTestCase
{

	public static function getAdditionalConfigFiles(): array
	{
		return [__DIR__ . '/throwing-meta-extension.neon'];
	}

	public function testThrowingMetaExtensionIsReportedAsInternalError(): void
	{
		$analyserApplication = self::getContainer()->getByType(AnalyseApplication::class);
		$resource = fopen('php://memory', 'w', false);
		if ($resource === false) {
			throw new ShouldNotHappenException();
		}
		$output = new StreamOutput($resource);
		$symfonyOutput = new SymfonyOutput(
			$output,
			new \PHPStan\Command\Symfony\SymfonyStyle(new SymfonyStyle($this->createStub(InputInterface::class), $output)),
		);

		$analysisResult = $analyserApplication->analyse(
			[__DIR__ . '/data/file-with-error.php'],
			true,
			$symfonyOutput,
			$symfonyOutput,
			false,
			false,
			null,
			null,
			null,
			null,
			$this->createStub(InputInterface::class),
		);

		$this->assertTrue($analysisResult->hasInternalErrors());
		$internalErrors = $analysisResult->getInternalErrorObjects();
		$this->assertCount(1, $internalErrors);
		$this->assertStringContainsString('boom from getHash', $internalErrors[0]->getMessage());
		$this->assertStringContainsString(
			'computing result cache metadata',
			$internalErrors[0]->getContextDescription(),
		);
	}

}
