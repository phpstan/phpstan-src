<?php

namespace Bug6448;

class HelloWorld
{
	/** @var resource */
	private $stream;

	/**
	 * @param resource $stream
	 */
	public function __construct($stream)
	{
		$this->stream = $stream;
	}

	/**
	 * @param array<string> $fields
	 */
	public function sayHello(
		array $fields,
		string $delimiter,
		string $enclosure,
		string $escape,
		string $eol
	): int|false {
		return fputcsv($this->stream, $fields, $delimiter, $enclosure, $escape, $eol);
	}
}
