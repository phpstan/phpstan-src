<?php declare(strict_types = 1);

namespace Bug14996;

/**
 * Every method is wrongly marked with #[\Override], so every single one of them
 * reports a fixable error. Turning one fixable error into a diff means walking
 * the AST of the whole file and printing it back, which makes files with many
 * fixable errors the worst case for PHPStan's error transforming.
 */
final class ManyOverrideAttributes
{

	#[\Override]
	public function method1(int $i, string $s): string
	{
		if ($i > 1) {
			return $s . '1';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method2(int $i, string $s): string
	{
		if ($i > 2) {
			return $s . '2';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method3(int $i, string $s): string
	{
		if ($i > 3) {
			return $s . '3';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method4(int $i, string $s): string
	{
		if ($i > 4) {
			return $s . '4';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method5(int $i, string $s): string
	{
		if ($i > 5) {
			return $s . '5';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method6(int $i, string $s): string
	{
		if ($i > 6) {
			return $s . '6';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method7(int $i, string $s): string
	{
		if ($i > 7) {
			return $s . '7';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method8(int $i, string $s): string
	{
		if ($i > 8) {
			return $s . '8';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method9(int $i, string $s): string
	{
		if ($i > 9) {
			return $s . '9';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method10(int $i, string $s): string
	{
		if ($i > 10) {
			return $s . '10';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method11(int $i, string $s): string
	{
		if ($i > 11) {
			return $s . '11';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method12(int $i, string $s): string
	{
		if ($i > 12) {
			return $s . '12';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method13(int $i, string $s): string
	{
		if ($i > 13) {
			return $s . '13';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method14(int $i, string $s): string
	{
		if ($i > 14) {
			return $s . '14';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method15(int $i, string $s): string
	{
		if ($i > 15) {
			return $s . '15';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method16(int $i, string $s): string
	{
		if ($i > 16) {
			return $s . '16';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method17(int $i, string $s): string
	{
		if ($i > 17) {
			return $s . '17';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method18(int $i, string $s): string
	{
		if ($i > 18) {
			return $s . '18';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method19(int $i, string $s): string
	{
		if ($i > 19) {
			return $s . '19';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method20(int $i, string $s): string
	{
		if ($i > 20) {
			return $s . '20';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method21(int $i, string $s): string
	{
		if ($i > 21) {
			return $s . '21';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method22(int $i, string $s): string
	{
		if ($i > 22) {
			return $s . '22';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method23(int $i, string $s): string
	{
		if ($i > 23) {
			return $s . '23';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method24(int $i, string $s): string
	{
		if ($i > 24) {
			return $s . '24';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method25(int $i, string $s): string
	{
		if ($i > 25) {
			return $s . '25';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method26(int $i, string $s): string
	{
		if ($i > 26) {
			return $s . '26';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method27(int $i, string $s): string
	{
		if ($i > 27) {
			return $s . '27';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method28(int $i, string $s): string
	{
		if ($i > 28) {
			return $s . '28';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method29(int $i, string $s): string
	{
		if ($i > 29) {
			return $s . '29';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method30(int $i, string $s): string
	{
		if ($i > 30) {
			return $s . '30';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method31(int $i, string $s): string
	{
		if ($i > 31) {
			return $s . '31';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method32(int $i, string $s): string
	{
		if ($i > 32) {
			return $s . '32';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method33(int $i, string $s): string
	{
		if ($i > 33) {
			return $s . '33';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method34(int $i, string $s): string
	{
		if ($i > 34) {
			return $s . '34';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method35(int $i, string $s): string
	{
		if ($i > 35) {
			return $s . '35';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method36(int $i, string $s): string
	{
		if ($i > 36) {
			return $s . '36';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method37(int $i, string $s): string
	{
		if ($i > 37) {
			return $s . '37';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method38(int $i, string $s): string
	{
		if ($i > 38) {
			return $s . '38';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method39(int $i, string $s): string
	{
		if ($i > 39) {
			return $s . '39';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method40(int $i, string $s): string
	{
		if ($i > 40) {
			return $s . '40';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method41(int $i, string $s): string
	{
		if ($i > 41) {
			return $s . '41';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method42(int $i, string $s): string
	{
		if ($i > 42) {
			return $s . '42';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method43(int $i, string $s): string
	{
		if ($i > 43) {
			return $s . '43';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method44(int $i, string $s): string
	{
		if ($i > 44) {
			return $s . '44';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method45(int $i, string $s): string
	{
		if ($i > 45) {
			return $s . '45';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method46(int $i, string $s): string
	{
		if ($i > 46) {
			return $s . '46';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method47(int $i, string $s): string
	{
		if ($i > 47) {
			return $s . '47';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method48(int $i, string $s): string
	{
		if ($i > 48) {
			return $s . '48';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method49(int $i, string $s): string
	{
		if ($i > 49) {
			return $s . '49';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method50(int $i, string $s): string
	{
		if ($i > 50) {
			return $s . '50';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method51(int $i, string $s): string
	{
		if ($i > 51) {
			return $s . '51';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method52(int $i, string $s): string
	{
		if ($i > 52) {
			return $s . '52';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method53(int $i, string $s): string
	{
		if ($i > 53) {
			return $s . '53';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method54(int $i, string $s): string
	{
		if ($i > 54) {
			return $s . '54';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method55(int $i, string $s): string
	{
		if ($i > 55) {
			return $s . '55';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method56(int $i, string $s): string
	{
		if ($i > 56) {
			return $s . '56';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method57(int $i, string $s): string
	{
		if ($i > 57) {
			return $s . '57';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method58(int $i, string $s): string
	{
		if ($i > 58) {
			return $s . '58';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method59(int $i, string $s): string
	{
		if ($i > 59) {
			return $s . '59';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method60(int $i, string $s): string
	{
		if ($i > 60) {
			return $s . '60';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method61(int $i, string $s): string
	{
		if ($i > 61) {
			return $s . '61';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method62(int $i, string $s): string
	{
		if ($i > 62) {
			return $s . '62';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method63(int $i, string $s): string
	{
		if ($i > 63) {
			return $s . '63';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method64(int $i, string $s): string
	{
		if ($i > 64) {
			return $s . '64';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method65(int $i, string $s): string
	{
		if ($i > 65) {
			return $s . '65';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method66(int $i, string $s): string
	{
		if ($i > 66) {
			return $s . '66';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method67(int $i, string $s): string
	{
		if ($i > 67) {
			return $s . '67';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method68(int $i, string $s): string
	{
		if ($i > 68) {
			return $s . '68';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method69(int $i, string $s): string
	{
		if ($i > 69) {
			return $s . '69';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method70(int $i, string $s): string
	{
		if ($i > 70) {
			return $s . '70';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method71(int $i, string $s): string
	{
		if ($i > 71) {
			return $s . '71';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method72(int $i, string $s): string
	{
		if ($i > 72) {
			return $s . '72';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method73(int $i, string $s): string
	{
		if ($i > 73) {
			return $s . '73';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method74(int $i, string $s): string
	{
		if ($i > 74) {
			return $s . '74';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method75(int $i, string $s): string
	{
		if ($i > 75) {
			return $s . '75';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method76(int $i, string $s): string
	{
		if ($i > 76) {
			return $s . '76';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method77(int $i, string $s): string
	{
		if ($i > 77) {
			return $s . '77';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method78(int $i, string $s): string
	{
		if ($i > 78) {
			return $s . '78';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method79(int $i, string $s): string
	{
		if ($i > 79) {
			return $s . '79';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method80(int $i, string $s): string
	{
		if ($i > 80) {
			return $s . '80';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method81(int $i, string $s): string
	{
		if ($i > 81) {
			return $s . '81';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method82(int $i, string $s): string
	{
		if ($i > 82) {
			return $s . '82';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method83(int $i, string $s): string
	{
		if ($i > 83) {
			return $s . '83';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method84(int $i, string $s): string
	{
		if ($i > 84) {
			return $s . '84';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method85(int $i, string $s): string
	{
		if ($i > 85) {
			return $s . '85';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method86(int $i, string $s): string
	{
		if ($i > 86) {
			return $s . '86';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method87(int $i, string $s): string
	{
		if ($i > 87) {
			return $s . '87';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method88(int $i, string $s): string
	{
		if ($i > 88) {
			return $s . '88';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method89(int $i, string $s): string
	{
		if ($i > 89) {
			return $s . '89';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method90(int $i, string $s): string
	{
		if ($i > 90) {
			return $s . '90';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method91(int $i, string $s): string
	{
		if ($i > 91) {
			return $s . '91';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method92(int $i, string $s): string
	{
		if ($i > 92) {
			return $s . '92';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method93(int $i, string $s): string
	{
		if ($i > 93) {
			return $s . '93';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method94(int $i, string $s): string
	{
		if ($i > 94) {
			return $s . '94';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method95(int $i, string $s): string
	{
		if ($i > 95) {
			return $s . '95';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method96(int $i, string $s): string
	{
		if ($i > 96) {
			return $s . '96';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method97(int $i, string $s): string
	{
		if ($i > 97) {
			return $s . '97';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method98(int $i, string $s): string
	{
		if ($i > 98) {
			return $s . '98';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method99(int $i, string $s): string
	{
		if ($i > 99) {
			return $s . '99';
		}

		return strrev($s) . $i;
	}

	#[\Override]
	public function method100(int $i, string $s): string
	{
		if ($i > 100) {
			return $s . '100';
		}

		return strrev($s) . $i;
	}

}
