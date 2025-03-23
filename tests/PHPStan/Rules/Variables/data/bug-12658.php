<?php declare(strict_types=1);

namespace Bug12658;

function (): void {
	$ads = ['inline_1', 'inline_2', 'inline_3', 'inline_4'];
	$paragraphs = ['a', 'b', 'c', 'd', 'f'];

	foreach ($paragraphs as $key => $paragraph) {
		if (!empty($ads)) {
			$ad = array_shift($ads);
		}
	}
};
