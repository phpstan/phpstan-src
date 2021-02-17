<?php

namespace Bug1711;

class WrongBehavior {
	private $x;

	public function __construct(?float $x) {
		$this->x = $x;
	}

	public function setX(?float $x) {
		$this->x = $x;
	}
}

class CorrectBehavior {
	private $x;

	public function __construct(float $x) {
		$this->x = $x;
	}

	public function setX(float $x) {
		$this->x = $x;
	}
}

function (): void {
	$items = [0.5, 1];

	foreach ($items as $item) {
		$wrong = new WrongBehavior($item);
		$wrong->setX($item);
		$correct = new CorrectBehavior($item);
		$correct->setX($item);
	}
};
