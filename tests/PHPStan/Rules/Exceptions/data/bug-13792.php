<?php declare(strict_types = 1);

namespace Bug13792;

use DOMDocument;

class Foo
{

	public function validConstantNames(DOMDocument $doc): void
	{
		$doc->createElement('div');
		$doc->createElement('my-element');
		$doc->createElement('ns:tag');
		$doc->createElement('_private');
	}

	public function dynamicName(DOMDocument $doc, string $name): void
	{
		$doc->createElement($name); // error
	}

	public function invalidConstantName(DOMDocument $doc): void
	{
		$doc->createElement(''); // error
	}

	/**
	 * @param 'div'|'span' $validUnion
	 * @param 'div'|'' $mixedUnion
	 */
	public function unions(DOMDocument $doc, string $validUnion, string $mixedUnion): void
	{
		$doc->createElement($validUnion);
		$doc->createElement($mixedUnion); // error
	}

}
