<?php

namespace Bug3530;

interface SyncableEntity {}
class A implements SyncableEntity {}
class B implements SyncableEntity {}
class C implements SyncableEntity {}
class D implements SyncableEntity {}
class E implements SyncableEntity {}
class F implements SyncableEntity {}
class G implements SyncableEntity {}
class H implements SyncableEntity {}
class I implements SyncableEntity {}
class J implements SyncableEntity {}

class HelloWorld
{
	/** @var array<string, class-string<SyncableEntity>> */
	const SYNC_CLASSES = [
		'a' => A::class,
		'b' => B::class,
		'c' => C::class,
		'd' => D::class,
		'e' => E::class,
		'f' => F::class,
		'g' => G::class,
		'h' => H::class,
		'i' => I::class,
		'j' => J::class,
	];

	/** @param class-string $class */
	private function getRepository($class) : void
	{
	}

	public function getSyncEntity(string $type, string $syncId) : void
	{
		$class = self::SYNC_CLASSES[$type] ?? null;
		if($class === null) {
			return;
		}

		$this->getRepository($class);
	}

	public function getSyncEntity2(string $type, string $syncId) : void
	{
		$class = static::SYNC_CLASSES[$type] ?? null;
		if($class === null) {
			return;
		}

		$this->getRepository($class);
	}
}

class HelloWorld2
{
	const SYNC_CLASSES = [
		'a' => A::class,
		'b' => B::class,
		'c' => C::class,
		'd' => D::class,
		'e' => E::class,
		'f' => F::class,
		'g' => G::class,
		'h' => H::class,
		'i' => I::class,
		'j' => J::class,
	];

	/** @param class-string $class */
	private function getRepository($class) : void
	{
	}

	public function getSyncEntity(string $type, string $syncId) : void
	{
		$class = self::SYNC_CLASSES[$type] ?? null;
		if($class === null) {
			return;
		}

		$this->getRepository($class);
	}

	public function getSyncEntity2(string $type, string $syncId) : void
	{
		$class = static::SYNC_CLASSES[$type] ?? null;
		if($class === null) {
			return;
		}

		$this->getRepository($class);
	}
}
