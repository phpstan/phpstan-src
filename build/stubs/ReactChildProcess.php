<?php

namespace React\ChildProcess;

use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;

class Process
{

	/**
	 * @var WritableStreamInterface
	 */
	public $stdin;

	/**
	 * @var ReadableStreamInterface
	 */
	public $stdout;

	/**
	 * @var ReadableStreamInterface
	 */
	public $stderr;

	/** @var array{WritableStreamInterface, ReadableStreamInterface, ReadableStreamInterface} */
	public $pipes = array();

}
