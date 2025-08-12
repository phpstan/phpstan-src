<?php declare(strict_types=1);

namespace Bug7773;

class JSONEncodingException extends \Exception
{
}

class JSONDecodingException extends \Exception
{
}

class HelloWorld
{
	/**
	 * Encodes the data as JSON
	 * @param array<mixed> $data json array
	 * @return string json string
	 * @throws JSONEncodingException
	 */
	public static function JSONEncode(array $data): string
	{
		if (!is_string($data = json_encode($data)))
			throw new JSONEncodingException();
		return $data;
	}

	/**
	 * Decodes the JSON data as an array
	 * @param string $data json string
	 * @return array<mixed> json array
	 * @throws JSONDecodingException
	 */
	public static function JSONDecode(string $data): array
	{
		if (!is_array($data = json_decode($data, true)))
			throw new JSONDecodingException();
		return $data;
	}
}
