<?php

namespace Bug13956;

final class Foo {
	/**
	 * @param list<string> $successMessages
	 */
	public function __construct(
		private array $successMessages = [],
	) {
	}

	public function addSuccess(string $message): self
	{
		$this->successMessages[] = $message;

		return $this;
	}

	/**
	 * @return string[]
	 */
	public function getSuccessMessages(): array {
		return $this->successMessages;
	}
}

function doBar(Foo $foo):void {
	$foo->addSuccess("Hello World");
}
