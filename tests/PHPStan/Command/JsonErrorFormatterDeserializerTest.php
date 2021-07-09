<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\Analyser\Error;
use PHPStan\Command\ErrorFormatter\JsonErrorFormatter;
use PHPStan\Testing\TestCase;

class JsonErrorFormatterDeserializerTest extends TestCase
{

	public function testJsonIsBidirectional(): void
	{
		$jsonFormatter = new JsonErrorFormatter(true);

		$firstSerialization = new CaptureOutput();
		$jsonFormatter->formatErrors(self::createSampleAnalysisResult(), $firstSerialization);

		$parsedAnalysisResult = JsonErrorFormatterDeserializer::deserializeErrors($firstSerialization->getResult());

		$secondSerialization = new CaptureOutput();
		$jsonFormatter->formatErrors($parsedAnalysisResult, $secondSerialization);

		self::assertEquals('{', $firstSerialization->getResult()[0]);
		self::assertEquals($firstSerialization->getResult(), $secondSerialization->getResult());
	}

	private static function createSampleAnalysisResult(): AnalysisResult
	{
		$fileSpecificErrors = [
			new Error(
				'Cushioned proxy should only be used untethered',
				'src/Code.php',
				42,
				true
			),
			new Error(
				'Liaised mutex found to be grandfathered',
				'src/MoreCode.php',
				43,
				false
			),
		];

		$notFileSpecificErrors = [
			'Orchestration was under quarantine',
		];

		$warnings = [
			'Technobabble detected',
		];

		return new AnalysisResult(
			$fileSpecificErrors,
			$notFileSpecificErrors,
			$warnings,
			false,
			false,
			'./jsontest.neon'
		);
	}

}
