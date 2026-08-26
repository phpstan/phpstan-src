<?php declare(strict_types = 1);

namespace Bug12827Exceptions {
	final class MissingRoutingReferenceException extends \RuntimeException {}
	final class UnpickedOrderException extends \RuntimeException {}
}

namespace Bug12827ExceptionsConsumer {
	use Bug12827Exceptions\MissingRoutingReferenceException;
	use Bug12827Exceptions\UnpickedOrderException;

	class Consumer
	{

		public function doFoo(): void
		{
			try {
				echo 'x';
			} catch (
				MissingRoutingreferenceException |
				UnpickedOrderException $e
			) {
			}
		}

	}
}
