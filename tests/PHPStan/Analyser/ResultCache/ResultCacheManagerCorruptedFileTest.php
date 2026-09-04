<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Override;
use PHPStan\Command\Output;
use PHPStan\File\FileWriter;
use PHPStan\Testing\PHPStanTestCase;
use function dirname;
use function is_dir;
use function is_file;
use function mkdir;
use function unlink;

class ResultCacheManagerCorruptedFileTest extends PHPStanTestCase
{

	/**
	 * A real file on purpose: deleting it clears PHP's stat cache, so a check that runs after the
	 * unlink sees the file as gone. A stream wrapper cannot reproduce that.
	 */
	public function testCorruptedCacheFileIsReportedAsCorrupted(): void
	{
		$cacheFilePath = self::getContainer()->getParameter('resultCachePath');
		if (!is_dir(dirname($cacheFilePath))) {
			mkdir(dirname($cacheFilePath), 0777, true);
		}
		FileWriter::write($cacheFilePath, "garbage\n");

		$manager = self::getContainer()->getByType(ResultCacheManagerFactory::class)->create([]);
		$resultCache = $manager->restore([], false, false, null, $this->createStub(Output::class));

		$this->assertTrue($resultCache->isFullAnalysis());
		$this->assertSame('Result cache not used because the cache file is corrupted.', $resultCache->getFullAnalysisReason());
		$this->assertFalse(is_file($cacheFilePath));
	}

	#[Override]
	protected function tearDown(): void
	{
		$cacheFilePath = self::getContainer()->getParameter('resultCachePath');
		if (is_file($cacheFilePath)) {
			unlink($cacheFilePath);
		}

		parent::tearDown();
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/result-cache-corrupted.neon',
		];
	}

}
