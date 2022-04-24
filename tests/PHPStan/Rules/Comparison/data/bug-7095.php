<?php // lint >= 8.0

namespace Bug7095;

match (isset($foo)) {
	true => 'a',
	false => 'b',
};
