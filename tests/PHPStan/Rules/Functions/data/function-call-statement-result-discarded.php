<?php // lint >= 8.1

namespace FunctionCallStatementResultDiscarded;

#[\NoDiscard]
function withSideEffects(int $i): array {
	echo __FUNCTION__ . "\n";
	return [1];
}

withSideEffects(5);

foreach (withSideEffects(5) as $num) {
	var_dump($num);
}

#[\nOdISCArD]
function differentCase(): array {
	echo __FUNCTION__ . "\n";
	return [1];
}

differentCase();

$callable = 'FunctionCallStatementResultDiscarded\\withSideEffects';
$callableResult = $callable();

$callable();

$firstClassCallable = withSideEffects(...);
$firstClasCallableResult = $firstClassCallable(5);

$firstClassCallable(5);

$closureWithNoDiscard = #[\NoDiscard] function () { return 1; };
$a = $closureWithNoDiscard();

$closureWithNoDiscard();

$arrowWithNoDiscard = #[\NoDiscard] fn () => 1;
$b = $arrowWithNoDiscard();

$arrowWithNoDiscard();

withSideEffects(...);

function canDiscard(int $i): int
{
	return 1;
}

canDiscard(5);

$canDiscardCb = 'FunctionCallStatementResultDiscarded\\canDiscard';
$canDiscardCb();

function (): void {
	if (random_int(1, 1)) {
		$fn = 'call_user_func';
	} else {
		$fn = 'nope';
	}

	$fn('FunctionCallStatementResultDiscarded\\withSideEffects', 5);
};

call_user_func('FunctionCallStatementResultDiscarded\\withSideEffects', 5);
call_user_func_array('FunctionCallStatementResultDiscarded\\withSideEffects', [5]);
forward_static_call('FunctionCallStatementResultDiscarded\\withSideEffects', 5);
forward_static_call_array('FunctionCallStatementResultDiscarded\\withSideEffects', [5]);
call_user_func(callback: 'FunctionCallStatementResultDiscarded\\withSideEffects', i: 5);
call_user_func('call_user_func', 'FunctionCallStatementResultDiscarded\\withSideEffects', 5);
call_user_func($firstClassCallable, 5);
call_user_func('FunctionCallStatementResultDiscarded\\canDiscard', 5);
$result = call_user_func('FunctionCallStatementResultDiscarded\\withSideEffects', 5);

class Foo
{

	#[\NoDiscard]
	public function doFoo(): int
	{
		return 1;
	}

	#[\NoDiscard]
	public static function doBar(): int
	{
		return 1;
	}

	public function canDiscard(): int
	{
		return 1;
	}

}

call_user_func([new Foo(), 'doFoo']);
call_user_func([Foo::class, 'doBar']);
call_user_func('FunctionCallStatementResultDiscarded\\Foo::doBar');
call_user_func([new Foo(), 'canDiscard']);

function (bool $b): void {
	$fn = $b ? 'call_user_func' : 'nope';
	$fn('FunctionCallStatementResultDiscarded\\withSideEffects', 5);

	$fn = $b ? 'call_user_func' : 'FunctionCallStatementResultDiscarded\\canDiscard';
	$fn('FunctionCallStatementResultDiscarded\\canDiscard', 5);
};
