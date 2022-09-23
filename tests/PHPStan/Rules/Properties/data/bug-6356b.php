<?php declare(strict_types=1); // lint >= 7.4

namespace Bug6356b;

class HelloWorld2
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

		$this->nestedDetails [] = [
			'name' => 'Bilbo Baggins',
			'age' => 'Eleventy-one',
		];

		$this->nestedDetails [12] ['age'] = 'Twelve';
		$this->nestedDetails [] ['age'] = 'Five';

		$this->nestedDetails [99] ['name'] = 'nothing';
	}
}
