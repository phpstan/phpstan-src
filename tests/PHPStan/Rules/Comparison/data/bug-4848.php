<?php

namespace Bug4848;

function (): void {
	$maxBigintInMysql = '18446744073709551615';
	if ($maxBigintInMysql === (string)(int)$maxBigintInMysql) {
		echo 'same: ' . (string)(int)$maxBigintInMysql;
	} else {
		echo 'different: ' .(string)(int)$maxBigintInMysql;
	}
};
