<?php declare(strict_types = 1);

namespace Bug7508Rule;

/**
 * @param array<mixed> $data
 */
function loopy (array $data ): void {

	foreach ($data as $key =>$value) {
		if(!is_array($value)) {
			continue;
		}
		$data[$key][0] = 'test';

	}
}
