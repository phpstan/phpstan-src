<?php

namespace GeneralizeScope;

use function PHPStan\Testing\assertType;

class Foo
{

	/** @var mixed[] */
	private $vals = [];

	public function doFoo(): string
	{
		$statistics = [];
		foreach ($this->vals as $val) {
			$key = $val['key'];
			$model = preg_replace('~[^\\-A-Z\\\\_]+.*$~i', '', $val['key']);

			if (!isset($statistics[$model][$key])) {
				$statistics[$model][$key] = [
					'saveCount' => 0,
					'removeCount' => 0,
					'loadCount' => 0,
					'hitCount' => 0,
				];
			}

			if (rand(0, 1)) {
				$statistics[$model][$key]['saveCount']++;
			} elseif (rand(0, 1)) {
				$statistics[$model][$key]['removeCount']++;
			} else {
				$statistics[$model][$key]['loadCount']++;
				if ($val['hit']) {
					$statistics[$model][$key]['hitCount']++;
				}
			}
		}

		assertType('array<string, non-empty-array<array{saveCount: int<0, max>, removeCount: int<0, max>, loadCount: int<0, max>, hitCount: int<0, max>}>>', $statistics);
	}

}
