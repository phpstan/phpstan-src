<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use HelgeSverre\Toon\Toon;
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
	}

	public function testIsAgentDetectedReturnsFalse(): void
	{
		$formatter = new AgentDetectedErrorFormatter(new ToonErrorFormatter());
		$this->assertFalse($formatter->isAgentDetected());
	}

	public function testIsAgentDetectedReturnsTrueWithAiAgent(): void
	{
		putenv('AI_AGENT=test');
		$formatter = new AgentDetectedErrorFormatter(new ToonErrorFormatter());
		$this->assertTrue($formatter->isAgentDetected());
	}

	public function testIsAgentDetectedReturnsTrueWithClaudeCode(): void
	{
		putenv('CLAUDE_CODE=1');
		$formatter = new AgentDetectedErrorFormatter(new ToonErrorFormatter());
		$this->assertTrue($formatter->isAgentDetected());
	}

	public function testFormatErrorsProducesToonOutput(): void
	{
		$formatter = new AgentDetectedErrorFormatter(new ToonErrorFormatter());

		$exitCode = $formatter->formatErrors(
			$this->getAnalysisResult(1, 0),
			$this->getOutput(),
		);

		$this->assertSame(1, $exitCode);

		$expectedData = [
			'totals' => [
				'errors' => 0,
				'file_errors' => 1,
			],
			'files' => [
				'/data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php' => [
					'errors' => 1,
					'messages' => [
						[
							'message' => 'Foo',
							'line' => 4,
							'ignorable' => true,
						],
					],
				],
			],
			'errors' => [],
		];

		$this->assertSame(Toon::encode($expectedData), $this->getOutputContent());
	}

	public function testFormatErrorsNoErrors(): void
	{
		$formatter = new AgentDetectedErrorFormatter(new ToonErrorFormatter());

		$exitCode = $formatter->formatErrors(
			$this->getAnalysisResult(0, 0),
			$this->getOutput(),
		);

		$this->assertSame(0, $exitCode);

		$expectedData = [
			'totals' => [
				'errors' => 0,
				'file_errors' => 0,
			],
			'files' => [],
			'errors' => [],
		];

		$this->assertSame(Toon::encode($expectedData), $this->getOutputContent());
	}

}
