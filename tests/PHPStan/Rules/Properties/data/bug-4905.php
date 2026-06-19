<?php declare(strict_types = 1);

namespace Bug4905;

class Event {
	public ?EventPreset $eventPreset = null;
	public function isSpecial(): bool {
		return rand(1, 5) === 5;
	}
}

class EventPreset {
	public bool $test = false;
}

class HelloWorld
{
	function test(): void {
		$event = rand(1, 5) === 5 ? new Event() : null;
		$eventPreset = $event?->eventPreset;
		$isSpecial = $event?->isSpecial();

		if ($isSpecial) {
			assert($eventPreset instanceof EventPreset);
		}

		if ($isSpecial && $eventPreset->test) {

		}
	}
}
