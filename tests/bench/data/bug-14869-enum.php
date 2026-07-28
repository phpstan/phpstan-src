<?php // lint >= 8.1

namespace Bug14869Enum;

/**
 * Exercises the enum-case branch of the finite-union fast path in
 * TypeCombinator::intersect().
 *
 * Assigning an enum case under many `$x === Enum::Case` branches over a large
 * backed enum makes the conditional-expression machinery repeatedly intersect
 * the growing enum-case union with the narrowed union. Before the fast path
 * this was super-linear (~30s at N=400); keying members by class + case name
 * turns each intersect into an O(n) set intersection. phpstan/phpstan#14869.
 */

enum E: int
{
	case C0 = 0;
	case C1 = 1;
	case C2 = 2;
	case C3 = 3;
	case C4 = 4;
	case C5 = 5;
	case C6 = 6;
	case C7 = 7;
	case C8 = 8;
	case C9 = 9;
	case C10 = 10;
	case C11 = 11;
	case C12 = 12;
	case C13 = 13;
	case C14 = 14;
	case C15 = 15;
	case C16 = 16;
	case C17 = 17;
	case C18 = 18;
	case C19 = 19;
	case C20 = 20;
	case C21 = 21;
	case C22 = 22;
	case C23 = 23;
	case C24 = 24;
	case C25 = 25;
	case C26 = 26;
	case C27 = 27;
	case C28 = 28;
	case C29 = 29;
	case C30 = 30;
	case C31 = 31;
	case C32 = 32;
	case C33 = 33;
	case C34 = 34;
	case C35 = 35;
	case C36 = 36;
	case C37 = 37;
	case C38 = 38;
	case C39 = 39;
	case C40 = 40;
	case C41 = 41;
	case C42 = 42;
	case C43 = 43;
	case C44 = 44;
	case C45 = 45;
	case C46 = 46;
	case C47 = 47;
	case C48 = 48;
	case C49 = 49;
	case C50 = 50;
	case C51 = 51;
	case C52 = 52;
	case C53 = 53;
	case C54 = 54;
	case C55 = 55;
	case C56 = 56;
	case C57 = 57;
	case C58 = 58;
	case C59 = 59;
	case C60 = 60;
	case C61 = 61;
	case C62 = 62;
	case C63 = 63;
	case C64 = 64;
	case C65 = 65;
	case C66 = 66;
	case C67 = 67;
	case C68 = 68;
	case C69 = 69;
	case C70 = 70;
	case C71 = 71;
	case C72 = 72;
	case C73 = 73;
	case C74 = 74;
	case C75 = 75;
	case C76 = 76;
	case C77 = 77;
	case C78 = 78;
	case C79 = 79;
	case C80 = 80;
	case C81 = 81;
	case C82 = 82;
	case C83 = 83;
	case C84 = 84;
	case C85 = 85;
	case C86 = 86;
	case C87 = 87;
	case C88 = 88;
	case C89 = 89;
	case C90 = 90;
	case C91 = 91;
	case C92 = 92;
	case C93 = 93;
	case C94 = 94;
	case C95 = 95;
	case C96 = 96;
	case C97 = 97;
	case C98 = 98;
	case C99 = 99;
	case C100 = 100;
	case C101 = 101;
	case C102 = 102;
	case C103 = 103;
	case C104 = 104;
	case C105 = 105;
	case C106 = 106;
	case C107 = 107;
	case C108 = 108;
	case C109 = 109;
	case C110 = 110;
	case C111 = 111;
	case C112 = 112;
	case C113 = 113;
	case C114 = 114;
	case C115 = 115;
	case C116 = 116;
	case C117 = 117;
	case C118 = 118;
	case C119 = 119;
}

