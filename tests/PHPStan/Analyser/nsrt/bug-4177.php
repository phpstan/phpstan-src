<?php

namespace Bug4177;

use function PHPStan\Testing\assertType;

class Dto
{
	/** @var int */
	private $unixtimestamp = 12345678;

	public function getPeriodFrom(): ?int
	{
		return  rand(0,1) ? $this->unixtimestamp : null;
	}

	public function getPeriodTo(): ?int
	{
		return  rand(0,1) ? $this->unixtimestamp : null;
	}
}

function (Dto $request): void {
	if ($request->getPeriodFrom() || $request->getPeriodTo()) {
		if ($request->getPeriodFrom()) {
			assertType('int<min, -1>|int<1, max>', $request->getPeriodFrom());
		}

		if ($request->getPeriodTo() !== null) {
			assertType('int', $request->getPeriodTo());
		}
	}
};
