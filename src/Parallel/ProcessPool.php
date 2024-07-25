<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use PHPStan\ShouldNotHappenException;
use React\Socket\TcpServer;
use function array_key_exists;
use function array_keys;
use function count;
use function sprintf;

final class ProcessPool
{

	/** @var array<string, Process> */
	private array $processes = [];

	/** @var callable(): void */
	private $onServerClose;

	/**
	 * @param callable(): void $onServerClose
	 */
	public function __construct(private TcpServer $server, callable $onServerClose)
	{
		$this->onServerClose = $onServerClose;
	}

	public function getProcess(string $identifier): Process
	{
		if (!array_key_exists($identifier, $this->processes)) {
			throw new ShouldNotHappenException(sprintf('Process %s not found.', $identifier));
		}

		return $this->processes[$identifier];
	}

	public function attachProcess(string $identifier, Process $process): void
	{
		$this->processes[$identifier] = $process;
	}

	public function tryQuitProcess(string $identifier): void
	{
		if (!array_key_exists($identifier, $this->processes)) {
			return;
		}

		$this->quitProcess($identifier);
	}

	private function quitProcess(string $identifier): void
	{
		$process = $this->getProcess($identifier);
		$process->quit();
		unset($this->processes[$identifier]);
		if (count($this->processes) !== 0) {
			return;
		}

		$this->server->close();
		$callback = $this->onServerClose;
		$callback();
	}

	public function quitAll(): void
	{
		foreach (array_keys($this->processes) as $identifier) {
			$this->quitProcess($identifier);
		}
	}

}
