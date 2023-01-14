<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

// use Jean85\PrettyVersions;
use Nette\Utils\Json;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;

class SarifErrorFormatter implements ErrorFormatter
{

	public function __construct(private bool $pretty)
	{
	}

	public function formatErrors(AnalysisResult $analysisResult, Output $output): int
	{
		// $phpstanVersion = PrettyVersions::getVersion('phpstan/phpstan')->getPrettyVersion();
		$phpstanVersion = '1.9.11';

		$tool = [
			'driver' => [
				'name' => 'PHPStan',
				'fullName' => 'PHP Static Analysis Tool',
				'informationUri' => 'https://phpstan.org',
				'semanticVersion' => $phpstanVersion,
			],
		];

		$results = [];

		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			$results[] = [
				'message' => [
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
					'canBeIgnored' => $fileSpecificError->canBeIgnored(),
					// 'identifier' => $fileSpecificError->getIdentifier(),
					// 'tip' => $fileSpecificError->getTip(),
					// 'metadata' => $fileSpecificError->getMetadata(),
				],
			];
		}

		foreach ($analysisResult->getNotFileSpecificErrors() as $notFileSpecificError) {
			$results[] = [
				'message' => [
					'text' => $notFileSpecificError,
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
