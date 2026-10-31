<?php declare(strict_types = 1);

namespace Bug10714;

/**
 * @param array<string, string> $info
 */
function issueLayout(array $info, string $other): string
{
	return strtolower(
		($info['test'] ?? 'default') . '-' .
		$other ?? 'other'
	);
}

function operatorStartsTheLine(string $a): string
{
	return $a
		?? 'b';
}

function operatorEndsTheLine(string $a): string
{
	return $a ??
		'b';
}

function singleLine(string $a): string
{
	return $a ?? 'b';
}

function assignOperatorStartsTheLine(string $a): string
{
	$a
		??= 'b';

	return $a;
}

/**
 * @param array{k: string} $s
 */
function assignOperatorAfterMultiLineLeftSide(array $s): string
{
	$s[
		'k'
	] ??= 'b';

	return $s['k'];
}

/**
 * @param array{k: string|null} $s
 */
function unnecessaryCoalesceAfterMultiLineLeftSide(array $s): ?string
{
	return $s[
		'k'
	] ?? null;
}

class PropertyOnMultiLineLeftSide
{

	private string $p = '';

	public function get(): string
	{
		return $this
			->p ?? 'x';
	}

}
