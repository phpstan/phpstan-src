<?php declare(strict_types = 1);

namespace BenchOrChainResolveTypeBlowup;

/**
 * Regression test for O(N^2) in deep BooleanOr chains resolved via BooleanOrHandler::resolveType().
 * Without the flattening, resolving the boolean type of the chain recursed into the left operand
 * and re-narrowed the whole left chain with filterByFalseyValue() at each level, creating O(N^2)
 * scope operations. Uses instanceof arms so the per-level narrowing is expensive enough to show
 * at the BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH = 4 default (no raised cap needed).
 */
final class C1 {}
final class C2 {}
final class C3 {}
final class C4 {}
final class C5 {}
final class C6 {}
final class C7 {}
final class C8 {}
final class C9 {}
final class C10 {}
final class C11 {}
final class C12 {}
final class C13 {}
final class C14 {}
final class C15 {}
final class C16 {}
final class C17 {}
final class C18 {}
final class C19 {}
final class C20 {}
final class C21 {}
final class C22 {}
final class C23 {}
final class C24 {}
final class C25 {}
final class C26 {}
final class C27 {}
final class C28 {}
final class C29 {}
final class C30 {}
final class C31 {}
final class C32 {}
final class C33 {}
final class C34 {}
final class C35 {}
final class C36 {}
final class C37 {}
final class C38 {}
final class C39 {}
final class C40 {}
final class C41 {}
final class C42 {}
final class C43 {}
final class C44 {}
final class C45 {}
final class C46 {}
final class C47 {}
final class C48 {}
final class C49 {}
final class C50 {}
final class C51 {}
final class C52 {}
final class C53 {}
final class C54 {}
final class C55 {}
final class C56 {}
final class C57 {}
final class C58 {}
final class C59 {}
final class C60 {}
final class C61 {}
final class C62 {}
final class C63 {}
final class C64 {}
final class C65 {}
final class C66 {}
final class C67 {}
final class C68 {}
final class C69 {}
final class C70 {}
final class C71 {}
final class C72 {}
final class C73 {}
final class C74 {}
final class C75 {}
final class C76 {}
final class C77 {}
final class C78 {}
final class C79 {}
final class C80 {}
final class C81 {}
final class C82 {}
final class C83 {}
final class C84 {}
final class C85 {}
final class C86 {}
final class C87 {}
final class C88 {}
final class C89 {}
final class C90 {}
final class C91 {}
final class C92 {}
final class C93 {}
final class C94 {}
final class C95 {}
final class C96 {}
final class C97 {}
final class C98 {}
final class C99 {}
final class C100 {}

function test(object $x): void {
	if ($x instanceof C1 || $x instanceof C2 || $x instanceof C3 || $x instanceof C4 || $x instanceof C5 || $x instanceof C6 || $x instanceof C7 || $x instanceof C8 || $x instanceof C9 || $x instanceof C10 || $x instanceof C11 || $x instanceof C12 || $x instanceof C13 || $x instanceof C14 || $x instanceof C15 || $x instanceof C16 || $x instanceof C17 || $x instanceof C18 || $x instanceof C19 || $x instanceof C20 || $x instanceof C21 || $x instanceof C22 || $x instanceof C23 || $x instanceof C24 || $x instanceof C25 || $x instanceof C26 || $x instanceof C27 || $x instanceof C28 || $x instanceof C29 || $x instanceof C30 || $x instanceof C31 || $x instanceof C32 || $x instanceof C33 || $x instanceof C34 || $x instanceof C35 || $x instanceof C36 || $x instanceof C37 || $x instanceof C38 || $x instanceof C39 || $x instanceof C40 || $x instanceof C41 || $x instanceof C42 || $x instanceof C43 || $x instanceof C44 || $x instanceof C45 || $x instanceof C46 || $x instanceof C47 || $x instanceof C48 || $x instanceof C49 || $x instanceof C50 || $x instanceof C51 || $x instanceof C52 || $x instanceof C53 || $x instanceof C54 || $x instanceof C55 || $x instanceof C56 || $x instanceof C57 || $x instanceof C58 || $x instanceof C59 || $x instanceof C60 || $x instanceof C61 || $x instanceof C62 || $x instanceof C63 || $x instanceof C64 || $x instanceof C65 || $x instanceof C66 || $x instanceof C67 || $x instanceof C68 || $x instanceof C69 || $x instanceof C70 || $x instanceof C71 || $x instanceof C72 || $x instanceof C73 || $x instanceof C74 || $x instanceof C75 || $x instanceof C76 || $x instanceof C77 || $x instanceof C78 || $x instanceof C79 || $x instanceof C80 || $x instanceof C81 || $x instanceof C82 || $x instanceof C83 || $x instanceof C84 || $x instanceof C85 || $x instanceof C86 || $x instanceof C87 || $x instanceof C88 || $x instanceof C89 || $x instanceof C90 || $x instanceof C91 || $x instanceof C92 || $x instanceof C93 || $x instanceof C94 || $x instanceof C95 || $x instanceof C96 || $x instanceof C97 || $x instanceof C98 || $x instanceof C99 || $x instanceof C100) {
		echo "yes";
	}
}
