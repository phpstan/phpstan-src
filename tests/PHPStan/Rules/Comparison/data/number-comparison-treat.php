<?php

namespace NumberComparisonTreatPhpDocTypesAsCertain;

class Foo
{

	/** @param positive-int $i */
	public function sayHello(int $i): void
	{
		if ($i >= 0) {
		}
	}

	/** @param positive-int $i */
	public function sayHello2(int $i): void
	{
		if ($i < 0) {
		}
	}

}
