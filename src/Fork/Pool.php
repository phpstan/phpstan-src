<?php declare(strict_types = 1);

namespace PHPStan\Fork;

/**
 * Largely taken from Psalm:
 * https://github.com/vimeo/psalm/blob/255ffa05ead00878ba36b60d7dc013c29e88f4cc/src/Psalm/Internal/Fork/Pool.php
 *
 * which was:
 *
 * Adapted with relatively few changes from
 * https://github.com/etsy/phan/blob/1ccbe7a43a6151ca7c0759d6c53e2c3686994e53/src/Phan/ForkPool.php
 *
 * Authors: https://github.com/morria, https://github.com/TysonAndre
 *
 * Fork off to n-processes and divide up tasks between
 * each process.
 */
class Pool
{

	private const EXIT_SUCCESS = 1;
	private const EXIT_FAILURE = 0;

	/** @var int[] */
	private $childPidList = [];

	/** @var resource[] */
	private $readStreams = [];

	/** @var bool */
	private $didHaveError = false;

	/** @var ?\Closure(mixed): void */
	private $taskDoneClosure;

	public const MAC_PCRE_MESSAGE = 'Mac users: pcre.jit is set to 1 in your PHP config.' . PHP_EOL
		. 'The pcre jit is known to cause segfaults in PHP 7.3 on Macs, and Psalm' . PHP_EOL
		. 'will not execute in threaded mode to avoid indecipherable errors.' . PHP_EOL
		. 'Consider adding pcre.jit=0 to your PHP config.' . PHP_EOL
		. 'Relevant info: https://bugs.php.net/bug.php?id=77260';

	/**
	 * @param array<int, array<int, mixed>> $processTaskDataIterator
	 * An array of task data items to be divided up among the
	 * workers. The size of this is the number of forked processes.
	 * @param \Closure $startupClosure
	 * A closure to execute upon starting a child
	 * @param \Closure(int, mixed): mixed $taskClosure
	 * A method to execute on each task data.
	 * This closure must return an array (to be gathered).
	 * @param \Closure(): mixed $shutdownClosure
	 * A closure to execute upon shutting down a child
	 * @param (\Closure(string, mixed): void)|null $taskDoneClosure
	 * A closure to execute when a task is done
	 */
	public function __construct(
		array $processTaskDataIterator,
		\Closure $startupClosure,
		\Closure $taskClosure,
		\Closure $shutdownClosure,
		?\Closure $taskDoneClosure = null
	)
	{
		$poolSize = count($processTaskDataIterator);
		$this->taskDoneClosure = $taskDoneClosure;

		//\assert(
		//	$poolSize > 1,
		//	'The pool size must be >= 2 to use the fork pool.'
		//);

		if (!extension_loaded('pcntl')) {
			echo 'The pcntl extension must be loaded in order for Psalm to be able to use multiple processes.'
				. PHP_EOL;
			exit(1);
		}

		if (ini_get('pcre.jit') === '1'
			&& \PHP_OS === 'Darwin'
			&& version_compare(PHP_VERSION, '7.3.0') >= 0
		) {
			die(
				self::MAC_PCRE_MESSAGE . PHP_EOL
			);
		}

		// We'll keep track of if this is the parent process
		// so that we can tell who will be doing the waiting
		$isParent = false;

		$sockets = [];

		// Fork as many times as requested to get the given
		// pool size
		$procId = 0;
		for (; $procId < $poolSize; ++$procId) {
			// Create an IPC socket pair.
			$sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
			if ($sockets === false) {
				error_log('unable to create stream socket pair');
				exit(self::EXIT_FAILURE);
			}

			// Fork
			$pid = pcntl_fork();
			if ($pid < 0) {
				error_log(posix_strerror(posix_get_last_error()));
				exit(self::EXIT_FAILURE);
			}

			// Parent
			if ($pid > 0) {
				$isParent = true;
				$this->childPidList[] = $pid;
				$this->readStreams[] = self::streamForParent($sockets);
				continue;
			}

			// Child
			$isParent = false;
			break;
		}

		// If we're the parent, return
		if ($isParent) {
			return;
		}

		// Get the write stream for the child.
		$writeStream = self::streamForChild($sockets);

		// Execute anything the children wanted to execute upon
		// starting up
		$startupClosure();

		// Get the work for this process
		$taskDataIterator = array_values($processTaskDataIterator)[$procId];

		$taskDoneBuffer = '';

		foreach ($taskDataIterator as $i => $taskData) {
			$taskResult = $taskClosure($i, $taskData);
			$taskDoneMessage = new ForkTaskDoneMessage($taskData, $taskResult);
			$serializedMessage = $taskDoneBuffer . base64_encode(serialize($taskDoneMessage)) . "\n";

			if (strlen($serializedMessage) > 200) {
				$bytesWritten = @fwrite($writeStream, $serializedMessage);
				$bytesWritten = $bytesWritten !== false ? $bytesWritten : 0;

				if (strlen($serializedMessage) !== $bytesWritten) {
					$taskDoneBuffer = substr($serializedMessage, $bytesWritten);
				} else {
					$taskDoneBuffer = '';
				}
			} else {
				$taskDoneBuffer = $serializedMessage;
			}
		}

		// Execute each child's shutdown closure before
		// exiting the process
		$results = $shutdownClosure();

		// Serialize this child's produced results and send them to the parent.
		$processDoneMessage = new ForkProcessDoneMessage($results);
		$serializedMessage = $taskDoneBuffer . base64_encode(serialize($processDoneMessage)) . "\n";

		$bytesToWrite = strlen($serializedMessage);
		$bytesWritten = 0;

		while ($bytesWritten < $bytesToWrite) {
			// attempt to write the remaining unsent part
			$bytesWritten += @fwrite($writeStream, substr($serializedMessage, $bytesWritten));

			if ($bytesWritten >= $bytesToWrite) {
				continue;
			}

			// wait a bit
			usleep(500000);
		}

		fclose($writeStream);

		// Children exit after completing their work
		exit(self::EXIT_SUCCESS);
	}

