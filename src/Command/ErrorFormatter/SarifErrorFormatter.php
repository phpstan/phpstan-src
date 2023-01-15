<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Nette\Utils\Json;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\Internal\ComposerHelper;

class SarifErrorFormatter implements ErrorFormatter
{

	public function __construct(private bool $pretty)
	{
	}

	public function formatErrors(AnalysisResult $analysisResult, Output $output): int
	{
		$phpstanVersion = ComposerHelper::getPhpStanVersion();

		$tool = [
			'driver' => [
				'name' => 'PHPStan',
				'fullName' => 'PHP Static Analysis Tool',
				'informationUri' => 'https://phpstan.org',
				'version' => $phpstanVersion,
				'semanticVersion' => $phpstanVersion,
			],
		];

		$results = [];

		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			$results[] = [
				'message' => [
					'level' => 'error',
					'text' => $fileSpecificError->getMessage(),
				],
				'locations' => [
					[
						'physicalLocation' => [
							'artifactLocation' => [
								'uri' => 'file://' . $fileSpecificError->getFile(),
							],
							'region' => [
								'startLine' => $fileSpecificError->getLine(),
							],
						],
					],
				],
				'properties' => [
					'ignorable' => $fileSpecificError->canBeIgnored(),
					// 'identifier' => $fileSpecificError->getIdentifier(),
					// 'tip' => $fileSpecificError->getTip(),
					// 'metadata' => $fileSpecificError->getMetadata(),
				],
			];
		}

		foreach ($analysisResult->getNotFileSpecificErrors() as $notFileSpecificError) {
			$results[] = [
				'message' => [
					'level' => 'error',
					'text' => $notFileSpecificError,
				],
			];
		}

		foreach ($analysisResult->getWarnings() as $warning) {
			$results[] = [
				'message' => [
					'level' => 'warning',
					'text' => $warning,
				],
			];
		}

		$sarif = [
			'$schema' => 'https://json.schemastore.org/sarif-2.1.0',
			'version' => '2.1.0',
			'runs' => [
				[
					'tool' => $tool,
					'results' => $results,
				],
			],
		];

		$json = Json::encode($sarif, $this->pretty ? Json::PRETTY : 0);

		$output->writeRaw($json);

		return $analysisResult->hasErrors() ? 1 : 0;
	}

}
