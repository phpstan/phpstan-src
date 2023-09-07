<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\File\NullRelativePathHelper;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\Testing\ErrorFormatterTestCase;
use function sprintf;

class FailWithoutResultCacheErrorFormatterTest extends ErrorFormatterTestCase
{

	public function dataFormatterOutputProvider(): iterable
	{
		yield [
			'not used result cache',
			2,
			'',
			false,
		];

		yield [
			'result cache used',
			0,
			'
 [OK] No errors

',
			true,
		];
	}

	/**
	 * @dataProvider dataFormatterOutputProvider
	 */
	public function testFormatErrors(
		string $message,
		int $exitCode,
		string $expected,
		bool $isResultCacheUsed,
	): void
	{
		$formatter = $this->createErrorFormatter();

		$this->assertSame($exitCode, $formatter->formatErrors(
			$this->createAnalysisResult($isResultCacheUsed),
			$this->getOutput(),
		), sprintf('%s: response code do not match', $message));

		$this->assertSame($expected, $this->getOutputContent(), sprintf('%s: output do not match', $message));
	}

	private function createErrorFormatter(): FailWithoutResultCacheErrorFormatter
	{
		$relativePathHelper = new NullRelativePathHelper();
		return new FailWithoutResultCacheErrorFormatter(
			new TableErrorFormatter(
				$relativePathHelper,
				new SimpleRelativePathHelper(self::DIRECTORY_PATH),
				new CiDetectedErrorFormatter(
					new GithubErrorFormatter($relativePathHelper),
					new TeamcityErrorFormatter($relativePathHelper),
				),
				false,
				null,
				null,
			),
		);
	}

	private function createAnalysisResult(bool $isResultCacheUsed): AnalysisResult
	{
		return new AnalysisResult(
			[],
			[],
			[],
			[],
			[],
			false,
			null,
			true,
			0,
			$isResultCacheUsed,
		);
	}

}
