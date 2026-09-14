<?php // lint >= 8.0

namespace TriggerErrorThrowType;

function doFoo(int $level): void
{
	try {
		trigger_error('foo', E_USER_ERROR);
	} catch (\Exception $e) {

	}
	try {
		trigger_error('foo', E_USER_DEPRECATED);
	} catch (\Exception $e) {

	}
	try {
		trigger_error('foo');
	} catch (\Exception $e) {

	}
	try {
		trigger_error('foo', 12345);
	} catch (\ValueError $e) {

	}
	try {
		trigger_error('foo', $level);
	} catch (\ValueError $e) {

	}
	try {
		trigger_error('foo', E_USER_WARNING);
	} catch (\ValueError $e) {

	}
	try {
		trigger_error('foo', 12345);
	} catch (\Exception $e) {

	}
}
