<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15850;

use function PHPStan\Testing\assertType;

class Event {}

/** @template TEvent of Event */
class RegisteredListener {}

/** @template TEvent of Event */
final class RegisteredListenerCache {
	/** @var list<RegisteredListener<TEvent>>|null */
	public ?array $list = null;
}

/** @template TEvent of Event */
class HandlerList {
	/** @var array<int, array<int, RegisteredListener<TEvent>>> */
	private array $handlerSlots = [];

	/**
	 * @param HandlerList<contravariant TEvent>|null $parentList
	 * @param RegisteredListenerCache<TEvent> $handlerCache
	 */
	public function __construct(
		private ?HandlerList $parentList,
		private RegisteredListenerCache $handlerCache,
	) {}

	/** @return list<RegisteredListener<TEvent>> */
	public function getListenerList(): array {
		if ($this->handlerCache->list !== null) {
			return $this->handlerCache->list;
		}

		$handlerLists = [];
		for ($currentList = $this; $currentList !== null; $currentList = $currentList->parentList) {
			$handlerLists[] = $currentList;
		}
		assertType('non-empty-list<Bug15850\HandlerList<contravariant TEvent of Bug15850\Event (class Bug15850\HandlerList, argument)>>', $handlerLists);

		$listenersByPriority = [];
		foreach ($handlerLists as $currentList) {
			foreach ($currentList->handlerSlots as $priority => $listeners) {
				$listenersByPriority[$priority] = array_merge($listenersByPriority[$priority] ?? [], $listeners);
			}
		}

		krsort($listenersByPriority, SORT_NUMERIC);

		return $this->handlerCache->list = array_merge(...$listenersByPriority);
	}
}

class Node
{
	public ?Node $parent = null;
}

function reassignFromOwnProperty(Node $c): void
{
	$c = $c->parent;
	if ($c !== null) {
		// the parent of the new $c, not of the one $c->parent was read from
		assertType('Bug15850\Node|null', $c->parent);
	}
}
