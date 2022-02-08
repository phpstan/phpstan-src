<?php // lint >= 7.4

namespace Bug6286;

class HelloWorld
{
	/**
	 * @var array{name:string,age:int}
	 */
	public array $details;
	/**
	 * @var array<array{name:string,age:int}>
	 */
	public array $nestedDetails;

	public function doSomething(): void
	{
		$this->details ['name'] = 'Douglas Adams';
		$this->details ['age'] = 'Forty-two';

		$this->nestedDetails [0] ['name'] = 'Bilbo Baggins';
		$this->nestedDetails [0] ['age'] = 'Eleventy-one';
	}
}
