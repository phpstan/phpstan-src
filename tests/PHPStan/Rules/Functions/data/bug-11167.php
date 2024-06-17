<?php declare(strict_types = 1);

namespace Bug11167;

var_dump(array_unique([['a'], ['b']], SORT_REGULAR));
