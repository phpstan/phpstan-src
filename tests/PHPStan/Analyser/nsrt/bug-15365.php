<?php // lint >= 8.6

declare(strict_types = 1);

namespace Bug15365;

use Io\Poll\Context;
use Io\Poll\Event;
use StreamPollHandle;
use Time\Duration;
use function PHPStan\Testing\assertType;

function (): void {
	// Create a poll context with automatic backend selection
	$context = new Context();

	// Create a non-blocking socket, just like before
	$stream = stream_socket_server('tcp://0.0.0.0:8080', $errno, $errstr);
	stream_set_blocking($stream, false);

	// Wrap that stream in a new `StreamPollHandle` so that it can make use of the new API
	$handle = new StreamPollHandle($stream);
	assertType('StreamPollHandle', $handle);

	// Add the handle to the context
	assertType('Io\Poll\Watcher', $context->add($handle, [Event::Read], ['type' => 'server']));

	while (true) {
		// Wait for one second, polling for new events
		$timeout = Duration::fromSeconds(1);
		assertType('Time\Duration', $timeout);
		assertType('list<Io\Poll\Watcher>', $context->wait($timeout));

		// …
	}
};
