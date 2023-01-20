<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Nette\Utils\Json;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use PHPStan\Internal\ComposerHelper;

class SarifErrorFormatter implements ErrorFormatter
{

	private const URI_BASE_ID = 'WORKINGDIR';

	public function __construct(
		private RelativePathHelper $relativePathHelper,
		private string $currentWorkingDirectory,
		private bool $pretty,
	)
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
				'rules' => [],
			],
		];

		$originalUriBaseIds = [
			self::URI_BASE_ID => [
				'uri' => 'file://' . $this->currentWorkingDirectory . '/',
			],
		];

		$results = [];

		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			$result = [
				'level' => 'error',
				'message' => [
					'text' => $fileSpecificError->getMessage(),
				],
				'locations' => [
					[
						'physicalLocation' => [
							'artifactLocation' => [
								'uri' => $this->relativePathHelper->getRelativePath($fileSpecificError->getFile()),
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
				'level' => 'error',
				'message' => [
					'text' => $notFileSpecificError,
				],
			];
		}

		foreach ($analysisResult->getWarnings() as $warning) {
			$results[] = [
				'level' => 'warning',
				'message' => [
					'text' => $warning,
				],
			];
		}

		$sarif = [
			'$schema' => 'https://json.schemastore.org/sarif-2.1.0.json',
			'version' => '2.1.0',
			'runs' => [
				[
					'tool' => $tool,
					'originalUriBaseIds' => $originalUriBaseIds,
					'results' => $results,
				],
			],
		];

		$encodeOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | ($this->pretty ? JSON_PRETTY_PRINT : 0);

		$json = json_encode($sarif, $encodeOptions);

		$output->writeRaw($json);

		return $analysisResult->hasErrors() ? 1 : 0;
	}
}
