<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Override;
use PHPStan\Internal\AgentDetector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;
use function fclose;
use function fopen;
use function getenv;
use function putenv;
use function rewind;
use function rtrim;
use function str_replace;
use function stream_get_contents;

class ErrorsConsoleStyleTest extends TestCase
{

	/** @var array<string, string|false> */
	private array $originalEnvVars = [];

	#[Override]
	protected function setUp(): void
	{
		foreach ([...AgentDetector::ENV_VARS, 'GITHUB_ACTIONS'] as $var) {
			$this->originalEnvVars[$var] = getenv($var);
			putenv($var);
		}
	}

	#[Override]
	protected function tearDown(): void
	{
		foreach ($this->originalEnvVars as $var => $value) {
			putenv($var . ($value !== false ? '=' . $value : ''));
		}
	}

	public function testProgressOutputInAgentDoesNotOverwrite(): void
	{
		$agentOutput = $this->renderProgressOutput(true);
		$regularOutput = $this->renderProgressOutput(false);

		self::assertSame(
			rtrim(<<<'EOT'
				 0/2 [>---------------------------]   0%
				 2/2 [============================] 100%
				EOT),
			$agentOutput,
		);
		self::assertSame(
			" 0/2 [>---------------------------]   0%\033[1G\033[2K 2/2 [============================] 100%",
			$regularOutput,
		);
	}

	private function renderProgressOutput(bool $isAgent): string
	{
		if ($isAgent) {
			putenv('AI_AGENT=1');
		} else {
			putenv('AI_AGENT');
		}

		$stream = fopen('php://memory', 'w+');
		self::assertNotFalse($stream);

		$output = new StreamOutput($stream, StreamOutput::VERBOSITY_NORMAL, true);
		$errorStyle = new ErrorsConsoleStyle(new StringInput(''), $output);

		$progressBar = $errorStyle->createProgressBar(2);
		$progressBar->setBarCharacter('=');
		$progressBar->setEmptyBarCharacter('-');
		$progressBar->setProgressCharacter('>');
		$progressBar->setProgress(0);
		$progressBar->display();
		$progressBar->setProgress(2);
		$progressBar->display();

		rewind($stream);
		$contents = stream_get_contents($stream);
		fclose($stream);

		self::assertIsString($contents);

		return str_replace(["\r\n", "\r"], "\n", $contents);
	}

}
