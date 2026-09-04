<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Override;
use PHPStan\Command\Output;
use PHPStan\Testing\PHPStanTestCase;
use function stream_wrapper_register;
use function stream_wrapper_unregister;

class ResultCacheManagerVanishedFileTest extends PHPStanTestCase
{

	protected function setUp(): void
	{
		parent::setUp();

		ResultCacheStreamWrapper::$deleted = false;
		stream_wrapper_register(ResultCacheStreamWrapper::SCHEME, ResultCacheStreamWrapper::class);
	}

	#[Override]
	protected function tearDown(): void
	{
		stream_wrapper_unregister(ResultCacheStreamWrapper::SCHEME);

		parent::tearDown();
	}

	public function testCacheFileThatVanishedBeforeOpeningIsNotReportedAsCorrupted(): void
	{
		$manager = self::getContainer()->getByType(ResultCacheManagerFactory::class)->create([]);
		$resultCache = $manager->restore([], false, false, null, $this->createStub(Output::class));

		$this->assertTrue($resultCache->isFullAnalysis());
		$this->assertSame('Result cache not used because the cache file disappeared while it was being read.', $resultCache->getFullAnalysisReason());
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/result-cache-vanished.neon',
		];
	}

}
