<?php

namespace Bug5232;

abstract class HelloWorld
{
	/**
	* @phpstan-return array{workId: string, collectionNumber: string, uuid: string|null}
	*/
	public function sayHello(string $content): array
	{
		$decodedContent = json_decode($content, true);
		if (
			!is_array($decodedContent)
			|| !isset($decodedContent['workId'], $decodedContent['collectionNumber'])
			|| !is_string($decodedContent['workId'])
			|| !is_string($decodedContent['collectionNumber'])
		) {
			throw new \Exception('Invalid response missing workId and/or collectionNumber');
		}

		$decodedContent['uuid'] = 'str';

		return $decodedContent;
	}
}
