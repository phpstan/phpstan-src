<?php declare(strict_types = 1);

namespace ArglessCallNarrowingWalkedType;

use function PHPStan\Testing\assertType;

class Request
{

	/**
	 * @return ($asResource is true ? resource : string)
	 */
	public function getContent(bool $asResource = false)
	{
		return '';
	}

}

class RequestEvent
{

	public function getRequest(): Request
	{
		return new Request();
	}

}

/**
 * @template T of object
 */
class Collection
{

	/** @var list<T> */
	private array $items = [];

	/** @return T|null */
	public function first()
	{
		return $this->items[0] ?? null;
	}

}

final class Item
{

	public string $name = '';

}

class Node
{

	/** @return static|false */
	public function getParent()
	{
		return false;
	}

}

/**
 * @param Collection<Item> $items
 */
function test(RequestEvent $event, string $contentType, Collection $items, Node $node): void
{
	if (str_starts_with($contentType, 'application/json') && $event->getRequest()->getContent()) {
		assertType('non-falsy-string', $event->getRequest()->getContent());
	}

	if ($event->getRequest()->getContent()) {
		assertType('non-falsy-string', $event->getRequest()->getContent());
	} else {
		assertType("''|'0'", $event->getRequest()->getContent());
	}

	if ($items->first() !== null) {
		assertType('ArglessCallNarrowingWalkedType\Item', $items->first());
	}

	while ($node->getParent() !== false) {
		$node = $node->getParent();
		assertType('ArglessCallNarrowingWalkedType\Node', $node);
	}
}
