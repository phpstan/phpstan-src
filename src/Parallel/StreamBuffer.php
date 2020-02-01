<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use React\Stream\ReadableStreamInterface;

class StreamBuffer
{

	/** @var string */
	private $buffer = '';

	public function __construct(ReadableStreamInterface $stream)
	{
		$stream->on('data', function (string $chunk): void {
			$this->buffer .= $chunk;
		});
	}

	public function getBuffer(): string
	{
		return $this->buffer;
	}

}
