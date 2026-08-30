<?php // lint >= 8.0

declare(strict_types = 1);

namespace ConditionalParamTypeAssert;

use function PHPStan\Testing\assertType;

class Dto {}
class ErrorPayload extends Dto {}
class MessagePayload extends Dto {}

/**
 * @template T of Dto
 */
class Message extends Dto
{

	/**
	 * @param ($success is true ? T : ErrorPayload) $payload
	 */
	public function __construct(
		public string $messageId,
		public bool $success,
		public Dto $payload,
	)
	{
	}

	/**
	 * @phpstan-assert-if-true T $this->payload
	 */
	public function isSuccess(): bool
	{
		return $this->success;
	}

}

/**
 * @param Message<MessagePayload> $message
 */
function doStuff(Message $message): void
{
	assertType('ConditionalParamTypeAssert\ErrorPayload|ConditionalParamTypeAssert\MessagePayload', $message->payload);
	if (!$message->isSuccess()) {
		assertType('ConditionalParamTypeAssert\ErrorPayload', $message->payload);
		return;
	}

	assertType('ConditionalParamTypeAssert\MessagePayload', $message->payload);
}
