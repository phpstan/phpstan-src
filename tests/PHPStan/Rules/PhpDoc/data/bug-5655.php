<?php declare(strict_types=1);

namespace Bug5655;

abstract class Error
{

	/**
	 * @var array{format: string, specificity?: int}
	 */
	const SPEC = [
		'format' => '',
	];
}
