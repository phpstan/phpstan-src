<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use function file_exists;
use function getenv;
use function is_string;
use function trim;

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
		$aiAgent = getenv('AI_AGENT');
		if (is_string($aiAgent) && trim($aiAgent) !== '') {
			return true;
		}

		return getenv('CURSOR_TRACE_ID') !== false
			|| getenv('CURSOR_AGENT') !== false
			|| getenv('GEMINI_CLI') !== false
			|| getenv('CODEX_SANDBOX') !== false
			|| getenv('AUGMENT_AGENT') !== false
			|| getenv('OPENCODE_CLIENT') !== false
			|| getenv('OPENCODE') !== false
			|| getenv('CLAUDECODE') !== false
			|| getenv('CLAUDE_CODE') !== false
			|| getenv('REPL_ID') !== false
			|| file_exists('/opt/.devin');
	}

	public function formatErrors(AnalysisResult $analysisResult, Output $output): int
	{
		return $this->jsonErrorFormatter->formatErrors($analysisResult, $output);
	}

}
