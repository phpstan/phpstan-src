<?php declare(strict_types=1); // lint >= 8.0

namespace OffsetAccessLegal;

function closure(): void
{
	(function(){})[0] ?? "error";
}

function nonArrayAccessibleObject()
{
	(new \stdClass())[0] ?? "error";
}

function arrayAccessibleObject()
{
	(new class implements \ArrayAccess {
		public function offsetExists($offset) {
			return true;
		}

		public function offsetGet($offset) {
			return $offset;
		}

		public function offsetSet($offset, $value) {
		}

		public function offsetUnset($offset) {
		}
	})[0] ?? "error";
}

function array_(): void
{
	[0][0] ?? "error";
}

function integer(): void
{
	(0)[0] ?? 'ok';
}

function float(): void
{
	(0.0)[0] ?? 'ok';
}

function null(): void
{
	(null)[0] ?? 'ok';
}

function bool(): void
{
	(true)[0] ?? 'ok';
}

function void(): void
{
	((function (){})())[0] ?? 'ok';
}

function resource(): void
{
	(tmpfile())[0] ?? 'ok';
}

function offsetAccessibleMaybeAndLegal(): void
{
	$arrayAccessible = rand() ? (new class implements \ArrayAccess {
		public function offsetExists($offset) {
			return true;
		}

		public function offsetGet($offset) {
			return $offset;
		}

		public function offsetSet($offset, $value) {
		}

		public function offsetUnset($offset) {
		}
	}) : false;

	($arrayAccessible)[0] ?? "error";

	(rand() ? "string" : true)[0] ?? "error";
}

function offsetAccessibleMaybeAndIllegal(): void
{
	$arrayAccessible = rand() ? new \stdClass() : ['test'];

	($arrayAccessible)[0] ?? "error";

	(rand() ? function(){} : ['test'])[0] ?? "error";
}
