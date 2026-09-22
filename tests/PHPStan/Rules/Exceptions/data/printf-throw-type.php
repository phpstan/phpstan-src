<?php

namespace PrintfThrowType;

class Foo
{

	/**
	 * @param resource $stream
	 * @param list<string> $parts
	 * @param 5 $phpDocWidth
	 */
	public function doFoo(int $id, string $name, string $format, $stream, array $parts, int $width, int $phpDocWidth, bool $flag): void
	{
		try {
			$a = sprintf('Product: %d, Image: %s', $id, $name);
		} catch (\ValueError $e) {

		} catch (\ArgumentCountError $e) {

		}

		try {
			$a = sprintf('%s');
		} catch (\ValueError $e) {

		} catch (\ArgumentCountError $e) {

		}

		try {
			$a = sprintf('%y', 1);
		} catch (\ValueError $e) {

		} catch (\ArgumentCountError $e) {

		}

		try {
			$a = sprintf($format, $id);
		} catch (\ValueError $e) {

		} catch (\ArgumentCountError $e) {

		}

		try {
			$a = sprintf('%s-%s', ...$parts);
		} catch (\ValueError $e) {

		} catch (\ArgumentCountError $e) {

		}

		try {
			$a = sprintf('%s-%s', ...['a', 'b']);
		} catch (\ValueError $e) {

		} catch (\ArgumentCountError $e) {

		}

		try {
			$a = sprintf('%*d', $width, $id);
		} catch (\ValueError $e) {

		} catch (\ArgumentCountError $e) {

		}

		try {
			$a = sprintf('%*d', 5, $id);
		} catch (\ValueError $e) {

		} catch (\ArgumentCountError $e) {

		}

		try {
			$a = sprintf('%*d', $phpDocWidth, $id);
		} catch (\ValueError $e) {

		} catch (\ArgumentCountError $e) {

		}

		try {
			$a = sprintf('%.*g %.*f', -1, 1.5, -1, 1.5);
		} catch (\ValueError $e) {

		} catch (\ArgumentCountError $e) {

		}

		try {
			$a = sprintf('%.*g', -1, 1.5);
		} catch (\ValueError $e) {

		} catch (\ArgumentCountError $e) {

		}

		$f = $flag ? '%s' : '%s %s';
		try {
			$a = sprintf($f, 'a');
		} catch (\ValueError $e) {

		} catch (\ArgumentCountError $e) {

		}

		try {
			printf('%s %5.2f%%', $name, 1.5);
		} catch (\ValueError $e) {

		} catch (\ArgumentCountError $e) {

		}

		try {
			fprintf($stream, '%d', $id);
		} catch (\ValueError $e) {

		} catch (\ArgumentCountError $e) {

		}

		try {
			$a = vsprintf('%s-%s', ['a', 'b']);
		} catch (\ValueError $e) {

		}

		try {
			$a = vsprintf('%s-%s', $parts);
		} catch (\ValueError $e) {

		}

		try {
			$a = vsprintf('%*d', [5, $id]);
		} catch (\ValueError $e) {

		}
	}

}
