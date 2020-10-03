<?php declare(strict_types = 1);

namespace PHPStan\Process\Runnable;

interface RunnableQueueLogger
{

	public function log(string $message): void;

}
