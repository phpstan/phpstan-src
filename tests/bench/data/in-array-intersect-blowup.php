<?php declare(strict_types = 1);

namespace BenchInArrayIntersectBlowup;

/**
 * Regression test for exponential blowup in InArrayFunctionTypeSpecifyingExtension::computeNeedleNarrowingType().
 *
 * The method intersects per-array guaranteed value types. When the array parameter is a union of N constant
 * arrays, each with M guaranteed values, an N-ary TypeCombinator::intersect() call causes M^N combinations
 * via the distributive law. Pairwise folding avoids this by letting intermediate intersections simplify early.
 *
 * @param 'v1'|'v2'|'v3' $needle
 * @param 'a'|'b'|'c'|'d'|'e' $variant
 */
function testInArray(string $needle, string $variant): void
{
	$arr = match($variant) {
		'a' => ['a1', 'a2', 'a3', 'a4', 'a5', 'a6', 'a7', 'a8', 'a9', 'a10', 'a11', 'a12', 'a13', 'a14', 'a15', 'a16', 'a17', 'a18', 'a19', 'a20', 'a21', 'a22', 'a23', 'a24', 'a25'],
		'b' => ['b1', 'b2', 'b3', 'b4', 'b5', 'b6', 'b7', 'b8', 'b9', 'b10', 'b11', 'b12', 'b13', 'b14', 'b15', 'b16', 'b17', 'b18', 'b19', 'b20', 'b21', 'b22', 'b23', 'b24', 'b25'],
		'c' => ['c1', 'c2', 'c3', 'c4', 'c5', 'c6', 'c7', 'c8', 'c9', 'c10', 'c11', 'c12', 'c13', 'c14', 'c15', 'c16', 'c17', 'c18', 'c19', 'c20', 'c21', 'c22', 'c23', 'c24', 'c25'],
		'd' => ['d1', 'd2', 'd3', 'd4', 'd5', 'd6', 'd7', 'd8', 'd9', 'd10', 'd11', 'd12', 'd13', 'd14', 'd15', 'd16', 'd17', 'd18', 'd19', 'd20', 'd21', 'd22', 'd23', 'd24', 'd25'],
		'e' => ['e1', 'e2', 'e3', 'e4', 'e5', 'e6', 'e7', 'e8', 'e9', 'e10', 'e11', 'e12', 'e13', 'e14', 'e15', 'e16', 'e17', 'e18', 'e19', 'e20', 'e21', 'e22', 'e23', 'e24', 'e25'],
	};

	if (!in_array($needle, $arr, true)) {
		echo $needle;
	}
}
