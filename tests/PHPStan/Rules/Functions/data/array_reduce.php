<?php declare(strict_types = 1);

array_reduce(
	[1,2,3],
	function(string $foo, string $current): string {
		return $foo . $current;
	},
	''
);

array_reduce(
	[1,2,3],
	function(string $foo, int $current): string {
		return $foo . $current;
	},
	null
);


array_reduce(
	[1,2,3],
	function(string $foo, int $current): string {
		return $foo . $current;
	},
);
