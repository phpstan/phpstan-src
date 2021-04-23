<?php

namespace Bug4863;

class HelloWorld
{
	/**
	 * @return \Generator<string>
	 */
	public function generate(): \Generator {
		try {
			yield 'test';
		} catch (\Exception $exception) {
			echo $exception->getMessage();
		}
	}
}
