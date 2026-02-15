<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use HelgeSverre\Toon\Toon;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use Symfony\Component\Console\Formatter\OutputFormatter;
use function count;

final class ToonErrorFormatter implements ErrorFormatter
{

	public function formatErrors(AnalysisResult $analysisResult, Output $output): int
	{
		$errorsArray = [
			'totals' => [
				'errors' => count($analysisResult->getNotFileSpecificErrors()),
				'file_errors' => count($analysisResult->getFileSpecificErrors()),
			],
			'files' => [],
			'errors' => [],
		];

		$tipFormatter = new OutputFormatter(false);

		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			$file = $fileSpecificError->getFile();
			if (!isset($errorsArray['files'][$file])) {
				$errorsArray['files'][$file] = [
					'errors' => 0,
					'messages' => [],
				];
			}
			$errorsArray['files'][$file]['errors']++;

			$message = [
				'message' => $fileSpecificError->getMessage(),
				'line' => $fileSpecificError->getLine(),
				'ignorable' => $fileSpecificError->canBeIgnored(),
			];

			if ($fileSpecificError->getTip() !== null) {
				$message['tip'] = $tipFormatter->format($fileSpecificError->getTip());
			}

			if ($fileSpecificError->getIdentifier() !== null) {
				$message['identifier'] = $fileSpecificError->getIdentifier();
			}

			$errorsArray['files'][$file]['messages'][] = $message;
		}

		foreach ($analysisResult->getNotFileSpecificErrors() as $notFileSpecificError) {
			$errorsArray['errors'][] = $notFileSpecificError;
		}

		$toon = Toon::encode($errorsArray);

		$output->writeRaw($toon);

		return $analysisResult->hasErrors() ? 1 : 0;
	}

}
