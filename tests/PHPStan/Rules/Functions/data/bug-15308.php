<?php // lint >= 8.0

namespace Bug15308;

str_pad('', PHP_INT_SIZE);
str_pad('', STR_PAD_LEFT);
