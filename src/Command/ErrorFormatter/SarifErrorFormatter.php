<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Nette\Utils\Json;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\Internal\ComposerHelper;

class SarifErrorFormatter implements ErrorFormatter
{

	const URI_BASE_ID = 'WORKINGDIR';

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

		$originalUriBaseIds = [
			self::URI_BASE_ID => 'file://' . getcwd() . '/',
		];

		$results = [];

		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			$result = [
				'message' => [
					'level' => 'error',
					'text' => $fileSpecificError->getMessage(),
				],
				'locations' => [
					[
						'physicalLocation' => [
							'artifactLocation' => [
								'uri' => $this->pathToArtifactLocation($fileSpecificError->getFilePath()),
								'uriBaseId' => self::URI_BASE_ID,
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
					// 'metadata' => $fileSpecificError->getMetadata(),
				],
			];

			if ($fileSpecificError->getTip() !== null) {
				$result['properties']['tip'] = $fileSpecificError->getTip();
			}

			$results[] = $result;
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
					'originalUriBaseIds' => $originalUriBaseIds,
					'results' => $results,
				],
			],
		];

		$json = Json::encode($sarif, $this->pretty ? Json::PRETTY : 0);

		$output->writeRaw($json);

		return $analysisResult->hasErrors() ? 1 : 0;
	}

	private function pathToArtifactLocation(string $path): string
	{
		$workingDir = getcwd();
		if ($workingDir === false) {
			$workingDir = '.';
		}

		if (substr($path, 0, strlen($workingDir)) === $workingDir) {
			return substr($path, strlen($workingDir) + 1);
		}

		return $path;
	}
}