function enumChain(E $x): E
{
	$v = E::C0;
	if ($x === E::C0) { $v = E::C0; }
	if ($x === E::C1) { $v = E::C1; }
	if ($x === E::C2) { $v = E::C2; }
	if ($x === E::C3) { $v = E::C3; }
	if ($x === E::C4) { $v = E::C4; }
	if ($x === E::C5) { $v = E::C5; }
	if ($x === E::C6) { $v = E::C6; }
	if ($x === E::C7) { $v = E::C7; }
	if ($x === E::C8) { $v = E::C8; }
	if ($x === E::C9) { $v = E::C9; }
	if ($x === E::C10) { $v = E::C10; }
	if ($x === E::C11) { $v = E::C11; }
	if ($x === E::C12) { $v = E::C12; }
	if ($x === E::C13) { $v = E::C13; }
	if ($x === E::C14) { $v = E::C14; }
	if ($x === E::C15) { $v = E::C15; }
	if ($x === E::C16) { $v = E::C16; }
	if ($x === E::C17) { $v = E::C17; }
	if ($x === E::C18) { $v = E::C18; }
	if ($x === E::C19) { $v = E::C19; }
	if ($x === E::C20) { $v = E::C20; }
	if ($x === E::C21) { $v = E::C21; }
	if ($x === E::C22) { $v = E::C22; }
	if ($x === E::C23) { $v = E::C23; }
	if ($x === E::C24) { $v = E::C24; }
	if ($x === E::C25) { $v = E::C25; }
	if ($x === E::C26) { $v = E::C26; }
	if ($x === E::C27) { $v = E::C27; }
	if ($x === E::C28) { $v = E::C28; }
	if ($x === E::C29) { $v = E::C29; }
	if ($x === E::C30) { $v = E::C30; }
	if ($x === E::C31) { $v = E::C31; }
	if ($x === E::C32) { $v = E::C32; }
	if ($x === E::C33) { $v = E::C33; }
	if ($x === E::C34) { $v = E::C34; }
	if ($x === E::C35) { $v = E::C35; }
	if ($x === E::C36) { $v = E::C36; }
	if ($x === E::C37) { $v = E::C37; }
	if ($x === E::C38) { $v = E::C38; }
	if ($x === E::C39) { $v = E::C39; }
	if ($x === E::C40) { $v = E::C40; }
	if ($x === E::C41) { $v = E::C41; }
	if ($x === E::C42) { $v = E::C42; }
	if ($x === E::C43) { $v = E::C43; }
	if ($x === E::C44) { $v = E::C44; }
	if ($x === E::C45) { $v = E::C45; }
	if ($x === E::C46) { $v = E::C46; }
	if ($x === E::C47) { $v = E::C47; }
	if ($x === E::C48) { $v = E::C48; }
	if ($x === E::C49) { $v = E::C49; }
	if ($x === E::C50) { $v = E::C50; }
	if ($x === E::C51) { $v = E::C51; }
	if ($x === E::C52) { $v = E::C52; }
	if ($x === E::C53) { $v = E::C53; }
	if ($x === E::C54) { $v = E::C54; }
	if ($x === E::C55) { $v = E::C55; }
	if ($x === E::C56) { $v = E::C56; }
	if ($x === E::C57) { $v = E::C57; }
	if ($x === E::C58) { $v = E::C58; }
	if ($x === E::C59) { $v = E::C59; }
	if ($x === E::C60) { $v = E::C60; }
	if ($x === E::C61) { $v = E::C61; }
	if ($x === E::C62) { $v = E::C62; }
	if ($x === E::C63) { $v = E::C63; }
	if ($x === E::C64) { $v = E::C64; }
	if ($x === E::C65) { $v = E::C65; }
	if ($x === E::C66) { $v = E::C66; }
	if ($x === E::C67) { $v = E::C67; }
	if ($x === E::C68) { $v = E::C68; }
	if ($x === E::C69) { $v = E::C69; }
	if ($x === E::C70) { $v = E::C70; }
	if ($x === E::C71) { $v = E::C71; }
	if ($x === E::C72) { $v = E::C72; }
	if ($x === E::C73) { $v = E::C73; }
	if ($x === E::C74) { $v = E::C74; }
	if ($x === E::C75) { $v = E::C75; }
	if ($x === E::C76) { $v = E::C76; }
	if ($x === E::C77) { $v = E::C77; }
	if ($x === E::C78) { $v = E::C78; }
	if ($x === E::C79) { $v = E::C79; }
	if ($x === E::C80) { $v = E::C80; }
	if ($x === E::C81) { $v = E::C81; }
	if ($x === E::C82) { $v = E::C82; }
	if ($x === E::C83) { $v = E::C83; }
	if ($x === E::C84) { $v = E::C84; }
	if ($x === E::C85) { $v = E::C85; }
	if ($x === E::C86) { $v = E::C86; }
	if ($x === E::C87) { $v = E::C87; }
	if ($x === E::C88) { $v = E::C88; }
	if ($x === E::C89) { $v = E::C89; }
	if ($x === E::C90) { $v = E::C90; }
	if ($x === E::C91) { $v = E::C91; }
	if ($x === E::C92) { $v = E::C92; }
	if ($x === E::C93) { $v = E::C93; }
	if ($x === E::C94) { $v = E::C94; }
	if ($x === E::C95) { $v = E::C95; }
	if ($x === E::C96) { $v = E::C96; }
	if ($x === E::C97) { $v = E::C97; }
	if ($x === E::C98) { $v = E::C98; }
	if ($x === E::C99) { $v = E::C99; }
	if ($x === E::C100) { $v = E::C100; }
	if ($x === E::C101) { $v = E::C101; }
	if ($x === E::C102) { $v = E::C102; }
	if ($x === E::C103) { $v = E::C103; }
	if ($x === E::C104) { $v = E::C104; }
	if ($x === E::C105) { $v = E::C105; }
	if ($x === E::C106) { $v = E::C106; }
	if ($x === E::C107) { $v = E::C107; }
	if ($x === E::C108) { $v = E::C108; }
	if ($x === E::C109) { $v = E::C109; }
	if ($x === E::C110) { $v = E::C110; }
	if ($x === E::C111) { $v = E::C111; }
	if ($x === E::C112) { $v = E::C112; }
	if ($x === E::C113) { $v = E::C113; }
	if ($x === E::C114) { $v = E::C114; }
	if ($x === E::C115) { $v = E::C115; }
	if ($x === E::C116) { $v = E::C116; }
	if ($x === E::C117) { $v = E::C117; }
	if ($x === E::C118) { $v = E::C118; }
	if ($x === E::C119) { $v = E::C119; }

	return $v;
}
