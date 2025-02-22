<?php

namespace Bug12629;

class Bug12629 {
	private function is_macintosh_enc($s) {

		if(!is_string($s)) {
			return false;
		}

		preg_match_all("![\x80-\x9f]!u", $s, $matchesMacintosh);
		preg_match_all("!\xc3[\x80-\x9f]!u", $s, $matchesUtf8);

		return count($matchesMacintosh[0]) > 0 && 0 == count($matchesUtf8[0]);
	}
}
