<?php

namespace Bug4290;

class HelloWorld
{
	public function test(): void
	{
		$array = self::getArray();

		$data = array_filter([
			'status' => isset($array['status']) ? $array['status'] : null,
			'value' => isset($array['value']) ? $array['value'] : null,
		]);

		if (count($data) === 0) {
			return;
		}

		isset($data['status']) ? 1 : 0;
	}

	/**
	 * @return string[]
	 */
	public static function getArray(): array
	{
		return ['value' => '100'];
	}
}
