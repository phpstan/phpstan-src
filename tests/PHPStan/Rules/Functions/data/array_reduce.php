<?php declare(strict_types = 1);

array_reduce(
	[1,2,3],
	fn(string $foo, string $current): string => $foo . $current,
	''
);
