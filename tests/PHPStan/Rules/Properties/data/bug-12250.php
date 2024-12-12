<?php // lint >= 7.4

namespace Bug12250;

class Model {}

/**
 * @template T of object|array<mixed>
 */
class WeakAnalysingMap
{
	/** @var list<T> */
	public array $values = [];
}

class Reference
{
	/** @var WeakAnalysingMap<Model> */
	private static WeakAnalysingMap $analysingTheirModelMap;

	public function createAnalysingTheirModel(): Model
	{
		self::$analysingTheirModelMap ??= new WeakAnalysingMap();

		$theirModel = new Model();

		self::$analysingTheirModelMap->values[] = $theirModel;

		return end(self::$analysingTheirModelMap->values);
	}
}
