<?php

namespace Bug10686;

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
		if ((self::$analysingTheirModelMap ?? null) === null) {
			self::$analysingTheirModelMap = new WeakAnalysingMap();
		}

		$theirModel = new Model();

		self::$analysingTheirModelMap->values[] = $theirModel;

		return end(self::$analysingTheirModelMap->values);
	}
}
