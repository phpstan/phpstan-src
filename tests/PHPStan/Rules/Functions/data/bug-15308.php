<?php // lint >= 8.0

namespace Bug15308;

// $length has no constant list of its own, so a numeric constant is a valid length.
str_pad('bla', PHP_INT_SIZE, 'bla');
str_pad('bla', SODIUM_CRYPTO_PWHASH_SALTBYTES, 'bla');

// One of str_pad()'s own flags in the $length position is still reported.
str_pad('bla', STR_PAD_LEFT, 'bla');
