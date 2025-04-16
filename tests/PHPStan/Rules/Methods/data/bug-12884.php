<?php declare(strict_types = 1);

namespace Bug12884;

class LogLevel
{
	const EMERGENCY = 'emergency';
	const ALERT = 'alert';
	const CRITICAL = 'critical';
	const ERROR = 'error';
	const WARNING = 'warning';
	const NOTICE = 'notice';
	const INFO = 'info';
	const DEBUG = 'debug';
}

class HelloWorld
{
	/** @param list<LogLevel::*> $levels */
	public function __construct(
		private LoggerInterface $logger,
		public array $levels = []
	) {}

	public function log(string $level, string $message): void
	{
		if (!in_array($level, $this->levels, true)) {
			$level = LogLevel::INFO;
		}
		$this->logger->log($level, $message);
	}
}

interface LoggerInterface
{
	/**
	 * @param 'emergency'|'alert'|'critical'|'error'|'warning'|'notice'|'info'|'debug' $level
	 */
	public function log($level, string|\Stringable $message): void;
}
