<?php

namespace DeadCatchDefinedVariable;

class Foo
{

	/** @throws \RuntimeException */
	private function process(string $name): void
	{
	}

	/** @param list<string> $names */
	public function doFoo(array $names): void
	{
		try {
			foreach ($names as $name) {
				$this->process($name);
			}
		} catch (\InvalidArgumentException $e) {
			echo $name;
			echo $undefined;
		}
	}

}
