<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use OndraM\CiDetector\CiDetector;
use OndraM\CiDetector\Exception\CiNotDetectedException;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;

/**
 * @api
 * @final
 */
class CiDetectedErrorFormatter implements ErrorFormatter
{

	public function __construct(
		private GithubErrorFormatter $githubErrorFormatter,
		private TeamcityErrorFormatter $teamcityErrorFormatter,
	)
	{
	}

	public function formatErrors(AnalysisResult $analysisResult, Output $output): int
	{
		$ciDetector = new CiDetector();

		try {
			$ci = $ciDetector->detect();
			if ($ci->getCiName() === CiDetector::CI_GITHUB_ACTIONS) {
				return $this->githubErrorFormatter->formatErrors($analysisResult, $output);
			} elseif ($ci->getCiName() === CiDetector::CI_TEAMCITY) {
				return $this->teamcityErrorFormatter->formatErrors($analysisResult, $output);
			}
		} catch (CiNotDetectedException) {
			// pass
		}

		if (!$analysisResult->hasErrors() && !$analysisResult->hasWarnings()) {
			return 0;
		}

		return $analysisResult->getTotalErrorsCount() > 0 ? 1 : 0;
	}

}
