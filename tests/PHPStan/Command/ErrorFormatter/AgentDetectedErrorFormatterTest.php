<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Override;
use PHPStan\Testing\ErrorFormatterTestCase;
use function putenv;

class AgentDetectedErrorFormatterTest extends ErrorFormatterTestCase
{

	#[Override]
	protected function setUp(): void
	{
		putenv('AI_AGENT');
		putenv('CURSOR_TRACE_ID');
		putenv('CURSOR_AGENT');
		putenv('GEMINI_CLI');
		putenv('CODEX_SANDBOX');
		putenv('AUGMENT_AGENT');
		putenv('OPENCODE_CLIENT');
		putenv('OPENCODE');
		putenv('CLAUDECODE');
		putenv('CLAUDE_CODE');
		putenv('REPL_ID');
	}

	#[Override]
	protected function tearDown(): void
	{
		putenv('AI_AGENT');
		putenv('CLAUDE_CODE');
	}

	public function testIsAgentDetectedReturnsFalse(): void
	{
		$formatter = new AgentDetectedErrorFormatter(new RawErrorFormatter());
		$this->assertFalse($formatter->isAgentDetected());
	}

	public function testIsAgentDetectedReturnsTrueWithAiAgent(): void
	{
		putenv('AI_AGENT=test');
		$formatter = new AgentDetectedErrorFormatter(new RawErrorFormatter());
		$this->assertTrue($formatter->isAgentDetected());
	}

	public function testIsAgentDetectedReturnsTrueWithClaudeCode(): void
	{
		putenv('CLAUDE_CODE=1');
		$formatter = new AgentDetectedErrorFormatter(new RawErrorFormatter());
		$this->assertTrue($formatter->isAgentDetected());
	}

	public function testFormatErrorsProducesRawOutput(): void
	{
		$formatter = new AgentDetectedErrorFormatter(new RawErrorFormatter());

		$exitCode = $formatter->formatErrors(
			$this->getAnalysisResult(1, 0),
			$this->getOutput(),
		);

		$this->assertSame(1, $exitCode);
		$this->assertSame(
			'/data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:4:Foo' . "\n",
			$this->getOutputContent(),
		);
	}

	public function testFormatErrorsNoErrors(): void
	{
		$formatter = new AgentDetectedErrorFormatter(new RawErrorFormatter());

		$exitCode = $formatter->formatErrors(
			$this->getAnalysisResult(0, 0),
			$this->getOutput(),
		);

		$this->assertSame(0, $exitCode);
		$this->assertSame('', $this->getOutputContent());
	}

}
