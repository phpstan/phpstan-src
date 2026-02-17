<?php declare(strict_types = 1);

namespace Bug13792;

use DOMDocument;
use function PHPStan\Testing\assertType;

class Foo
{

	public function validNames(DOMDocument $doc): void
	{
		assertType('DOMElement', $doc->createElement('div'));
		assertType('DOMElement', $doc->createElement('my-element'));
		assertType('DOMElement', $doc->createElement('_element'));
		assertType('DOMElement', $doc->createElement('a'));
		assertType('DOMElement', $doc->createElement('div', 'content'));
	}

	public function invalidNames(DOMDocument $doc): void
	{
		assertType('(DOMElement|false)', $doc->createElement('123element'));
		assertType('(DOMElement|false)', $doc->createElement(''));
		assertType('(DOMElement|false)', $doc->createElement('my element'));
	}

	public function dynamicName(DOMDocument $doc, string $name): void
	{
		assertType('(DOMElement|false)', $doc->createElement($name));
	}

	/** @param 'div'|'span' $name */
	public function unionOfValidNames(DOMDocument $doc, string $name): void
	{
		assertType('DOMElement', $doc->createElement($name));
	}

	/** @param 'div'|'' $name */
	public function unionOfMixedNames(DOMDocument $doc, string $name): void
	{
		assertType('(DOMElement|false)', $doc->createElement($name));
	}

	public function createAttribute(DOMDocument $doc): void
	{
		assertType('DOMAttr', $doc->createAttribute('valid'));
		assertType('(DOMAttr|false)', $doc->createAttribute(''));
	}

	public function createEntityReference(DOMDocument $doc): void
	{
		assertType('DOMEntityReference', $doc->createEntityReference('amp'));
		assertType('(DOMEntityReference|false)', $doc->createEntityReference(''));
	}

	public function createProcessingInstruction(DOMDocument $doc): void
	{
		assertType('DOMProcessingInstruction', $doc->createProcessingInstruction('xml'));
		assertType('(DOMProcessingInstruction|false)', $doc->createProcessingInstruction(''));
	}

	public function createCDATASection(DOMDocument $doc): void
	{
		assertType('DOMCDATASection', $doc->createCDATASection('anything'));
		assertType('DOMCDATASection', $doc->createCDATASection(''));
	}

}