	/**
	 * Prepare the socket pair to be used in a parent process and
	 * return the stream the parent will use to read results.
	 *
	 * @param resource[] $sockets the socket pair for IPC
	 *
	 * @return resource
	 */
	private static function streamForParent(array $sockets)
	{
		[$forRead, $forWrite] = $sockets;

		// The parent will not use the write channel, so it
		// must be closed to prevent deadlock.
		fclose($forWrite);

		// stream_select will be used to read multiple streams, so these
		// must be set to non-blocking mode.
		if (!stream_set_blocking($forRead, false)) {
			error_log('unable to set read stream to non-blocking');
			exit(self::EXIT_FAILURE);
		}

		return $forRead;
	}

	/**
	 * Prepare the socket pair to be used in a child process and return
	 * the stream the child will use to write results.
	 *
	 * @param resource[] $sockets the socket pair for IPC
	 *
	 * @return resource
	 */
	private static function streamForChild(array $sockets)
	{
		[$forRead, $forWrite] = $sockets;

		// The while will not use the read channel, so it must
		// be closed to prevent deadlock.
		fclose($forRead);

		return $forWrite;
	}

	/**
	 * Read the results that each child process has serialized on their write streams.
	 * The results are returned in an array, one for each worker. The order of the results
	 * is not maintained.
	 *
	 * @return array<int, mixed>
	 */
	private function readResultsFromChildren(): array
	{
		// Create an array of all active streams, indexed by
		// resource id.
		$streams = [];
		foreach ($this->readStreams as $stream) {
			$streams[(int) $stream] = $stream;
		}

		// Create an array for the content received on each stream,
		// indexed by resource id.
		/** @var array<int, string> $content */
		$content = array_fill_keys(array_keys($streams), '');

		$terminationMessages = [];

		// Read the data off of all the stream.
		while (count($streams) > 0) {
			$needsRead = array_values($streams);
			$needsWrite = null;
			$needsExcept = null;

			// Wait for data on at least one stream.
			$num = stream_select($needsRead, $needsWrite, $needsExcept, null /* no timeout */);
			if ($num === false) {
				error_log('unable to select on read stream');
				exit(self::EXIT_FAILURE);
			}

			// For each stream that was ready, read the content.
			foreach ($needsRead as $file) {
				$buffer = fread($file, 1024);
				if ($buffer !== false) {
					$content[(int) $file] .= $buffer;
				}

				if ($buffer !== false && strpos($buffer, "\n") !== false) {
					$fileContent = $content[(int) $file];
					if ($fileContent === null) {
						throw new \PHPStan\ShouldNotHappenException();
					}
					$serializedMessages = explode("\n", $fileContent);
					$content[(int) $file] = array_pop($serializedMessages);

					foreach ($serializedMessages as $serializedMessage) {
						$decodedMessage = base64_decode($serializedMessage, true);
						if ($decodedMessage === false) {
							throw new \LogicException('Children must return base64 encoded message');
						}
						$message = unserialize($decodedMessage);

						if ($message instanceof ForkProcessDoneMessage) {
							$terminationMessages[] = $message->data;
						} elseif ($message instanceof ForkTaskDoneMessage) {
							if ($this->taskDoneClosure !== null) {
								($this->taskDoneClosure)($message->file, $message->data);
							}
						} else {
							error_log('Child should return ForkMessage - response type=' . gettype($message));
							$this->didHaveError = true;
						}
					}
				}

				// If the stream has closed, stop trying to select on it.
				if (!feof($file)) {
					// If the stream has closed, stop trying to select on it.
					continue;
					// If the stream has closed, stop trying to select on it.
				}

				if ($content[(int) $file] !== '') {
					error_log('Child did not send full message before closing the connection');
					$this->didHaveError = true;
				}

				fclose($file);
				unset($streams[(int) $file]);
			}
		}

		return array_values($terminationMessages);
	}

	/**
	 * Wait for all child processes to complete
	 *
	 * @return array<int, mixed>
	 */
	public function wait(): array
	{
		// Read all the streams from child processes into an array.
		$content = $this->readResultsFromChildren();

		// Wait for all children to return
		foreach ($this->childPidList as $childPid) {
			$processLookup = posix_kill($childPid, 0);

			$status = 0;

			if ($processLookup) {
				posix_kill($childPid, SIGALRM);

				if (pcntl_waitpid($childPid, $status) < 0) {
					error_log(posix_strerror(posix_get_last_error()));
				}
			}

			// Check to see if the child died a graceful death
			if (!pcntl_wifsignaled($status)) {
				// Check to see if the child died a graceful death
				continue;
				// Check to see if the child died a graceful death
			}

			$returnCode = pcntl_wexitstatus($status);
			$termSig = pcntl_wtermsig($status);

			if ($termSig === SIGALRM) {
				continue;
			}

			$this->didHaveError = true;
			error_log(sprintf(
				'Child terminated with return code %s and signal %s',
				$returnCode,
				$termSig
			));
		}

		return $content;
	}

	/**
	 * Returns true if this had an error, e.g. due to memory limits or due to a child process crashing.
	 *
	 * @return  bool
	 */
	public function didHaveError(): bool
	{
		return $this->didHaveError;
	}

}
