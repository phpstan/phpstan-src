<?php

namespace InvalidFlagThrowType;

class Foo
{

	public function doArrayChangeKeyCase(array $a, int $case): void
	{
		try {
			array_change_key_case($a);
		} catch (\ValueError $e) {

		}

		try {
			array_change_key_case($a, CASE_LOWER);
		} catch (\ValueError $e) {

		}

		try {
			array_change_key_case($a, CASE_UPPER);
		} catch (\ValueError $e) {

		}

		try {
			array_change_key_case($a, 2);
		} catch (\ValueError $e) {

		}

		try {
			array_change_key_case($a, $case);
		} catch (\ValueError $e) {

		}
	}

	public function doPathinfo(string $path, int $flags, bool $b): void
	{
		try {
			pathinfo($path);
		} catch (\ValueError $e) {

		}

		try {
			pathinfo($path, PATHINFO_DIRNAME);
		} catch (\ValueError $e) {

		}

		try {
			pathinfo($path, PATHINFO_BASENAME);
		} catch (\ValueError $e) {

		}

		try {
			pathinfo($path, PATHINFO_EXTENSION);
		} catch (\ValueError $e) {

		}

		try {
			pathinfo($path, PATHINFO_FILENAME);
		} catch (\ValueError $e) {

		}

		try {
			pathinfo($path, PATHINFO_ALL);
		} catch (\ValueError $e) {

		}

		try {
			pathinfo($path, $b ? PATHINFO_DIRNAME : PATHINFO_BASENAME);
		} catch (\ValueError $e) {

		}

		try {
			pathinfo($path, 0);
		} catch (\ValueError $e) {

		}

		try {
			pathinfo($path, PATHINFO_DIRNAME | PATHINFO_BASENAME);
		} catch (\ValueError $e) {

		}

		try {
			pathinfo($path, 16);
		} catch (\ValueError $e) {

		}

		try {
			pathinfo($path, $flags);
		} catch (\ValueError $e) {

		}
	}

	public function doArrayFilterCallback(array $a, \Closure $closure, ?callable $nullableCallable, callable $callable, string $functionName): void
	{
		try {
			array_filter($a, static function (): bool { throw new \LogicException(); });
		} catch (\LogicException $e) {

		}

		try {
			array_filter($a, $closure);
		} catch (\RuntimeException $e) {

		}

		try {
			array_filter($a, $nullableCallable);
		} catch (\RuntimeException $e) {

		}

		try {
			array_filter($a, $callable);
		} catch (\RuntimeException $e) {

		}

		try {
			array_filter($a, $functionName);
		} catch (\RuntimeException $e) {

		}

		try {
			array_filter($a, $closure, ARRAY_FILTER_USE_KEY);
		} catch (\RuntimeException $e) {

		}
	}

}
