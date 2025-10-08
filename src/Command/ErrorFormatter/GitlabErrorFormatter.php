<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Nette\Utils\Json;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\RelativePathHelper;
use function hash;
use function implode;

/**
 * @see https://docs.gitlab.com/ci/testing/code_quality#code-quality-report-format
 */
#[AutowiredService(name: 'errorFormatter.gitlab')]
final class GitlabErrorFormatter implements ErrorFormatter
{

	public function __construct(
		#[AutowiredParameter(ref: '@simpleRelativePathHelper')]
		private RelativePathHelper $relativePathHelper,
	)
	{
	}

	public function formatErrors(AnalysisResult $analysisResult, Output $output): int
	{
		$errorsArray = [];

		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			$error = [
				'description' => $fileSpecificError->getMessage(),
				// This field is intended to be shown in the GitLab code quality scan widget and mentioned in the GitLab docs.
				// However, due to a regression in GitLab, it is currently not shown - see https://gitlab.com/gitlab-org/gitlab/-/issues/578172.
				// There is no harm in having it here, and it allows users to work around the GitLab bug by post-processing the JSON file.
				// For example, they might prepend `check_name` to `description`, causing GitLab to show the error identifier as part of the message.
				// TODO: remove this comment once the GitLab bug is fixed
				'check_name' => $fileSpecificError->getIdentifier(),
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
