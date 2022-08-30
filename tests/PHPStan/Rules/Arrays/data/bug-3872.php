<?php

namespace Bug3872;

class Demo
{
	public function analyze($json): void
	{
		foreach (json_decode($json, true) as $item) {
			$item += ['value' => '', 'operator' => ''];
			if ($item['value']) {
				$item['value'] = strtotime($item['value']);
				if ($item['operator'] === 'eq') {
					echo 'test';
				}
			}
		}
	}
}
