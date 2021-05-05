<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorsConsoleStyle;
use PHPStan\Command\Output;
use PHPStan\Command\Symfony\SymfonyOutput;
use PHPStan\Command\Symfony\SymfonyStyle;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;

abstract class ErrorFormatterTestCase extends \PHPStan\Testing\TestCase
{

	protected const DIRECTORY_PATH = '/data/folder/with space/and unicode 😃/project';

	private ?StreamOutput $outputStream = null;

	private ?Output $output = null;

	private function getOutputStream(): StreamOutput
	{
		if (PHP_VERSION_ID >= 80000 && DIRECTORY_SEPARATOR === '\\') {
			$this->markTestSkipped('Skipped because of https://github.com/symfony/symfony/issues/37508');
		}
		if ($this->outputStream === null) {
			$resource = fopen('php://memory', 'w', false);
			if ($resource === false) {
				throw new \PHPStan\ShouldNotHappenException();
			}
			$this->outputStream = new StreamOutput($resource);
		}

		return $this->outputStream;
	}

	protected function getOutput(): Output
	{
		if ($this->output === null) {
			$errorConsoleStyle = new ErrorsConsoleStyle(new StringInput(''), $this->getOutputStream());
			$this->output = new SymfonyOutput($this->getOutputStream(), new SymfonyStyle($errorConsoleStyle));
		}

		return $this->output;
	}

	protected function getOutputContent(): string
	{
		rewind($this->getOutputStream()->getStream());

		$contents = stream_get_contents($this->getOutputStream()->getStream());
		if ($contents === false) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		return $this->rtrimMultiline($contents);
	}

	protected function getAnalysisResult(int $numFileErrors, int $numGenericErrors): AnalysisResult
	{
		if ($numFileErrors > 4 || $numFileErrors < 0 || $numGenericErrors > 2 || $numGenericErrors < 0) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$fileErrors = array_slice([
			new Error('Foo', self::DIRECTORY_PATH . '/folder with unicode 😃/file name with "spaces" and unicode 😃.php', 4),
			new Error('Foo', self::DIRECTORY_PATH . '/foo.php', 1),
			new Error("Bar\nBar2", self::DIRECTORY_PATH . '/foo.php', 5),
			new Error("Bar\nBar2", self::DIRECTORY_PATH . '/folder with unicode 😃/file name with "spaces" and unicode 😃.php', 2),
		], 0, $numFileErrors);

		$genericErrors = array_slice([
			'first generic error',
			'second generic error',
		], 0, $numGenericErrors);

		return new AnalysisResult(
			$fileErrors,
			$genericErrors,
			[],
			[],
			false,
			null,
			true
		);
	}

	private function rtrimMultiline(string $output): string
	{
		$result = array_map(static function (string $line): string {
			return rtrim($line, " \r\n");
		}, explode("\n", $output));

		return implode("\n", $result);
	}

}
