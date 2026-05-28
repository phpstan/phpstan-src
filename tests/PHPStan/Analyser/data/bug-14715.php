<?php // lint >= 8.0

namespace Bug14715;

class Event {
	public function __construct(public int $a) {}
}

class EventOutput extends Event {
	public function __construct(public int $a, public bool $b) {
		parent::__construct(a: $a);
	}

	public static function fromEvent(Event $event, bool $b): self
	{
		$properties = ['b' => $b];
		$construct = new \ReflectionMethod(Event::class, '__construct');
		foreach ($construct->getParameters() as $parameter) {
			$properties[$parameter->getName()] = $event->{$parameter->getName()};
		}

		return new self(...$properties);
	}
}
