<?php

namespace TemplateArgumentNestedCollection;

class Event
{
}

/** @template T */
class Collection
{

	/** @param iterable<T> $items */
	public function __construct(iterable $items = [])
	{
	}

}

/**
 * @template T of Event = Event
 * @extends Collection<T>
 */
class EventCollection extends Collection
{
}

/** @template ID of string|array<string, string> = string */
class WrittenEvent extends Event
{

	/** @param ID $id */
	public function __construct($id)
	{
	}

}

/** @template ID of string|array<string, string> = string */
class ContainerEvent
{

	/** @param EventCollection<WrittenEvent<ID>> $events */
	public function __construct(EventCollection $events)
	{
	}

}

function emptyCollection(): void
{
	new ContainerEvent(new EventCollection());
}

function populatedCollection(): void
{
	new ContainerEvent(new EventCollection([new WrittenEvent('foo')]));
}

function incompatibleCollection(): void
{
	new ContainerEvent(new EventCollection([new Event()]));
}
