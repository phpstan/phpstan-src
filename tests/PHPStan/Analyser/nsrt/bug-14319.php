<?php // lint >= 8.0

namespace Bug14319;

use function PHPStan\Testing\assertType;

function foo(string $a, int $b): array|object
{
	return $a;
}


final class test
{
	protected function edit(int|string|null $IdNum = null): void
	{
		$rows = foo("SELECT *", $IdNum);
		assertType('array|object', $rows);

		if ($_POST['edycja'] === 'edycja' ) {
			$raport = '';
			if ($rows['rap_tr']) {
				$raport .= 'T: '.$rows['rap_tr'].", \n";
			}
			if ($rows['rap_ks']) {
				$raport .= 'K: '.$rows['rap_ks'].", \n";
			}
			if ($rows['rap_br']) {
				$raport .= 'B: '.$rows['rap_br'].", \n";
			}
			if ($rows['rap_cz']) {
				$raport .= 'C: '.$rows['rap_cz'].", \n";
			}
			if ($rows['rap_fil']) {
				$raport .= 'Fil: '.$rows['rap_fil'].", \n";
			}
			if ($rows['rap_roz']) {
				$raport .= 'Roz: '.$rows['rap_roz'].", \n";
			}
			if ($rows['rap_roz2']) {
				$raport .= 'Roz: '.$rows['rap_roz2'].", \n";
			}
			if ($rows['rap_roz3']) {
				$raport .= 'Roz: '.$rows['rap_roz3'].", \n";
			}
			assertType("(non-empty-array&hasOffsetValue('rap_br', mixed)&hasOffsetValue('rap_cz', mixed)&hasOffsetValue('rap_fil', mixed)&hasOffsetValue('rap_ks', mixed)&hasOffsetValue('rap_roz', mixed)&hasOffsetValue('rap_roz2', mixed)&hasOffsetValue('rap_roz3', mixed)&hasOffsetValue('rap_tr', mixed))|(ArrayAccess&hasOffsetValue('rap_br', mixed)&hasOffsetValue('rap_cz', mixed)&hasOffsetValue('rap_fil', mixed)&hasOffsetValue('rap_ks', mixed)&hasOffsetValue('rap_roz', mixed)&hasOffsetValue('rap_roz2', mixed)&hasOffsetValue('rap_roz3', mixed)&hasOffsetValue('rap_tr', mixed))", $rows);
		}
	}
}
