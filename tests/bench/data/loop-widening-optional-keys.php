<?php declare(strict_types = 1);

namespace BenchLoopWideningOptionalKeys;

final class Answer
{

	/** @var list<string> */
	public array $list1 = [];

	/** @var list<string> */
	public array $list2 = [];

	/** @var list<string> */
	public array $list3 = [];

	/** @var list<string> */
	public array $list4 = [];

	/** @var list<string> */
	public array $list5 = [];

	/** @var list<string> */
	public array $list6 = [];

	/** @var list<string> */
	public array $list7 = [];

	/** @var list<string> */
	public array $list8 = [];

	/** @var list<string> */
	public array $list9 = [];

	/** @var list<string> */
	public array $loop1 = [];

	/** @var list<string> */
	public array $loop2 = [];

	/** @var list<string> */
	public array $loop3 = [];

}

/**
 * Regression test for loop widening expanding a shape with optional keys into every variant.
 *
 * Nine conditional writes give $values nine optional keys, and the append in each loop makes
 * the loop's key optional too. Widening the loop's scope flattened both sides with
 * TypeUtils::flattenTypes(), which turns a shape with N optional keys into its 2^N concrete
 * variants, only for the union right after to merge the thousand of them back into the same
 * shape pair by pair. MutatingScope::generalizeType() now splits only unions.
 *
 * `bin/phpstan analyse -l 8 --debug` on this file: 4.6 s on 2.3.x, 1.5 s with the fix.
 */
final class Analysis
{

	/**
	 * @return array<string, list<string>>
	 */
	public function convert1(Answer $answer): array
	{
		$values = ['fields' => ['answer']];
		if ($answer->list1 !== []) {
			$values['list1'] = array_map(static fn (string $value): string => strtolower($value), $answer->list1);
		}
		if ($answer->list2 !== []) {
			$values['list2'] = array_map(static fn (string $value): string => strtolower($value), $answer->list2);
		}
		if ($answer->list3 !== []) {
			$values['list3'] = array_map(static fn (string $value): string => strtolower($value), $answer->list3);
		}
		if ($answer->list4 !== []) {
			$values['list4'] = array_map(static fn (string $value): string => strtolower($value), $answer->list4);
		}
		if ($answer->list5 !== []) {
			$values['list5'] = array_map(static fn (string $value): string => strtolower($value), $answer->list5);
		}
		if ($answer->list6 !== []) {
			$values['list6'] = array_map(static fn (string $value): string => strtolower($value), $answer->list6);
		}
		if ($answer->list7 !== []) {
			$values['list7'] = array_map(static fn (string $value): string => strtolower($value), $answer->list7);
		}
		if ($answer->list8 !== []) {
			$values['list8'] = array_map(static fn (string $value): string => strtolower($value), $answer->list8);
		}
		if ($answer->list9 !== []) {
			$values['list9'] = array_map(static fn (string $value): string => strtolower($value), $answer->list9);
		}

		foreach ($answer->loop1 as $value) {
			if ($value !== '') {
				$values['items'][] = $value;
			}
		}
		foreach ($answer->loop2 as $value) {
			if ($value !== '') {
				$values['items'][] = $value;
			}
		}
		foreach ($answer->loop3 as $value) {
			if ($value !== '') {
				$values['items'][] = $value;
			}
		}

		return $values;
	}

	/**
	 * @return array<string, list<string>>
	 */
	public function convert2(Answer $answer): array
	{
		$values = ['fields' => ['answer']];
		if ($answer->list1 !== []) {
			$values['list1'] = array_map(static fn (string $value): string => strtolower($value), $answer->list1);
		}
		if ($answer->list2 !== []) {
			$values['list2'] = array_map(static fn (string $value): string => strtolower($value), $answer->list2);
		}
		if ($answer->list3 !== []) {
			$values['list3'] = array_map(static fn (string $value): string => strtolower($value), $answer->list3);
		}
		if ($answer->list4 !== []) {
			$values['list4'] = array_map(static fn (string $value): string => strtolower($value), $answer->list4);
		}
		if ($answer->list5 !== []) {
			$values['list5'] = array_map(static fn (string $value): string => strtolower($value), $answer->list5);
		}
		if ($answer->list6 !== []) {
			$values['list6'] = array_map(static fn (string $value): string => strtolower($value), $answer->list6);
		}
		if ($answer->list7 !== []) {
			$values['list7'] = array_map(static fn (string $value): string => strtolower($value), $answer->list7);
		}
		if ($answer->list8 !== []) {
			$values['list8'] = array_map(static fn (string $value): string => strtolower($value), $answer->list8);
		}
		if ($answer->list9 !== []) {
			$values['list9'] = array_map(static fn (string $value): string => strtolower($value), $answer->list9);
		}

		foreach ($answer->loop1 as $value) {
			if ($value !== '') {
				$values['items'][] = $value;
			}
		}
		foreach ($answer->loop2 as $value) {
			if ($value !== '') {
				$values['items'][] = $value;
			}
		}
		foreach ($answer->loop3 as $value) {
			if ($value !== '') {
				$values['items'][] = $value;
			}
		}

		return $values;
	}

