<?php declare(strict_types = 1);

namespace PHPStan\Command;

/**
 * Test helper to capture and verify output
 */
class CaptureOutput implements Output
{

	/** @var string */
	private $result = '';

	public function writeFormatted(string $message): void
	{
		$this->result .= $message;
	}

	public function writeLineFormatted(string $message): void
	{
		$this->result .= $message . "\n";
	}

	public function writeRaw(string $message): void
	{
		$this->result .= $message;
	}

	public function getStyle(): OutputStyle
	{
		return new class implements OutputStyle {

			public function title(string $message): void
			{
			}

			public function section(string $message): void
			{
			}

			public function listing(array $elements): void
			{
			}

			public function success(string $message): void
			{
			}

			public function error(string $message): void
			{
			}

			public function warning(string $message): void
			{
			}

			public function note(string $message): void
			{
			}

			public function caution(string $message): void
			{
			}

			public function table(array $headers, array $rows): void
			{
			}

			public function newLine(int $count = 1): void
			{
			}

			public function progressStart(int $max = 0): void
			{
			}

			public function progressAdvance(int $step = 1): void
			{
			}

			public function progressFinish(): void
			{
			}

		};
	}

	public function getResult(): string
	{
		return $this->result;
	}

}
