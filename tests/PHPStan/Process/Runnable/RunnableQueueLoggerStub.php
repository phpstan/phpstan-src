<?php declare(strict_types = 1);

namespace PHPStan\Process\Runnable;

class RunnableQueueLoggerStub implements RunnableQueueLogger
{

	/** @var string[] */
	private $messages = [];

	/**
	 * @return string[]
	 */
	public function getMessages(): array
	{
		return $this->messages;
	}

	public function log(string $message): void
	{
		$this->messages[] = $message;
	}

}