	/**
	 * @return array<string, list<string>>
	 */
	public function convert3(Answer $answer): array
	{
		$values = ['fields' => ['answer']];
		if ($answer->list1 !== []) {
			$values['list1'] = array_map(static fn (string $value): string => strtolower($value), $answer->list1);
		}
		if ($answer->list2 !== []) {
			$values['list2'] = array_map(static fn (string $value): string => strtolower($value), $answer->list2);
		}
		if ($answer->list3 !== []) {
			$values['list3'] = array_map(static fn (string $value): string => strtolower($value), $answer->list3);
		}
		if ($answer->list4 !== []) {
			$values['list4'] = array_map(static fn (string $value): string => strtolower($value), $answer->list4);
		}
		if ($answer->list5 !== []) {
			$values['list5'] = array_map(static fn (string $value): string => strtolower($value), $answer->list5);
		}
		if ($answer->list6 !== []) {
			$values['list6'] = array_map(static fn (string $value): string => strtolower($value), $answer->list6);
		}
		if ($answer->list7 !== []) {
			$values['list7'] = array_map(static fn (string $value): string => strtolower($value), $answer->list7);
		}
		if ($answer->list8 !== []) {
			$values['list8'] = array_map(static fn (string $value): string => strtolower($value), $answer->list8);
		}
		if ($answer->list9 !== []) {
			$values['list9'] = array_map(static fn (string $value): string => strtolower($value), $answer->list9);
		}

		foreach ($answer->loop1 as $value) {
			if ($value !== '') {
				$values['items'][] = $value;
			}
		}
		foreach ($answer->loop2 as $value) {
			if ($value !== '') {
				$values['items'][] = $value;
			}
		}
		foreach ($answer->loop3 as $value) {
			if ($value !== '') {
				$values['items'][] = $value;
			}
		}

		return $values;
	}

	/**
	 * @return array<string, list<string>>
	 */
	public function convert4(Answer $answer): array
	{
		$values = ['fields' => ['answer']];
		if ($answer->list1 !== []) {
			$values['list1'] = array_map(static fn (string $value): string => strtolower($value), $answer->list1);
		}
		if ($answer->list2 !== []) {
			$values['list2'] = array_map(static fn (string $value): string => strtolower($value), $answer->list2);
		}
		if ($answer->list3 !== []) {
			$values['list3'] = array_map(static fn (string $value): string => strtolower($value), $answer->list3);
		}
		if ($answer->list4 !== []) {
			$values['list4'] = array_map(static fn (string $value): string => strtolower($value), $answer->list4);
		}
		if ($answer->list5 !== []) {
			$values['list5'] = array_map(static fn (string $value): string => strtolower($value), $answer->list5);
		}
		if ($answer->list6 !== []) {
			$values['list6'] = array_map(static fn (string $value): string => strtolower($value), $answer->list6);
		}
		if ($answer->list7 !== []) {
			$values['list7'] = array_map(static fn (string $value): string => strtolower($value), $answer->list7);
		}
		if ($answer->list8 !== []) {
			$values['list8'] = array_map(static fn (string $value): string => strtolower($value), $answer->list8);
		}
		if ($answer->list9 !== []) {
			$values['list9'] = array_map(static fn (string $value): string => strtolower($value), $answer->list9);
		}

		foreach ($answer->loop1 as $value) {
			if ($value !== '') {
				$values['items'][] = $value;
			}
		}
		foreach ($answer->loop2 as $value) {
			if ($value !== '') {
				$values['items'][] = $value;
			}
		}
		foreach ($answer->loop3 as $value) {
			if ($value !== '') {
				$values['items'][] = $value;
			}
		}

		return $values;
	}

}
