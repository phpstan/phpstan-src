<?php declare(strict_types=1);

namespace Bug9714;

use function PHPStan\Testing\assertType;

class ExampleClass
{
	public function exampleFunction(): void
	{
		$xml = new \SimpleXmlElement('');
		$elements = $xml->xpath('//data');
		assertType('array<SimpleXmlElement>', $elements);
	}
}

