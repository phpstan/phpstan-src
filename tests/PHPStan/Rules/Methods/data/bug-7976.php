<?php declare(strict_types = 1);

namespace Bug7976;

/**
 * @extends \ArrayObject<string, ?float>
 */
class Arr extends \ArrayObject
{

}

class HelloWorld
{
	/** @return \ArrayObject<string|int, non-empty-array<string, mixed>> */
	public function sayHello(): \ArrayObject
	{
		$input = [
			[
				"id" => 1,
				"name" => "name_1",
				"value" => 1.2,
				"arr" => new Arr(["p_1" => 1, "p_2" => 2, "n_1" => 1])
			],
			[
				"id" => 2,
				"name" => "name_2",
				"value" => null,
				"arr" => new Arr(["p_3" => 1, "p_2" => 2, "n_2" => 1])
			],
			[
				"id" => 3,
				"name" => "name_3",
				"value" => 1.4,
				"arr" => new Arr(["p_3" => 1, "p_5" => 2, "n_2" => null])
			],
		];

		$data = $this->initData();
		foreach ($input as $in) {
			$id = $in["id"];
			$data[$id]["id"] = $in["id"];
			$data[$id]["name"] = $in["name"];
			$data[$id]["value"] = $in["value"];
			/** @var string $name */
			/** @var ?float $value */
			foreach ($in["arr"] as $name => $value) {
				if (strpos($name, 'p') !== false && null !== $value) {
					$data[$id][$name] = $value;
				}
			}
		}
		return new \ArrayObject($data);
	}

	/** @return array<string, array<string, mixed>> */
	private function initData(): array
	{
		return [];
	}
}
