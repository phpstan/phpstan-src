<?php declare(strict_types = 1);

/*
 * instructions: 5.902B
 * cycles: 6.890B
 * memory: 79.85MiB
 */

namespace ManyStoredConditions;

/** @param array<int, ?int> $in */
function f(array $in): int
{
	$sum = 0;
	$x0 = $in[0];
	$ok0 = $x0 !== null;
	if ($ok0) { $sum += 1; }
	$x1 = $in[1];
	$ok1 = $x1 !== null;
	if ($ok1) { $sum += 1; }
	$x2 = $in[2];
	$ok2 = $x2 !== null;
	if ($ok2) { $sum += 1; }
	$x3 = $in[3];
	$ok3 = $x3 !== null;
	if ($ok3) { $sum += 1; }
	$x4 = $in[4];
	$ok4 = $x4 !== null;
	if ($ok4) { $sum += 1; }
	$x5 = $in[5];
	$ok5 = $x5 !== null;
	if ($ok5) { $sum += 1; }
	$x6 = $in[6];
	$ok6 = $x6 !== null;
	if ($ok6) { $sum += 1; }
	$x7 = $in[7];
	$ok7 = $x7 !== null;
	if ($ok7) { $sum += 1; }
	$x8 = $in[8];
	$ok8 = $x8 !== null;
	if ($ok8) { $sum += 1; }
	$x9 = $in[9];
	$ok9 = $x9 !== null;
	if ($ok9) { $sum += 1; }
	$x10 = $in[10];
	$ok10 = $x10 !== null;
	if ($ok10) { $sum += 1; }
	$x11 = $in[11];
	$ok11 = $x11 !== null;
	if ($ok11) { $sum += 1; }
	$x12 = $in[12];
	$ok12 = $x12 !== null;
	if ($ok12) { $sum += 1; }
	$x13 = $in[13];
	$ok13 = $x13 !== null;
	if ($ok13) { $sum += 1; }
	$x14 = $in[14];
	$ok14 = $x14 !== null;
	if ($ok14) { $sum += 1; }
	$x15 = $in[15];
	$ok15 = $x15 !== null;
	if ($ok15) { $sum += 1; }
	$x16 = $in[16];
	$ok16 = $x16 !== null;
	if ($ok16) { $sum += 1; }
	$x17 = $in[17];
	$ok17 = $x17 !== null;
	if ($ok17) { $sum += 1; }
	$x18 = $in[18];
	$ok18 = $x18 !== null;
	if ($ok18) { $sum += 1; }
	$x19 = $in[19];
	$ok19 = $x19 !== null;
	if ($ok19) { $sum += 1; }
	$x20 = $in[20];
	$ok20 = $x20 !== null;
	if ($ok20) { $sum += 1; }
	$x21 = $in[21];
	$ok21 = $x21 !== null;
	if ($ok21) { $sum += 1; }
	$x22 = $in[22];
	$ok22 = $x22 !== null;
	if ($ok22) { $sum += 1; }
	$x23 = $in[23];
	$ok23 = $x23 !== null;
	if ($ok23) { $sum += 1; }
	$x24 = $in[24];
	$ok24 = $x24 !== null;
	if ($ok24) { $sum += 1; }
	$x25 = $in[25];
	$ok25 = $x25 !== null;
	if ($ok25) { $sum += 1; }
	$x26 = $in[26];
	$ok26 = $x26 !== null;
	if ($ok26) { $sum += 1; }
	$x27 = $in[27];
	$ok27 = $x27 !== null;
	if ($ok27) { $sum += 1; }
	$x28 = $in[28];
	$ok28 = $x28 !== null;
	if ($ok28) { $sum += 1; }
	$x29 = $in[29];
	$ok29 = $x29 !== null;
	if ($ok29) { $sum += 1; }
	$x30 = $in[30];
	$ok30 = $x30 !== null;
	if ($ok30) { $sum += 1; }
	$x31 = $in[31];
	$ok31 = $x31 !== null;
	if ($ok31) { $sum += 1; }
	$x32 = $in[32];
	$ok32 = $x32 !== null;
	if ($ok32) { $sum += 1; }
	$x33 = $in[33];
	$ok33 = $x33 !== null;
	if ($ok33) { $sum += 1; }
	$x34 = $in[34];
	$ok34 = $x34 !== null;
	if ($ok34) { $sum += 1; }
	$x35 = $in[35];
	$ok35 = $x35 !== null;
	if ($ok35) { $sum += 1; }
	$x36 = $in[36];
	$ok36 = $x36 !== null;
	if ($ok36) { $sum += 1; }
	$x37 = $in[37];
	$ok37 = $x37 !== null;
	if ($ok37) { $sum += 1; }
	$x38 = $in[38];
	$ok38 = $x38 !== null;
	if ($ok38) { $sum += 1; }
	$x39 = $in[39];
	$ok39 = $x39 !== null;
	if ($ok39) { $sum += 1; }
	$x40 = $in[40];
	$ok40 = $x40 !== null;
	if ($ok40) { $sum += 1; }
	$x41 = $in[41];
	$ok41 = $x41 !== null;
	if ($ok41) { $sum += 1; }
	$x42 = $in[42];
	$ok42 = $x42 !== null;
	if ($ok42) { $sum += 1; }
	$x43 = $in[43];
	$ok43 = $x43 !== null;
	if ($ok43) { $sum += 1; }
	$x44 = $in[44];
	$ok44 = $x44 !== null;
	if ($ok44) { $sum += 1; }
	$x45 = $in[45];
	$ok45 = $x45 !== null;
	if ($ok45) { $sum += 1; }
	$x46 = $in[46];
	$ok46 = $x46 !== null;
	if ($ok46) { $sum += 1; }
	$x47 = $in[47];
	$ok47 = $x47 !== null;
	if ($ok47) { $sum += 1; }
	$x48 = $in[48];
	$ok48 = $x48 !== null;
	if ($ok48) { $sum += 1; }
	$x49 = $in[49];
	$ok49 = $x49 !== null;
	if ($ok49) { $sum += 1; }
	$x50 = $in[50];
	$ok50 = $x50 !== null;
	if ($ok50) { $sum += 1; }
	$x51 = $in[51];
	$ok51 = $x51 !== null;
	if ($ok51) { $sum += 1; }
	$x52 = $in[52];
	$ok52 = $x52 !== null;
	if ($ok52) { $sum += 1; }
	$x53 = $in[53];
	$ok53 = $x53 !== null;
	if ($ok53) { $sum += 1; }
	$x54 = $in[54];
	$ok54 = $x54 !== null;
	if ($ok54) { $sum += 1; }
	$x55 = $in[55];
	$ok55 = $x55 !== null;
	if ($ok55) { $sum += 1; }
	$x56 = $in[56];
	$ok56 = $x56 !== null;
	if ($ok56) { $sum += 1; }
	$x57 = $in[57];
	$ok57 = $x57 !== null;
	if ($ok57) { $sum += 1; }
	$x58 = $in[58];
	$ok58 = $x58 !== null;
	if ($ok58) { $sum += 1; }
	$x59 = $in[59];
	$ok59 = $x59 !== null;
	if ($ok59) { $sum += 1; }
	$x60 = $in[60];
	$ok60 = $x60 !== null;
	if ($ok60) { $sum += 1; }
	$x61 = $in[61];
	$ok61 = $x61 !== null;
	if ($ok61) { $sum += 1; }
	$x62 = $in[62];
	$ok62 = $x62 !== null;
	if ($ok62) { $sum += 1; }
	$x63 = $in[63];
	$ok63 = $x63 !== null;
	if ($ok63) { $sum += 1; }
	$x64 = $in[64];
	$ok64 = $x64 !== null;
	if ($ok64) { $sum += 1; }
	$x65 = $in[65];
	$ok65 = $x65 !== null;
	if ($ok65) { $sum += 1; }
	$x66 = $in[66];
	$ok66 = $x66 !== null;
	if ($ok66) { $sum += 1; }
	$x67 = $in[67];
	$ok67 = $x67 !== null;
	if ($ok67) { $sum += 1; }
	$x68 = $in[68];
	$ok68 = $x68 !== null;
	if ($ok68) { $sum += 1; }
	$x69 = $in[69];
	$ok69 = $x69 !== null;
	if ($ok69) { $sum += 1; }
	$x70 = $in[70];
	$ok70 = $x70 !== null;
	if ($ok70) { $sum += 1; }
	$x71 = $in[71];
	$ok71 = $x71 !== null;
	if ($ok71) { $sum += 1; }
	$x72 = $in[72];
	$ok72 = $x72 !== null;
	if ($ok72) { $sum += 1; }
	$x73 = $in[73];
	$ok73 = $x73 !== null;
	if ($ok73) { $sum += 1; }
	$x74 = $in[74];
	$ok74 = $x74 !== null;
	if ($ok74) { $sum += 1; }
	$x75 = $in[75];
	$ok75 = $x75 !== null;
	if ($ok75) { $sum += 1; }
	$x76 = $in[76];
	$ok76 = $x76 !== null;
	if ($ok76) { $sum += 1; }
	$x77 = $in[77];
	$ok77 = $x77 !== null;
	if ($ok77) { $sum += 1; }
	$x78 = $in[78];
	$ok78 = $x78 !== null;
	if ($ok78) { $sum += 1; }
	$x79 = $in[79];
	$ok79 = $x79 !== null;
	if ($ok79) { $sum += 1; }
	$x80 = $in[80];
	$ok80 = $x80 !== null;
	if ($ok80) { $sum += 1; }
	$x81 = $in[81];
	$ok81 = $x81 !== null;
	if ($ok81) { $sum += 1; }
	$x82 = $in[82];
	$ok82 = $x82 !== null;
	if ($ok82) { $sum += 1; }
	$x83 = $in[83];
	$ok83 = $x83 !== null;
	if ($ok83) { $sum += 1; }
	$x84 = $in[84];
	$ok84 = $x84 !== null;
	if ($ok84) { $sum += 1; }
	$x85 = $in[85];
	$ok85 = $x85 !== null;
	if ($ok85) { $sum += 1; }
	$x86 = $in[86];
	$ok86 = $x86 !== null;
	if ($ok86) { $sum += 1; }
	$x87 = $in[87];
	$ok87 = $x87 !== null;
	if ($ok87) { $sum += 1; }
	$x88 = $in[88];
	$ok88 = $x88 !== null;
	if ($ok88) { $sum += 1; }
	$x89 = $in[89];
	$ok89 = $x89 !== null;
	if ($ok89) { $sum += 1; }
	$x90 = $in[90];
	$ok90 = $x90 !== null;
	if ($ok90) { $sum += 1; }
	$x91 = $in[91];
	$ok91 = $x91 !== null;
	if ($ok91) { $sum += 1; }
	$x92 = $in[92];
	$ok92 = $x92 !== null;
	if ($ok92) { $sum += 1; }
	$x93 = $in[93];
	$ok93 = $x93 !== null;
	if ($ok93) { $sum += 1; }
	$x94 = $in[94];
	$ok94 = $x94 !== null;
	if ($ok94) { $sum += 1; }
	$x95 = $in[95];
	$ok95 = $x95 !== null;
	if ($ok95) { $sum += 1; }
	$x96 = $in[96];
	$ok96 = $x96 !== null;
	if ($ok96) { $sum += 1; }
	$x97 = $in[97];
	$ok97 = $x97 !== null;
	if ($ok97) { $sum += 1; }
	$x98 = $in[98];
	$ok98 = $x98 !== null;
	if ($ok98) { $sum += 1; }
	$x99 = $in[99];
	$ok99 = $x99 !== null;
	if ($ok99) { $sum += 1; }
	$x100 = $in[100];
	$ok100 = $x100 !== null;
	if ($ok100) { $sum += 1; }
	$x101 = $in[101];
	$ok101 = $x101 !== null;
	if ($ok101) { $sum += 1; }
	$x102 = $in[102];
	$ok102 = $x102 !== null;
	if ($ok102) { $sum += 1; }
	$x103 = $in[103];
	$ok103 = $x103 !== null;
	if ($ok103) { $sum += 1; }
	$x104 = $in[104];
	$ok104 = $x104 !== null;
	if ($ok104) { $sum += 1; }
	$x105 = $in[105];
	$ok105 = $x105 !== null;
	if ($ok105) { $sum += 1; }
	$x106 = $in[106];
	$ok106 = $x106 !== null;
	if ($ok106) { $sum += 1; }
	$x107 = $in[107];
	$ok107 = $x107 !== null;
	if ($ok107) { $sum += 1; }
	$x108 = $in[108];
	$ok108 = $x108 !== null;
	if ($ok108) { $sum += 1; }
	$x109 = $in[109];
	$ok109 = $x109 !== null;
	if ($ok109) { $sum += 1; }
	$x110 = $in[110];
	$ok110 = $x110 !== null;
	if ($ok110) { $sum += 1; }
	$x111 = $in[111];
	$ok111 = $x111 !== null;
	if ($ok111) { $sum += 1; }
	$x112 = $in[112];
	$ok112 = $x112 !== null;
	if ($ok112) { $sum += 1; }
	$x113 = $in[113];
	$ok113 = $x113 !== null;
	if ($ok113) { $sum += 1; }
	$x114 = $in[114];
	$ok114 = $x114 !== null;
	if ($ok114) { $sum += 1; }
	$x115 = $in[115];
	$ok115 = $x115 !== null;
	if ($ok115) { $sum += 1; }
	$x116 = $in[116];
	$ok116 = $x116 !== null;
	if ($ok116) { $sum += 1; }
	$x117 = $in[117];
	$ok117 = $x117 !== null;
	if ($ok117) { $sum += 1; }
	$x118 = $in[118];
	$ok118 = $x118 !== null;
	if ($ok118) { $sum += 1; }
	$x119 = $in[119];
	$ok119 = $x119 !== null;
	if ($ok119) { $sum += 1; }
	$x120 = $in[120];
	$ok120 = $x120 !== null;
	if ($ok120) { $sum += 1; }
	$x121 = $in[121];
	$ok121 = $x121 !== null;
	if ($ok121) { $sum += 1; }
	$x122 = $in[122];
	$ok122 = $x122 !== null;
	if ($ok122) { $sum += 1; }
	$x123 = $in[123];
	$ok123 = $x123 !== null;
	if ($ok123) { $sum += 1; }
	$x124 = $in[124];
	$ok124 = $x124 !== null;
	if ($ok124) { $sum += 1; }
	$x125 = $in[125];
	$ok125 = $x125 !== null;
	if ($ok125) { $sum += 1; }
	$x126 = $in[126];
	$ok126 = $x126 !== null;
	if ($ok126) { $sum += 1; }
	$x127 = $in[127];
	$ok127 = $x127 !== null;
	if ($ok127) { $sum += 1; }
	$x128 = $in[128];
	$ok128 = $x128 !== null;
	if ($ok128) { $sum += 1; }
	$x129 = $in[129];
	$ok129 = $x129 !== null;
	if ($ok129) { $sum += 1; }
	$x130 = $in[130];
	$ok130 = $x130 !== null;
	if ($ok130) { $sum += 1; }
	$x131 = $in[131];
	$ok131 = $x131 !== null;
	if ($ok131) { $sum += 1; }
	$x132 = $in[132];
	$ok132 = $x132 !== null;
	if ($ok132) { $sum += 1; }
	$x133 = $in[133];
	$ok133 = $x133 !== null;
	if ($ok133) { $sum += 1; }
	$x134 = $in[134];
	$ok134 = $x134 !== null;
	if ($ok134) { $sum += 1; }
	$x135 = $in[135];
	$ok135 = $x135 !== null;
	if ($ok135) { $sum += 1; }
	$x136 = $in[136];
	$ok136 = $x136 !== null;
	if ($ok136) { $sum += 1; }
	$x137 = $in[137];
	$ok137 = $x137 !== null;
	if ($ok137) { $sum += 1; }
	$x138 = $in[138];
	$ok138 = $x138 !== null;
	if ($ok138) { $sum += 1; }
	$x139 = $in[139];
	$ok139 = $x139 !== null;
	if ($ok139) { $sum += 1; }
	$x140 = $in[140];
	$ok140 = $x140 !== null;
	if ($ok140) { $sum += 1; }
	$x141 = $in[141];
	$ok141 = $x141 !== null;
	if ($ok141) { $sum += 1; }
	$x142 = $in[142];
	$ok142 = $x142 !== null;
	if ($ok142) { $sum += 1; }
	$x143 = $in[143];
	$ok143 = $x143 !== null;
	if ($ok143) { $sum += 1; }
	$x144 = $in[144];
	$ok144 = $x144 !== null;
	if ($ok144) { $sum += 1; }
	$x145 = $in[145];
	$ok145 = $x145 !== null;
	if ($ok145) { $sum += 1; }
	$x146 = $in[146];
	$ok146 = $x146 !== null;
	if ($ok146) { $sum += 1; }
	$x147 = $in[147];
	$ok147 = $x147 !== null;
	if ($ok147) { $sum += 1; }
	$x148 = $in[148];
	$ok148 = $x148 !== null;
	if ($ok148) { $sum += 1; }
	$x149 = $in[149];
	$ok149 = $x149 !== null;
	if ($ok149) { $sum += 1; }
	return $sum;
}
