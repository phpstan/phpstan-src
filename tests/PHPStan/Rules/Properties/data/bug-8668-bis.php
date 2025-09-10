<?php

namespace Bug8668Bis;

class Sample {
	private string $sample = 'abc';

	public function test(): void {
		echo self::$sample;
		echo isset(self::$sample);

		echo $this->sample; // ok
	}
}

class Sample2 {
	private $sample = 'abc';

	public function test(): void {
		echo self::$sample;
		echo isset(self::$sample);

		echo $this->sample; // ok
	}
}
