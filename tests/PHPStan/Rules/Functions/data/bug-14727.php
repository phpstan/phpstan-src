<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14727;

$one = 1;

class C {
	public static int $unknownInt;
}

json_encode(['payload'], 1);
json_encode(['payload'], 1 | 2);
json_encode(['payload'], $one | 2);
json_encode(['payload'], C::$unknownInt | 2);

json_encode(['payload'], 0);
json_encode(['payload'], JSON_PRETTY_PRINT | 0);
json_encode(['payload'], JSON_PRETTY_PRINT | 64);
json_encode(['payload'], flags: 128);
array_unique([], 2);
json_decode('{}', true, 512, JSON_THROW_ON_ERROR);
json_decode('{}', true, 512, 4194304);
