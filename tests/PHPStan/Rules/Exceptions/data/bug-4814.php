<?php

namespace Bug4814;

function (string $s): void {
	try {
		json_decode($s, true, 512, JSON_THROW_ON_ERROR);
	} catch (\JsonException $e) {

	}
};

function (string $s): void {
	try {
		json_decode($s);
	} catch (\JsonException $e) {

	}
};
