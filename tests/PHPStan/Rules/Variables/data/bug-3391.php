<?php declare(strict_types=1);

namespace Bug3391;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * This method represents \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize().
	 *
	 * @return array|string|int|float|bool|null
	 */
	public function getArray()
	{
		return ['id' => 1];
	}

	public function test()
	{
		$data = $this->getArray();

		$data['foo'] = 'a';
		$data['bar'] = 'b';
		assertType("non-empty-array&hasOffsetValue('bar', 'b')&hasOffsetValue('foo', 'a')", $data);

		unset($data['id']);

		assertType("non-empty-array<mixed~'id', mixed>&hasOffsetValue('bar', 'b')&hasOffsetValue('foo', 'a')", $data);
		return $data;
	}
}
