<?php declare(strict_types = 1);

namespace PHPStan\Command;

use function getenv;
use function is_string;
use function trim;

final class AgentDetector
{

	public static function isAgentDetected(): bool
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
			|| getenv('REPLIT_AGENT') !== false;
	}

}
