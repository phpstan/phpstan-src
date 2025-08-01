<?php declare(strict_types = 1);

namespace Bug8236;

class HelloWorld
{
	public static function sayHello(): void
	{
		$xml = new \SimpleXmlElement('<root><a></a></root>');
		$string = 'But isn\'t 7 > 5 & 9 < 11?';
		$node = $xml->a->addChild('formula1');
		if ($node !== null) {
			$node[0] = $string;
		}
		echo $xml->asXML();
	}
}
HelloWorld::sayHello();
