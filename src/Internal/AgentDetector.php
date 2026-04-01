<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use function file_exists;
use function getenv;
use function trim;

final class AgentDetector
{

	public const ENV_VARS = [
		'AUGMENT_AGENT',
		'AMP_CURRENT_THREAD_ID',
		'AI_AGENT',
		'CURSOR_TRACE_ID',
		'CURSOR_AGENT',
		'GEMINI_CLI',
		'CODEX_SANDBOX',
		'CODEX_THREAD_ID',
		'AUGMENT_AGENT',
		'OPENCODE_CLIENT',
		'OPENCODE',
		'CLAUDECODE',
		'CLAUDE_CODE',
		'REPL_ID',
	];

	public static function isRunningInAgent(): bool
	{
		// Copyright (c) Pushpak Chhajed pushpak1300@gmail.com
		// from https://github.com/shipfastlabs/agent-detector/blob/98766473b2dfe183b0c2605ceb04e587a78d1872/src/AgentDetector.php

		foreach (self::ENV_VARS as $envVar) {
			$value = getenv($envVar);
			if ($value === false) {
				continue;
			}

			if ($envVar === 'AI_AGENT' && trim($value) === '') {
				continue;
			}

			return true;
		}

		if (@file_exists('/opt/.devin')) {
			return true;
		}

		return false;
	}

}
