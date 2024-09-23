<?php

namespace Bug7082;

function takesStr(string $val): string {
	return $val . 'foo';
}

function (): void {
	$nullable = $_GET['x'] ?? null;
	echo takesStr($nullable);
};
