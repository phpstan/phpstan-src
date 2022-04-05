<?php declare(strict_types = 1);

namespace Bug1714;

/**
 * @param string|int $val
 * @param string|array $text
 * @param array $data
 */
function _renderInput($val, $text, $data) : array {
	if (isset($text["foo"], $text["bar"])) {
		$radio = $text;
	} else {
		$radio = ['value' => $val, 'text' => $text];
	}
	$radio['name'] = $data['name'];
	return $radio;
}
