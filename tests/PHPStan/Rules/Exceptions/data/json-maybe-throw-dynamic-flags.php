<?php

namespace JsonMaybeThrowDynamicFlags;

function (string $s, int $flags): void {
	try {
		json_decode($s, true, 512, JSON_BIGINT_AS_STRING | $flags);
	} catch (\JsonException $e) {

	}
};

function (string $s, int $flags): void {
	try {
		json_decode($s, true, 512, $flags | JSON_BIGINT_AS_STRING);
	} catch (\JsonException $e) {

	}
};

/**
 * @param mixed $m
 */
function ($m, int $flags): void {
	try {
		json_encode($m, JSON_PRETTY_PRINT | $flags);
	} catch (\JsonException $e) {

	}
};

function (string $s): void {
	try {
		json_decode($s, true, 512, JSON_BIGINT_AS_STRING | JSON_INVALID_UTF8_IGNORE);
	} catch (\JsonException $e) {

	}
};
