<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use function file_exists;
use function getenv;
use function is_string;
use function trim;

final class AgentDetector
{

	public static function isRunningInAgent(): bool
	{
		// Copyright (c) Pushpak Chhajed pushpak1300@gmail.com
		// from https://github.com/shipfastlabs/agent-detector/blob/98766473b2dfe183b0c2605ceb04e587a78d1872/src/AgentDetector.php

		$aiAgent = getenv('AI_AGENT');
		if (is_string($aiAgent) && trim($aiAgent) !== '') {
			return true;
		}

		if (getenv('CURSOR_TRACE_ID') !== false) {
			return true;
		}

		if (getenv('CURSOR_AGENT') !== false) {
			return true;
		}

		if (getenv('GEMINI_CLI') !== false) {
			return true;
		}

		if (getenv('CODEX_SANDBOX') !== false) {
			return true;
		}

		if (getenv('AUGMENT_AGENT') !== false) {
			return true;
		}

		if (getenv('OPENCODE_CLIENT') !== false || getenv('OPENCODE') !== false) {
			return true;
		}

		if (getenv('CLAUDECODE') !== false || getenv('CLAUDE_CODE') !== false) {
			return true;
		}

		if (getenv('REPL_ID') !== false) {
			return true;
		}

		if (@file_exists('/opt/.devin')) {
			return true;
		}

		return false;
	}

}
