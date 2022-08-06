<?php declare(strict_types=1);

namespace Bug3391;

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

		unset($data['id']);

		return $data;
	}
}
