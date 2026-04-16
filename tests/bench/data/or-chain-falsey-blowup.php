<?php declare(strict_types = 1);

namespace BenchOrChainFalseyBlowup;

/**
 * Regression test for O(N²) in specifyTypesForFlattenedBooleanOr falsey path.
 * Each unionWith call incrementally grew the sureNotTypes union,
 * causing O(N²) TypeCombinator::union() work. The fix batches all
 * per-expression types and builds unions once at the end.
 *
 * Slow with BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH raised to 128.
 */
function test(string $x): void
{
	if (
		$x === 'v001' || $x === 'v002' || $x === 'v003' || $x === 'v004' || $x === 'v005' ||
		$x === 'v006' || $x === 'v007' || $x === 'v008' || $x === 'v009' || $x === 'v010' ||
		$x === 'v011' || $x === 'v012' || $x === 'v013' || $x === 'v014' || $x === 'v015' ||
		$x === 'v016' || $x === 'v017' || $x === 'v018' || $x === 'v019' || $x === 'v020' ||
		$x === 'v021' || $x === 'v022' || $x === 'v023' || $x === 'v024' || $x === 'v025' ||
		$x === 'v026' || $x === 'v027' || $x === 'v028' || $x === 'v029' || $x === 'v030' ||
		$x === 'v031' || $x === 'v032' || $x === 'v033' || $x === 'v034' || $x === 'v035' ||
		$x === 'v036' || $x === 'v037' || $x === 'v038' || $x === 'v039' || $x === 'v040' ||
		$x === 'v041' || $x === 'v042' || $x === 'v043' || $x === 'v044' || $x === 'v045' ||
		$x === 'v046' || $x === 'v047' || $x === 'v048' || $x === 'v049' || $x === 'v050' ||
		$x === 'v051' || $x === 'v052' || $x === 'v053' || $x === 'v054' || $x === 'v055' ||
		$x === 'v056' || $x === 'v057' || $x === 'v058' || $x === 'v059' || $x === 'v060' ||
		$x === 'v061' || $x === 'v062' || $x === 'v063' || $x === 'v064' || $x === 'v065' ||
		$x === 'v066' || $x === 'v067' || $x === 'v068' || $x === 'v069' || $x === 'v070' ||
		$x === 'v071' || $x === 'v072' || $x === 'v073' || $x === 'v074' || $x === 'v075' ||
		$x === 'v076' || $x === 'v077' || $x === 'v078' || $x === 'v079' || $x === 'v080' ||
		$x === 'v081' || $x === 'v082' || $x === 'v083' || $x === 'v084' || $x === 'v085' ||
		$x === 'v086' || $x === 'v087' || $x === 'v088' || $x === 'v089' || $x === 'v090' ||
		$x === 'v091' || $x === 'v092' || $x === 'v093' || $x === 'v094' || $x === 'v095' ||
		$x === 'v096' || $x === 'v097' || $x === 'v098' || $x === 'v099' || $x === 'v100'
	) {
		echo $x;
	}
}
