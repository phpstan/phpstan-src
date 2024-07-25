<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Nette\Utils\Json;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use Symfony\Component\Console\Formatter\OutputFormatter;
use function array_key_exists;
use function count;

final class JsonErrorFormatter implements ErrorFormatter
{

	public function __construct(private bool $pretty)
	{
	}

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
			if (!array_key_exists($file, $errorsArray['files'])) {
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

		$json = Json::encode($errorsArray, $this->pretty ? Json::PRETTY : 0);

		$output->writeRaw($json);

		return $analysisResult->hasErrors() ? 1 : 0;
	}

}
