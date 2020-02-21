<?php declare(strict_types = 1);

namespace PHPStan\Command;

class CaptureOutput implements Output
{

	/** @var string */
	private $result = '';

	public function writeFormatted(string $message): void
	{
		throw new \Exception('Not implemented');
	}

	public function writeLineFormatted(string $message): void
	{
		throw new \Exception('Not implemented');
	}

	public function writeRaw(string $message): void
	{
		$this->result .= $message;
	}

	public function getStyle(): OutputStyle
	{
		throw new \Exception('Not implemented');
	}

	public function getResult(): string
	{
		return $this->result;
	}

}
