<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorsConsoleStyle;
use PHPStan\Command\Output;
use PHPStan\Command\Symfony\SymfonyOutput;
use PHPStan\Command\Symfony\SymfonyStyle;
use PHPStan\ShouldNotHappenException;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;
use function array_map;
use function array_slice;
use function explode;
use function fopen;
use function implode;
use function rewind;
use function rtrim;
use function stream_get_contents;

abstract class ErrorFormatterTestCase extends PHPStanTestCase
{

	protected const DIRECTORY_PATH = '/data/folder/with space/and unicode 😃/project';

	private const KIND_DECORATED = 'decorated';
	private const KIND_PLAIN = 'plain';

	/** @var array<string, StreamOutput> */
	private array $outputStream = [];

	/** @var array<string, Output> */
	private array $output = [];

	private function getOutputStream(bool $decorated = false): StreamOutput
	{
		$kind = $decorated ? self::KIND_DECORATED : self::KIND_PLAIN;
		if (!isset($this->outputStream[$kind])) {
			$resource = fopen('php://memory', 'w', false);
			if ($resource === false) {
				throw new ShouldNotHappenException();
			}
			$this->outputStream[$kind] = new StreamOutput($resource, StreamOutput::VERBOSITY_NORMAL, $decorated);
		}

		return $this->outputStream[$kind];
	}

	protected function getOutput(bool $decorated = false): Output
	{
		$kind = $decorated ? self::KIND_DECORATED : self::KIND_PLAIN;
		if (!isset($this->output[$kind])) {
			$outputStream = $this->getOutputStream($decorated);
			$errorConsoleStyle = new ErrorsConsoleStyle(new StringInput(''), $outputStream);
			$this->output[$kind] = new SymfonyOutput($outputStream, new SymfonyStyle($errorConsoleStyle));
		}

		return $this->output[$kind];
	}

	protected function getOutputContent(bool $decorated = false): string
	{
		rewind($this->getOutputStream($decorated)->getStream());

		$contents = stream_get_contents($this->getOutputStream($decorated)->getStream());
		if ($contents === false) {
			throw new ShouldNotHappenException();
		}

		return $this->rtrimMultiline($contents);
	}

	protected function getAnalysisResult(int $numFileErrors, int $numGenericErrors): AnalysisResult
	{
		if ($numFileErrors > 5 || $numFileErrors < 0 || $numGenericErrors > 2 || $numGenericErrors < 0) {
			throw new ShouldNotHappenException();
		}

		$fileErrors = array_slice([
			new Error('Foo', self::DIRECTORY_PATH . '/folder with unicode 😃/file name with "spaces" and unicode 😃.php', 4),
			new Error('Foo', self::DIRECTORY_PATH . '/foo.php', 1),
			new Error("Bar\nBar2", self::DIRECTORY_PATH . '/foo.php', 5, true, null, null, 'a tip'),
			new Error("Bar\nBar2", self::DIRECTORY_PATH . '/folder with unicode 😃/file name with "spaces" and unicode 😃.php', 2),
			new Error("Bar\nBar2", self::DIRECTORY_PATH . '/foo.php', null),
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
			[],
			false,
			null,
			true,
			0,
			false,
			[],
		);
	}

	private function rtrimMultiline(string $output): string
	{
		$result = array_map(static fn (string $line): string => rtrim($line, " \r\n"), explode("\n", $output));

		return implode("\n", $result);
	}

}
