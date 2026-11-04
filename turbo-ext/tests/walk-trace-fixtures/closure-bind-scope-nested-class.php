<?php declare(strict_types = 1);

namespace WalkTraceClosureBindScopeNestedClass;

// a class or function declared inside a closure bound with Closure::bind()
// has its own self/parent/static: entering it resets the bound class
class Foo
{
	protected const A = 'Foo';
}

\Closure::bind(static fn () => new class extends Foo {
	public const Z = 'z';
	public function f(): array { return [self::Z, static::Z, self::A, parent::A, new self(), new static(), new parent()]; }
}, null, Foo::class)();

\Closure::bind(static function (): void {
	function walkTraceClosureBindInner(): void
	{
		echo self::A;
	}
	(static fn () => [self::A, new self()])();
}, null, Foo::class)();
