<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Nette\Utils\Json;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use function hash;
use function implode;

/**
 * @see https://docs.gitlab.com/ee/user/project/merge_requests/code_quality.html#implementing-a-custom-tool
 */
final class GitlabErrorFormatter implements ErrorFormatter
{

	public function __construct(private RelativePathHelper $relativePathHelper)
	{
	}

	public function formatErrors(AnalysisResult $analysisResult, Output $output): int
	{
		$errorsArray = [];

		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			$error = [
				'description' => $fileSpecificError->getMessage(),
				'fingerprint' => hash(
					'sha256',
					implode(
						[
							$fileSpecificError->getFile(),
							$fileSpecificError->getLine(),
							$fileSpecificError->getMessage(),
						],
					),
				),
				'severity' => $fileSpecificError->canBeIgnored() ? 'major' : 'blocker',
				'location' => [
					'path' => $this->relativePathHelper->getRelativePath($fileSpecificError->getFile()),
					'lines' => [
						'begin' => $fileSpecificError->getLine() ?? 0,
					],
				],
			];

			$errorsArray[] = $error;
		}

		foreach ($analysisResult->getNotFileSpecificErrors() as $notFileSpecificError) {
			$errorsArray[] = [
				'description' => $notFileSpecificError,
				'fingerprint' => hash('sha256', $notFileSpecificError),
				'severity' => 'major',
				'location' => [
					'path' => '',
					'lines' => [
						'begin' => 0,
					],
				],
			];
		}

		$json = Json::encode($errorsArray, Json::PRETTY);

		$output->writeRaw($json);

		return $analysisResult->hasErrors() ? 1 : 0;
	}

}
