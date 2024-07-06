<?php declare(strict_types = 1);

namespace Bug11292;

function (string $s): void {
	$nonUrl = "[^-_#$+.!*%'(),;/?:@~=&a-zA-Z0-9\x7f-\xff]";
	$pattern = '{\\b(https?://)?(?:([^]\\\\\\x00-\\x20\\"(),:-<>[\\x7f-\\xff]{1,64})(:[^]\\\\\\x00-\\x20\\"(),:-<>[\\x7f-\\xff]{1,64})?@)?((?:[-a-zA-Z0-9\\x7f-\\xff]{1,63}\\.)+[a-zA-Z\\x7f-\\xff][-a-zA-Z0-9\\x7f-\\xff]{1,62})((:[0-9]{1,5})?(/[!$-/0-9:;=@_~\':;!a-zA-Z\\x7f-\\xff]*?)?(\\?[!$-/0-9:;=@_\':;!a-zA-Z\\x7f-\\xff]+?)?(#[!$-/0-9?:;=@_\':;!a-zA-Z\\x7f-\\xff]+?)?)(?=[)\'?.!,;:]*(' . $nonUrl . '|$))}';
	if (preg_match($pattern, $s, $matches, PREG_OFFSET_CAPTURE, 0)) {

	}
};
