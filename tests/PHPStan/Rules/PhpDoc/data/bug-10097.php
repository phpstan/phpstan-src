<?php declare(strict_types = 1);

namespace Bug10097;

interface SomeMessage {}

/**
 * @template T of object
 */
interface Consumer
{
	/**
	 * @param T $message
	 */
	public function process($message): void;

	/**
	 * @return class-string<T>
	 */
	public function getMessageType(): string;
}


/**
 * @extends Consumer<SomeMessage>
 */
interface SomeMessageConsumer extends Consumer
{
}

/**
 * @return list<Consumer<*>>
 */
function getConsumers(SomeMessageConsumer $consumerA): array
{
	return [$consumerA];
}
