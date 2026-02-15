<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Override;
use PHPUnit\Framework\TestCase;
use function putenv;

class AgentDetectorTest extends TestCase
{

	/** @var list<string> */
	private const ENV_VARS = [
		'AI_AGENT',
		'CURSOR_TRACE_ID',
		'CURSOR_AGENT',
		'GEMINI_CLI',
		'CODEX_SANDBOX',
		'AUGMENT_AGENT',
		'OPENCODE_CLIENT',
		'OPENCODE',
		'CLAUDECODE',
		'CLAUDE_CODE',
		'REPLIT_AGENT',
	];

	#[Override]
	protected function setUp(): void
	{
		foreach (self::ENV_VARS as $var) {
			putenv($var);
		}
	}

	#[Override]
	protected function tearDown(): void
	{
		foreach (self::ENV_VARS as $var) {
			putenv($var);
		}
	}

	public function testReturnsFalseWithNoEnvVars(): void
	{
		$this->assertFalse(AgentDetector::isAgentDetected());
	}

	public function testReturnsTrueWithAiAgent(): void
	{
		putenv('AI_AGENT=test');
		$this->assertTrue(AgentDetector::isAgentDetected());
	}

	public function testReturnsFalseWithEmptyAiAgent(): void
	{
		putenv('AI_AGENT=');
		$this->assertFalse(AgentDetector::isAgentDetected());
	}

	public function testReturnsTrueWithClaudeCode(): void
	{
		putenv('CLAUDE_CODE=1');
		$this->assertTrue(AgentDetector::isAgentDetected());
	}

	public function testReturnsTrueWithCursorTraceId(): void
	{
		putenv('CURSOR_TRACE_ID=abc');
		$this->assertTrue(AgentDetector::isAgentDetected());
	}

	public function testReturnsTrueWithReplitAgent(): void
	{
		putenv('REPLIT_AGENT=1');
		$this->assertTrue(AgentDetector::isAgentDetected());
	}

}
