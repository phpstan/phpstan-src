<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use AgentDetector\AgentDetector;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;

/**
 * @api
 */
#[AutowiredService(as: AgentDetectedErrorFormatter::class)]
final class AgentDetectedErrorFormatter implements ErrorFormatter
{

	public function __construct(
		#[AutowiredParameter(ref: '@errorFormatter.json')]
		private JsonErrorFormatter $jsonErrorFormatter,
	)
	{
	}

	public function isAgentDetected(): bool
	{
		return AgentDetector::detect()->isAgent;
	}

	public function formatErrors(AnalysisResult $analysisResult, Output $output): int
	{
		return $this->jsonErrorFormatter->formatErrors($analysisResult, $output);
	}

}
