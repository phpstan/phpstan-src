<?php declare(strict_types = 1);

namespace Bug11870;

use function PHPStan\Testing\assertType;

function () {
	assert(isset($v) && is_array($v) && isset($v['LANGUAGE']));

	$language = $v['LANGUAGE'] !== 1 ? 'en' : '';
	if ($language !== '') {
		assertType('true', $v['LANGUAGE'] !== 1);
	}
};
