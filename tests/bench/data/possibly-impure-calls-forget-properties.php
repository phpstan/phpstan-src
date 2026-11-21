<?php declare(strict_types = 1);

/*
 * instructions: 14.36B
 * cycles: 17.44B
 * memory: 174.1MiB
 */

namespace BenchPossiblyImpureCallsForgetProperties;

/**
 * Narrows many variables and properties, then makes many calls to a method that is not known to
 * be pure. Analysed with rememberPossiblyImpureFunctionValues off by the .neon file of the same
 * name, so each call forgets the narrowed properties of $this, scanning every narrowed
 * expression. possibly-impure-calls-remember-properties.php is the same code analysed with the
 * option on.
 */
final class Builder
{

	private int $counter = 0;

	private ?string $label0 = null;

	private ?string $label1 = null;

	private ?string $label2 = null;

	private ?string $label3 = null;

	public function __construct(bool $labelled)
	{
		if (!$labelled) {
			return;
		}

		$this->label0 = 'label';
		$this->label1 = 'label';
		$this->label2 = 'label';
		$this->label3 = 'label';
	}

	private function nextId(): int
	{
		return ++$this->counter;
	}

	public function build(
		?int $a0,
		?int $b0,
		?int $a1,
		?int $b1,
		?int $a2,
		?int $b2,
		?int $a3,
		?int $b3,
		?int $a4,
		?int $b4,
		?int $a5,
		?int $b5,
		?int $a6,
		?int $b6,
		?int $a7,
		?int $b7,
		?int $a8,
		?int $b8,
		?int $a9,
		?int $b9,
		?int $a10,
		?int $b10,
		?int $a11,
		?int $b11,
		?int $a12,
		?int $b12,
		?int $a13,
		?int $b13,
		?int $a14,
		?int $b14,
		?int $a15,
		?int $b15,
		?int $a16,
		?int $b16,
		?int $a17,
		?int $b17,
		?int $a18,
		?int $b18,
		?int $a19,
		?int $b19,
		?int $a20,
		?int $b20,
		?int $a21,
		?int $b21,
		?int $a22,
		?int $b22,
		?int $a23,
		?int $b23,
		?int $a24,
		?int $b24,
		?int $a25,
		?int $b25,
		?int $a26,
		?int $b26,
		?int $a27,
		?int $b27,
		?int $a28,
		?int $b28,
		?int $a29,
		?int $b29,
		?int $a30,
		?int $b30,
		?int $a31,
		?int $b31,
		?int $a32,
		?int $b32,
		?int $a33,
		?int $b33,
		?int $a34,
		?int $b34,
		?int $a35,
		?int $b35,
		?int $a36,
		?int $b36,
		?int $a37,
		?int $b37,
		?int $a38,
		?int $b38,
		?int $a39,
		?int $b39,
		?int $a40,
		?int $b40,
		?int $a41,
		?int $b41,
		?int $a42,
		?int $b42,
		?int $a43,
		?int $b43,
		?int $a44,
		?int $b44,
		?int $a45,
		?int $b45,
		?int $a46,
		?int $b46,
		?int $a47,
		?int $b47,
		?int $a48,
		?int $b48,
		?int $a49,
		?int $b49,
		?int $a50,
		?int $b50,
		?int $a51,
		?int $b51,
		?int $a52,
		?int $b52,
		?int $a53,
		?int $b53,
		?int $a54,
		?int $b54,
		?int $a55,
		?int $b55,
		?int $a56,
		?int $b56,
		?int $a57,
		?int $b57,
		?int $a58,
		?int $b58,
		?int $a59,
		?int $b59,
	): int
	{
		if ($this->label0 === null) {
			return 0;
		}
		if ($this->label1 === null) {
			return 0;
		}
		if ($this->label2 === null) {
			return 0;
		}
		if ($this->label3 === null) {
			return 0;
		}
		if ($a0 === null) {
			return 0;
		}
		if ($a1 === null) {
			return 0;
		}
		if ($a2 === null) {
			return 0;
		}
		if ($a3 === null) {
			return 0;
		}
		if ($a4 === null) {
			return 0;
		}
		if ($a5 === null) {
			return 0;
		}
		if ($a6 === null) {
			return 0;
		}
		if ($a7 === null) {
			return 0;
		}
		if ($a8 === null) {
			return 0;
		}
		if ($a9 === null) {
			return 0;
		}
		if ($a10 === null) {
			return 0;
		}
		if ($a11 === null) {
			return 0;
		}
		if ($a12 === null) {
			return 0;
		}
		if ($a13 === null) {
			return 0;
		}
		if ($a14 === null) {
			return 0;
		}
		if ($a15 === null) {
			return 0;
		}
		if ($a16 === null) {
			return 0;
		}
		if ($a17 === null) {
			return 0;
		}
		if ($a18 === null) {
			return 0;
		}
		if ($a19 === null) {
			return 0;
		}
		if ($a20 === null) {
			return 0;
		}
		if ($a21 === null) {
			return 0;
		}
		if ($a22 === null) {
			return 0;
		}
		if ($a23 === null) {
			return 0;
		}
		if ($a24 === null) {
			return 0;
		}
		if ($a25 === null) {
			return 0;
		}
		if ($a26 === null) {
			return 0;
		}
		if ($a27 === null) {
			return 0;
		}
		if ($a28 === null) {
			return 0;
		}
		if ($a29 === null) {
			return 0;
		}
		if ($a30 === null) {
			return 0;
		}
		if ($a31 === null) {
			return 0;
		}
		if ($a32 === null) {
			return 0;
		}
		if ($a33 === null) {
			return 0;
		}
		if ($a34 === null) {
			return 0;
		}
		if ($a35 === null) {
			return 0;
		}
		if ($a36 === null) {
			return 0;
		}
		if ($a37 === null) {
			return 0;
		}
		if ($a38 === null) {
			return 0;
		}
		if ($a39 === null) {
			return 0;
		}
		if ($a40 === null) {
			return 0;
		}
		if ($a41 === null) {
			return 0;
		}
		if ($a42 === null) {
			return 0;
		}
		if ($a43 === null) {
			return 0;
		}
		if ($a44 === null) {
			return 0;
		}
		if ($a45 === null) {
			return 0;
		}
		if ($a46 === null) {
			return 0;
		}
		if ($a47 === null) {
			return 0;
		}
		if ($a48 === null) {
			return 0;
		}
		if ($a49 === null) {
			return 0;
		}
		if ($a50 === null) {
			return 0;
		}
		if ($a51 === null) {
			return 0;
		}
		if ($a52 === null) {
			return 0;
		}
		if ($a53 === null) {
			return 0;
		}
		if ($a54 === null) {
			return 0;
		}
		if ($a55 === null) {
			return 0;
		}
		if ($a56 === null) {
			return 0;
		}
		if ($a57 === null) {
			return 0;
		}
		if ($a58 === null) {
			return 0;
		}
		if ($a59 === null) {
			return 0;
		}
		$hasB0 = $b0 !== null;
		$hasB1 = $b1 !== null;
		$hasB2 = $b2 !== null;
		$hasB3 = $b3 !== null;
		$hasB4 = $b4 !== null;
		$hasB5 = $b5 !== null;
		$hasB6 = $b6 !== null;
		$hasB7 = $b7 !== null;
		$hasB8 = $b8 !== null;
		$hasB9 = $b9 !== null;
		$hasB10 = $b10 !== null;
		$hasB11 = $b11 !== null;
		$hasB12 = $b12 !== null;
		$hasB13 = $b13 !== null;
		$hasB14 = $b14 !== null;
		$hasB15 = $b15 !== null;
		$hasB16 = $b16 !== null;
		$hasB17 = $b17 !== null;
		$hasB18 = $b18 !== null;
		$hasB19 = $b19 !== null;
		$hasB20 = $b20 !== null;
		$hasB21 = $b21 !== null;
		$hasB22 = $b22 !== null;
		$hasB23 = $b23 !== null;
		$hasB24 = $b24 !== null;
		$hasB25 = $b25 !== null;
		$hasB26 = $b26 !== null;
		$hasB27 = $b27 !== null;
		$hasB28 = $b28 !== null;
		$hasB29 = $b29 !== null;
		$hasB30 = $b30 !== null;
		$hasB31 = $b31 !== null;
		$hasB32 = $b32 !== null;
		$hasB33 = $b33 !== null;
		$hasB34 = $b34 !== null;
		$hasB35 = $b35 !== null;
		$hasB36 = $b36 !== null;
		$hasB37 = $b37 !== null;
		$hasB38 = $b38 !== null;
		$hasB39 = $b39 !== null;
		$hasB40 = $b40 !== null;
		$hasB41 = $b41 !== null;
		$hasB42 = $b42 !== null;
		$hasB43 = $b43 !== null;
		$hasB44 = $b44 !== null;
		$hasB45 = $b45 !== null;
		$hasB46 = $b46 !== null;
		$hasB47 = $b47 !== null;
		$hasB48 = $b48 !== null;
		$hasB49 = $b49 !== null;
		$hasB50 = $b50 !== null;
		$hasB51 = $b51 !== null;
		$hasB52 = $b52 !== null;
		$hasB53 = $b53 !== null;
		$hasB54 = $b54 !== null;
		$hasB55 = $b55 !== null;
		$hasB56 = $b56 !== null;
		$hasB57 = $b57 !== null;
		$hasB58 = $b58 !== null;
		$hasB59 = $b59 !== null;

		$total = 0;
		$total += $this->nextId() + $a0;
		$total += $this->nextId() + $a1;
		$total += $this->nextId() + $a2;
		$total += $this->nextId() + $a3;
		if ($hasB0) {
			$total += $b0;
		}
		$total += $this->nextId() + $a4;
		$total += $this->nextId() + $a5;
		$total += $this->nextId() + $a6;
		$total += $this->nextId() + $a7;
		if ($hasB1) {
			$total += $b1;
		}
		$total += $this->nextId() + $a8;
		$total += $this->nextId() + $a9;
		$total += $this->nextId() + $a10;
		$total += $this->nextId() + $a11;
		if ($hasB2) {
			$total += $b2;
		}
		$total += $this->nextId() + $a12;
		$total += $this->nextId() + $a13;
		$total += $this->nextId() + $a14;
		$total += $this->nextId() + $a15;
		if ($hasB3) {
			$total += $b3;
		}
		$total += $this->nextId() + $a16;
		$total += $this->nextId() + $a17;
		$total += $this->nextId() + $a18;
		$total += $this->nextId() + $a19;
		if ($hasB4) {
			$total += $b4;
		}
		$total += $this->nextId() + $a20;
		$total += $this->nextId() + $a21;
		$total += $this->nextId() + $a22;
		$total += $this->nextId() + $a23;
		if ($hasB5) {
			$total += $b5;
		}
		$total += $this->nextId() + $a24;
		$total += $this->nextId() + $a25;
		$total += $this->nextId() + $a26;
		$total += $this->nextId() + $a27;
		if ($hasB6) {
			$total += $b6;
		}
		$total += $this->nextId() + $a28;
		$total += $this->nextId() + $a29;
		$total += $this->nextId() + $a30;
		$total += $this->nextId() + $a31;
		if ($hasB7) {
			$total += $b7;
		}
		$total += $this->nextId() + $a32;
		$total += $this->nextId() + $a33;
		$total += $this->nextId() + $a34;
		$total += $this->nextId() + $a35;
		if ($hasB8) {
			$total += $b8;
		}
		$total += $this->nextId() + $a36;
		$total += $this->nextId() + $a37;
		$total += $this->nextId() + $a38;
		$total += $this->nextId() + $a39;
		if ($hasB9) {
			$total += $b9;
		}
		$total += $this->nextId() + $a40;
		$total += $this->nextId() + $a41;
		$total += $this->nextId() + $a42;
		$total += $this->nextId() + $a43;
		if ($hasB10) {
			$total += $b10;
		}
		$total += $this->nextId() + $a44;
		$total += $this->nextId() + $a45;
		$total += $this->nextId() + $a46;
		$total += $this->nextId() + $a47;
		if ($hasB11) {
			$total += $b11;
		}
		$total += $this->nextId() + $a48;
		$total += $this->nextId() + $a49;
		$total += $this->nextId() + $a50;
		$total += $this->nextId() + $a51;
		if ($hasB12) {
			$total += $b12;
		}
		$total += $this->nextId() + $a52;
		$total += $this->nextId() + $a53;
		$total += $this->nextId() + $a54;
		$total += $this->nextId() + $a55;
		if ($hasB13) {
			$total += $b13;
		}
		$total += $this->nextId() + $a56;
		$total += $this->nextId() + $a57;
		$total += $this->nextId() + $a58;
		$total += $this->nextId() + $a59;
		if ($hasB14) {
			$total += $b14;
		}
		$total += $this->nextId() + $a0;
		$total += $this->nextId() + $a1;
		$total += $this->nextId() + $a2;
		$total += $this->nextId() + $a3;
		if ($hasB15) {
			$total += $b15;
		}
		$total += $this->nextId() + $a4;
		$total += $this->nextId() + $a5;
		$total += $this->nextId() + $a6;
		$total += $this->nextId() + $a7;
		if ($hasB16) {
			$total += $b16;
		}
		$total += $this->nextId() + $a8;
		$total += $this->nextId() + $a9;
		$total += $this->nextId() + $a10;
		$total += $this->nextId() + $a11;
		if ($hasB17) {
			$total += $b17;
		}
		$total += $this->nextId() + $a12;
		$total += $this->nextId() + $a13;
		$total += $this->nextId() + $a14;
		$total += $this->nextId() + $a15;
		if ($hasB18) {
			$total += $b18;
		}
		$total += $this->nextId() + $a16;
		$total += $this->nextId() + $a17;
		$total += $this->nextId() + $a18;
		$total += $this->nextId() + $a19;
		if ($hasB19) {
			$total += $b19;
		}
		$total += $this->nextId() + $a20;
		$total += $this->nextId() + $a21;
		$total += $this->nextId() + $a22;
		$total += $this->nextId() + $a23;
		if ($hasB20) {
			$total += $b20;
		}
		$total += $this->nextId() + $a24;
		$total += $this->nextId() + $a25;
		$total += $this->nextId() + $a26;
		$total += $this->nextId() + $a27;
		if ($hasB21) {
			$total += $b21;
		}
		$total += $this->nextId() + $a28;
		$total += $this->nextId() + $a29;
		$total += $this->nextId() + $a30;
		$total += $this->nextId() + $a31;
		if ($hasB22) {
			$total += $b22;
		}
		$total += $this->nextId() + $a32;
		$total += $this->nextId() + $a33;
		$total += $this->nextId() + $a34;
		$total += $this->nextId() + $a35;
		if ($hasB23) {
			$total += $b23;
		}
		$total += $this->nextId() + $a36;
		$total += $this->nextId() + $a37;
		$total += $this->nextId() + $a38;
		$total += $this->nextId() + $a39;
		if ($hasB24) {
			$total += $b24;
		}
		$total += $this->nextId() + $a40;
		$total += $this->nextId() + $a41;
		$total += $this->nextId() + $a42;
		$total += $this->nextId() + $a43;
		if ($hasB25) {
			$total += $b25;
		}
		$total += $this->nextId() + $a44;
		$total += $this->nextId() + $a45;
		$total += $this->nextId() + $a46;
		$total += $this->nextId() + $a47;
		if ($hasB26) {
			$total += $b26;
		}
		$total += $this->nextId() + $a48;
		$total += $this->nextId() + $a49;
		$total += $this->nextId() + $a50;
		$total += $this->nextId() + $a51;
		if ($hasB27) {
			$total += $b27;
		}
		$total += $this->nextId() + $a52;
		$total += $this->nextId() + $a53;
		$total += $this->nextId() + $a54;
		$total += $this->nextId() + $a55;
		if ($hasB28) {
			$total += $b28;
		}
		$total += $this->nextId() + $a56;
		$total += $this->nextId() + $a57;
		$total += $this->nextId() + $a58;
		$total += $this->nextId() + $a59;
		if ($hasB29) {
			$total += $b29;
		}
		$total += $this->nextId() + $a0;
		$total += $this->nextId() + $a1;
		$total += $this->nextId() + $a2;
		$total += $this->nextId() + $a3;
		if ($hasB30) {
			$total += $b30;
		}
		$total += $this->nextId() + $a4;
		$total += $this->nextId() + $a5;
		$total += $this->nextId() + $a6;
		$total += $this->nextId() + $a7;
		if ($hasB31) {
			$total += $b31;
		}
		$total += $this->nextId() + $a8;
		$total += $this->nextId() + $a9;
		$total += $this->nextId() + $a10;
		$total += $this->nextId() + $a11;
		if ($hasB32) {
			$total += $b32;
		}
		$total += $this->nextId() + $a12;
		$total += $this->nextId() + $a13;
		$total += $this->nextId() + $a14;
		$total += $this->nextId() + $a15;
		if ($hasB33) {
			$total += $b33;
		}
		$total += $this->nextId() + $a16;
		$total += $this->nextId() + $a17;
		$total += $this->nextId() + $a18;
		$total += $this->nextId() + $a19;
		if ($hasB34) {
			$total += $b34;
		}
		$total += $this->nextId() + $a20;
		$total += $this->nextId() + $a21;
		$total += $this->nextId() + $a22;
		$total += $this->nextId() + $a23;
		if ($hasB35) {
			$total += $b35;
		}
		$total += $this->nextId() + $a24;
		$total += $this->nextId() + $a25;
		$total += $this->nextId() + $a26;
		$total += $this->nextId() + $a27;
		if ($hasB36) {
			$total += $b36;
		}
		$total += $this->nextId() + $a28;
		$total += $this->nextId() + $a29;
		$total += $this->nextId() + $a30;
		$total += $this->nextId() + $a31;
		if ($hasB37) {
			$total += $b37;
		}
		$total += $this->nextId() + $a32;
		$total += $this->nextId() + $a33;
		$total += $this->nextId() + $a34;
		$total += $this->nextId() + $a35;
		if ($hasB38) {
			$total += $b38;
		}
		$total += $this->nextId() + $a36;
		$total += $this->nextId() + $a37;
		$total += $this->nextId() + $a38;
		$total += $this->nextId() + $a39;
		if ($hasB39) {
			$total += $b39;
		}
		$total += $this->nextId() + $a40;
		$total += $this->nextId() + $a41;
		$total += $this->nextId() + $a42;
		$total += $this->nextId() + $a43;
		if ($hasB40) {
			$total += $b40;
		}
		$total += $this->nextId() + $a44;
		$total += $this->nextId() + $a45;
		$total += $this->nextId() + $a46;
		$total += $this->nextId() + $a47;
		if ($hasB41) {
			$total += $b41;
		}
		$total += $this->nextId() + $a48;
		$total += $this->nextId() + $a49;
		$total += $this->nextId() + $a50;
		$total += $this->nextId() + $a51;
		if ($hasB42) {
			$total += $b42;
		}
		$total += $this->nextId() + $a52;
		$total += $this->nextId() + $a53;
		$total += $this->nextId() + $a54;
		$total += $this->nextId() + $a55;
		if ($hasB43) {
			$total += $b43;
		}
		$total += $this->nextId() + $a56;
		$total += $this->nextId() + $a57;
		$total += $this->nextId() + $a58;
		$total += $this->nextId() + $a59;
		if ($hasB44) {
			$total += $b44;
		}
		$total += $this->nextId() + $a0;
		$total += $this->nextId() + $a1;
		$total += $this->nextId() + $a2;
		$total += $this->nextId() + $a3;
		if ($hasB45) {
			$total += $b45;
		}
		$total += $this->nextId() + $a4;
		$total += $this->nextId() + $a5;
		$total += $this->nextId() + $a6;
		$total += $this->nextId() + $a7;
		if ($hasB46) {
			$total += $b46;
		}
		$total += $this->nextId() + $a8;
		$total += $this->nextId() + $a9;
		$total += $this->nextId() + $a10;
		$total += $this->nextId() + $a11;
		if ($hasB47) {
			$total += $b47;
		}
		$total += $this->nextId() + $a12;
		$total += $this->nextId() + $a13;
		$total += $this->nextId() + $a14;
		$total += $this->nextId() + $a15;
		if ($hasB48) {
			$total += $b48;
		}
		$total += $this->nextId() + $a16;
		$total += $this->nextId() + $a17;
		$total += $this->nextId() + $a18;
		$total += $this->nextId() + $a19;
		if ($hasB49) {
			$total += $b49;
		}
		$total += $this->nextId() + $a20;
		$total += $this->nextId() + $a21;
		$total += $this->nextId() + $a22;
		$total += $this->nextId() + $a23;
		if ($hasB50) {
			$total += $b50;
		}
		$total += $this->nextId() + $a24;
		$total += $this->nextId() + $a25;
		$total += $this->nextId() + $a26;
		$total += $this->nextId() + $a27;
		if ($hasB51) {
			$total += $b51;
		}
		$total += $this->nextId() + $a28;
		$total += $this->nextId() + $a29;
		$total += $this->nextId() + $a30;
		$total += $this->nextId() + $a31;
		if ($hasB52) {
			$total += $b52;
		}
		$total += $this->nextId() + $a32;
		$total += $this->nextId() + $a33;
		$total += $this->nextId() + $a34;
		$total += $this->nextId() + $a35;
		if ($hasB53) {
			$total += $b53;
		}
		$total += $this->nextId() + $a36;
		$total += $this->nextId() + $a37;
		$total += $this->nextId() + $a38;
		$total += $this->nextId() + $a39;
		if ($hasB54) {
			$total += $b54;
		}
		$total += $this->nextId() + $a40;
		$total += $this->nextId() + $a41;
		$total += $this->nextId() + $a42;
		$total += $this->nextId() + $a43;
		if ($hasB55) {
			$total += $b55;
		}
		$total += $this->nextId() + $a44;
		$total += $this->nextId() + $a45;
		$total += $this->nextId() + $a46;
		$total += $this->nextId() + $a47;
		if ($hasB56) {
			$total += $b56;
		}
		$total += $this->nextId() + $a48;
		$total += $this->nextId() + $a49;
		$total += $this->nextId() + $a50;
		$total += $this->nextId() + $a51;
		if ($hasB57) {
			$total += $b57;
		}
		$total += $this->nextId() + $a52;
		$total += $this->nextId() + $a53;
		$total += $this->nextId() + $a54;
		$total += $this->nextId() + $a55;
		if ($hasB58) {
			$total += $b58;
		}
		$total += $this->nextId() + $a56;
		$total += $this->nextId() + $a57;
		$total += $this->nextId() + $a58;
		$total += $this->nextId() + $a59;
		if ($hasB59) {
			$total += $b59;
		}
		$total += $this->nextId() + $a0;
		$total += $this->nextId() + $a1;
		$total += $this->nextId() + $a2;
		$total += $this->nextId() + $a3;
		if ($hasB0) {
			$total += $b0;
		}
		$total += $this->nextId() + $a4;
		$total += $this->nextId() + $a5;
		$total += $this->nextId() + $a6;
		$total += $this->nextId() + $a7;
		if ($hasB1) {
			$total += $b1;
		}
		$total += $this->nextId() + $a8;
		$total += $this->nextId() + $a9;
		$total += $this->nextId() + $a10;
		$total += $this->nextId() + $a11;
		if ($hasB2) {
			$total += $b2;
		}
		$total += $this->nextId() + $a12;
		$total += $this->nextId() + $a13;
		$total += $this->nextId() + $a14;
		$total += $this->nextId() + $a15;
		if ($hasB3) {
			$total += $b3;
		}
		$total += $this->nextId() + $a16;
		$total += $this->nextId() + $a17;
		$total += $this->nextId() + $a18;
		$total += $this->nextId() + $a19;
		if ($hasB4) {
			$total += $b4;
		}
		$total += $this->nextId() + $a20;
		$total += $this->nextId() + $a21;
		$total += $this->nextId() + $a22;
		$total += $this->nextId() + $a23;
		if ($hasB5) {
			$total += $b5;
		}
		$total += $this->nextId() + $a24;
		$total += $this->nextId() + $a25;
		$total += $this->nextId() + $a26;
		$total += $this->nextId() + $a27;
		if ($hasB6) {
			$total += $b6;
		}
		$total += $this->nextId() + $a28;
		$total += $this->nextId() + $a29;
		$total += $this->nextId() + $a30;
		$total += $this->nextId() + $a31;
		if ($hasB7) {
			$total += $b7;
		}
		$total += $this->nextId() + $a32;
		$total += $this->nextId() + $a33;
		$total += $this->nextId() + $a34;
		$total += $this->nextId() + $a35;
		if ($hasB8) {
			$total += $b8;
		}
		$total += $this->nextId() + $a36;
		$total += $this->nextId() + $a37;
		$total += $this->nextId() + $a38;
		$total += $this->nextId() + $a39;
		if ($hasB9) {
			$total += $b9;
		}
		$total += $this->nextId() + $a40;
		$total += $this->nextId() + $a41;
		$total += $this->nextId() + $a42;
		$total += $this->nextId() + $a43;
		if ($hasB10) {
			$total += $b10;
		}
		$total += $this->nextId() + $a44;
		$total += $this->nextId() + $a45;
		$total += $this->nextId() + $a46;
		$total += $this->nextId() + $a47;
		if ($hasB11) {
			$total += $b11;
		}
		$total += $this->nextId() + $a48;
		$total += $this->nextId() + $a49;
		$total += $this->nextId() + $a50;
		$total += $this->nextId() + $a51;
		if ($hasB12) {
			$total += $b12;
		}
		$total += $this->nextId() + $a52;
		$total += $this->nextId() + $a53;
		$total += $this->nextId() + $a54;
		$total += $this->nextId() + $a55;
		if ($hasB13) {
			$total += $b13;
		}
		$total += $this->nextId() + $a56;
		$total += $this->nextId() + $a57;
		$total += $this->nextId() + $a58;
		$total += $this->nextId() + $a59;
		if ($hasB14) {
			$total += $b14;
		}
		$total += $this->nextId() + $a0;
		$total += $this->nextId() + $a1;
		$total += $this->nextId() + $a2;
		$total += $this->nextId() + $a3;
		if ($hasB15) {
			$total += $b15;
		}
		$total += $this->nextId() + $a4;
		$total += $this->nextId() + $a5;
		$total += $this->nextId() + $a6;
		$total += $this->nextId() + $a7;
		if ($hasB16) {
			$total += $b16;
		}
		$total += $this->nextId() + $a8;
		$total += $this->nextId() + $a9;
		$total += $this->nextId() + $a10;
		$total += $this->nextId() + $a11;
		if ($hasB17) {
			$total += $b17;
		}
		$total += $this->nextId() + $a12;
		$total += $this->nextId() + $a13;
		$total += $this->nextId() + $a14;
		$total += $this->nextId() + $a15;
		if ($hasB18) {
			$total += $b18;
		}
		$total += $this->nextId() + $a16;
		$total += $this->nextId() + $a17;
		$total += $this->nextId() + $a18;
		$total += $this->nextId() + $a19;
		if ($hasB19) {
			$total += $b19;
		}
		$total += $this->nextId() + $a20;
		$total += $this->nextId() + $a21;
		$total += $this->nextId() + $a22;
		$total += $this->nextId() + $a23;
		if ($hasB20) {
			$total += $b20;
		}
		$total += $this->nextId() + $a24;
		$total += $this->nextId() + $a25;
		$total += $this->nextId() + $a26;
		$total += $this->nextId() + $a27;
		if ($hasB21) {
			$total += $b21;
		}
		$total += $this->nextId() + $a28;
		$total += $this->nextId() + $a29;
		$total += $this->nextId() + $a30;
		$total += $this->nextId() + $a31;
		if ($hasB22) {
			$total += $b22;
		}
		$total += $this->nextId() + $a32;
		$total += $this->nextId() + $a33;
		$total += $this->nextId() + $a34;
		$total += $this->nextId() + $a35;
		if ($hasB23) {
			$total += $b23;
		}
		$total += $this->nextId() + $a36;
		$total += $this->nextId() + $a37;
		$total += $this->nextId() + $a38;
		$total += $this->nextId() + $a39;
		if ($hasB24) {
			$total += $b24;
		}
		$total += $this->nextId() + $a40;
		$total += $this->nextId() + $a41;
		$total += $this->nextId() + $a42;
		$total += $this->nextId() + $a43;
		if ($hasB25) {
			$total += $b25;
		}
		$total += $this->nextId() + $a44;
		$total += $this->nextId() + $a45;
		$total += $this->nextId() + $a46;
		$total += $this->nextId() + $a47;
		if ($hasB26) {
			$total += $b26;
		}
		$total += $this->nextId() + $a48;
		$total += $this->nextId() + $a49;
		$total += $this->nextId() + $a50;
		$total += $this->nextId() + $a51;
		if ($hasB27) {
			$total += $b27;
		}
		$total += $this->nextId() + $a52;
		$total += $this->nextId() + $a53;
		$total += $this->nextId() + $a54;
		$total += $this->nextId() + $a55;
		if ($hasB28) {
			$total += $b28;
		}
		$total += $this->nextId() + $a56;
		$total += $this->nextId() + $a57;
		$total += $this->nextId() + $a58;
		$total += $this->nextId() + $a59;
		if ($hasB29) {
			$total += $b29;
		}
		$total += $this->nextId() + $a0;
		$total += $this->nextId() + $a1;
		$total += $this->nextId() + $a2;
		$total += $this->nextId() + $a3;
		if ($hasB30) {
			$total += $b30;
		}
		$total += $this->nextId() + $a4;
		$total += $this->nextId() + $a5;
		$total += $this->nextId() + $a6;
		$total += $this->nextId() + $a7;
		if ($hasB31) {
			$total += $b31;
		}
		$total += $this->nextId() + $a8;
		$total += $this->nextId() + $a9;
		$total += $this->nextId() + $a10;
		$total += $this->nextId() + $a11;
		if ($hasB32) {
			$total += $b32;
		}
		$total += $this->nextId() + $a12;
		$total += $this->nextId() + $a13;
		$total += $this->nextId() + $a14;
		$total += $this->nextId() + $a15;
		if ($hasB33) {
			$total += $b33;
		}
		$total += $this->nextId() + $a16;
		$total += $this->nextId() + $a17;
		$total += $this->nextId() + $a18;
		$total += $this->nextId() + $a19;
		if ($hasB34) {
			$total += $b34;
		}
		$total += $this->nextId() + $a20;
		$total += $this->nextId() + $a21;
		$total += $this->nextId() + $a22;
		$total += $this->nextId() + $a23;
		if ($hasB35) {
			$total += $b35;
		}
		$total += $this->nextId() + $a24;
		$total += $this->nextId() + $a25;
		$total += $this->nextId() + $a26;
		$total += $this->nextId() + $a27;
		if ($hasB36) {
			$total += $b36;
		}
		$total += $this->nextId() + $a28;
		$total += $this->nextId() + $a29;
		$total += $this->nextId() + $a30;
		$total += $this->nextId() + $a31;
		if ($hasB37) {
			$total += $b37;
		}
		$total += $this->nextId() + $a32;
		$total += $this->nextId() + $a33;
		$total += $this->nextId() + $a34;
		$total += $this->nextId() + $a35;
		if ($hasB38) {
			$total += $b38;
		}
		$total += $this->nextId() + $a36;
		$total += $this->nextId() + $a37;
		$total += $this->nextId() + $a38;
		$total += $this->nextId() + $a39;
		if ($hasB39) {
			$total += $b39;
		}
		$total += $this->nextId() + $a40;
		$total += $this->nextId() + $a41;
		$total += $this->nextId() + $a42;
		$total += $this->nextId() + $a43;
		if ($hasB40) {
			$total += $b40;
		}
		$total += $this->nextId() + $a44;
		$total += $this->nextId() + $a45;
		$total += $this->nextId() + $a46;
		$total += $this->nextId() + $a47;
		if ($hasB41) {
			$total += $b41;
		}
		$total += $this->nextId() + $a48;
		$total += $this->nextId() + $a49;
		$total += $this->nextId() + $a50;
		$total += $this->nextId() + $a51;
		if ($hasB42) {
			$total += $b42;
		}
		$total += $this->nextId() + $a52;
		$total += $this->nextId() + $a53;
		$total += $this->nextId() + $a54;
		$total += $this->nextId() + $a55;
		if ($hasB43) {
			$total += $b43;
		}
		$total += $this->nextId() + $a56;
		$total += $this->nextId() + $a57;
		$total += $this->nextId() + $a58;
		$total += $this->nextId() + $a59;
		if ($hasB44) {
			$total += $b44;
		}
		$total += $this->nextId() + $a0;
		$total += $this->nextId() + $a1;
		$total += $this->nextId() + $a2;
		$total += $this->nextId() + $a3;
		if ($hasB45) {
			$total += $b45;
		}
		$total += $this->nextId() + $a4;
		$total += $this->nextId() + $a5;
		$total += $this->nextId() + $a6;
		$total += $this->nextId() + $a7;
		if ($hasB46) {
			$total += $b46;
		}
		$total += $this->nextId() + $a8;
		$total += $this->nextId() + $a9;
		$total += $this->nextId() + $a10;
		$total += $this->nextId() + $a11;
		if ($hasB47) {
			$total += $b47;
		}
		$total += $this->nextId() + $a12;
		$total += $this->nextId() + $a13;
		$total += $this->nextId() + $a14;
		$total += $this->nextId() + $a15;
		if ($hasB48) {
			$total += $b48;
		}
		$total += $this->nextId() + $a16;
		$total += $this->nextId() + $a17;
		$total += $this->nextId() + $a18;
		$total += $this->nextId() + $a19;
		if ($hasB49) {
			$total += $b49;
		}
		$total += $this->nextId() + $a20;
		$total += $this->nextId() + $a21;
		$total += $this->nextId() + $a22;
		$total += $this->nextId() + $a23;
		if ($hasB50) {
			$total += $b50;
		}
		$total += $this->nextId() + $a24;
		$total += $this->nextId() + $a25;
		$total += $this->nextId() + $a26;
		$total += $this->nextId() + $a27;
		if ($hasB51) {
			$total += $b51;
		}
		$total += $this->nextId() + $a28;
		$total += $this->nextId() + $a29;
		$total += $this->nextId() + $a30;
		$total += $this->nextId() + $a31;
		if ($hasB52) {
			$total += $b52;
		}
		$total += $this->nextId() + $a32;
		$total += $this->nextId() + $a33;
		$total += $this->nextId() + $a34;
		$total += $this->nextId() + $a35;
		if ($hasB53) {
			$total += $b53;
		}
		$total += $this->nextId() + $a36;
		$total += $this->nextId() + $a37;
		$total += $this->nextId() + $a38;
		$total += $this->nextId() + $a39;
		if ($hasB54) {
			$total += $b54;
		}
		$total += $this->nextId() + $a40;
		$total += $this->nextId() + $a41;
		$total += $this->nextId() + $a42;
		$total += $this->nextId() + $a43;
		if ($hasB55) {
			$total += $b55;
		}
		$total += $this->nextId() + $a44;
		$total += $this->nextId() + $a45;
		$total += $this->nextId() + $a46;
		$total += $this->nextId() + $a47;
		if ($hasB56) {
			$total += $b56;
		}
		$total += $this->nextId() + $a48;
		$total += $this->nextId() + $a49;
		$total += $this->nextId() + $a50;
		$total += $this->nextId() + $a51;
		if ($hasB57) {
			$total += $b57;
		}
		$total += $this->nextId() + $a52;
		$total += $this->nextId() + $a53;
		$total += $this->nextId() + $a54;
		$total += $this->nextId() + $a55;
		if ($hasB58) {
			$total += $b58;
		}
		$total += $this->nextId() + $a56;
		$total += $this->nextId() + $a57;
		$total += $this->nextId() + $a58;
		$total += $this->nextId() + $a59;
		if ($hasB59) {
			$total += $b59;
		}
		$total += $this->nextId() + $a0;
		$total += $this->nextId() + $a1;
		$total += $this->nextId() + $a2;
		$total += $this->nextId() + $a3;
		if ($hasB0) {
			$total += $b0;
		}
		$total += $this->nextId() + $a4;
		$total += $this->nextId() + $a5;
		$total += $this->nextId() + $a6;
		$total += $this->nextId() + $a7;
		if ($hasB1) {
			$total += $b1;
		}
		$total += $this->nextId() + $a8;
		$total += $this->nextId() + $a9;
		$total += $this->nextId() + $a10;
		$total += $this->nextId() + $a11;
		if ($hasB2) {
			$total += $b2;
		}
		$total += $this->nextId() + $a12;
		$total += $this->nextId() + $a13;
		$total += $this->nextId() + $a14;
		$total += $this->nextId() + $a15;
		if ($hasB3) {
			$total += $b3;
		}
		$total += $this->nextId() + $a16;
		$total += $this->nextId() + $a17;
		$total += $this->nextId() + $a18;
		$total += $this->nextId() + $a19;
		if ($hasB4) {
			$total += $b4;
		}
		$total += $this->nextId() + $a20;
		$total += $this->nextId() + $a21;
		$total += $this->nextId() + $a22;
		$total += $this->nextId() + $a23;
		if ($hasB5) {
			$total += $b5;
		}
		$total += $this->nextId() + $a24;
		$total += $this->nextId() + $a25;
		$total += $this->nextId() + $a26;
		$total += $this->nextId() + $a27;
		if ($hasB6) {
			$total += $b6;
		}
		$total += $this->nextId() + $a28;
		$total += $this->nextId() + $a29;
		$total += $this->nextId() + $a30;
		$total += $this->nextId() + $a31;
		if ($hasB7) {
			$total += $b7;
		}
		$total += $this->nextId() + $a32;
		$total += $this->nextId() + $a33;
		$total += $this->nextId() + $a34;
		$total += $this->nextId() + $a35;
		if ($hasB8) {
			$total += $b8;
		}
		$total += $this->nextId() + $a36;
		$total += $this->nextId() + $a37;
		$total += $this->nextId() + $a38;
		$total += $this->nextId() + $a39;
		if ($hasB9) {
			$total += $b9;
		}
		$total += $this->nextId() + $a40;
		$total += $this->nextId() + $a41;
		$total += $this->nextId() + $a42;
		$total += $this->nextId() + $a43;
		if ($hasB10) {
			$total += $b10;
		}
		$total += $this->nextId() + $a44;
		$total += $this->nextId() + $a45;
		$total += $this->nextId() + $a46;
		$total += $this->nextId() + $a47;
		if ($hasB11) {
			$total += $b11;
		}
		$total += $this->nextId() + $a48;
		$total += $this->nextId() + $a49;
		$total += $this->nextId() + $a50;
		$total += $this->nextId() + $a51;
		if ($hasB12) {
			$total += $b12;
		}
		$total += $this->nextId() + $a52;
		$total += $this->nextId() + $a53;
		$total += $this->nextId() + $a54;
		$total += $this->nextId() + $a55;
		if ($hasB13) {
			$total += $b13;
		}
		$total += $this->nextId() + $a56;
		$total += $this->nextId() + $a57;
		$total += $this->nextId() + $a58;
		$total += $this->nextId() + $a59;
		if ($hasB14) {
			$total += $b14;
		}
		$total += $this->nextId() + $a0;
		$total += $this->nextId() + $a1;
		$total += $this->nextId() + $a2;
		$total += $this->nextId() + $a3;
		if ($hasB15) {
			$total += $b15;
		}
		$total += $this->nextId() + $a4;
		$total += $this->nextId() + $a5;
		$total += $this->nextId() + $a6;
		$total += $this->nextId() + $a7;
		if ($hasB16) {
			$total += $b16;
		}
		$total += $this->nextId() + $a8;
		$total += $this->nextId() + $a9;
		$total += $this->nextId() + $a10;
		$total += $this->nextId() + $a11;
		if ($hasB17) {
			$total += $b17;
		}
		$total += $this->nextId() + $a12;
		$total += $this->nextId() + $a13;
		$total += $this->nextId() + $a14;
		$total += $this->nextId() + $a15;
		if ($hasB18) {
			$total += $b18;
		}
		$total += $this->nextId() + $a16;
		$total += $this->nextId() + $a17;
		$total += $this->nextId() + $a18;
		$total += $this->nextId() + $a19;
		if ($hasB19) {
			$total += $b19;
		}
		$total += $this->nextId() + $a20;
		$total += $this->nextId() + $a21;
		$total += $this->nextId() + $a22;
		$total += $this->nextId() + $a23;
		if ($hasB20) {
			$total += $b20;
		}
		$total += $this->nextId() + $a24;
		$total += $this->nextId() + $a25;
		$total += $this->nextId() + $a26;
		$total += $this->nextId() + $a27;
		if ($hasB21) {
			$total += $b21;
		}
		$total += $this->nextId() + $a28;
		$total += $this->nextId() + $a29;
		$total += $this->nextId() + $a30;
		$total += $this->nextId() + $a31;
		if ($hasB22) {
			$total += $b22;
		}
		$total += $this->nextId() + $a32;
		$total += $this->nextId() + $a33;
		$total += $this->nextId() + $a34;
		$total += $this->nextId() + $a35;
		if ($hasB23) {
			$total += $b23;
		}
		$total += $this->nextId() + $a36;
		$total += $this->nextId() + $a37;
		$total += $this->nextId() + $a38;
		$total += $this->nextId() + $a39;
		if ($hasB24) {
			$total += $b24;
		}
		$total += $this->nextId() + $a40;
		$total += $this->nextId() + $a41;
		$total += $this->nextId() + $a42;
		$total += $this->nextId() + $a43;
		if ($hasB25) {
			$total += $b25;
		}
		$total += $this->nextId() + $a44;
		$total += $this->nextId() + $a45;
		$total += $this->nextId() + $a46;
		$total += $this->nextId() + $a47;
		if ($hasB26) {
			$total += $b26;
		}
		$total += $this->nextId() + $a48;
		$total += $this->nextId() + $a49;
		$total += $this->nextId() + $a50;
		$total += $this->nextId() + $a51;
		if ($hasB27) {
			$total += $b27;
		}
		$total += $this->nextId() + $a52;
		$total += $this->nextId() + $a53;
		$total += $this->nextId() + $a54;
		$total += $this->nextId() + $a55;
		if ($hasB28) {
			$total += $b28;
		}
		$total += $this->nextId() + $a56;
		$total += $this->nextId() + $a57;
		$total += $this->nextId() + $a58;
		$total += $this->nextId() + $a59;
		if ($hasB29) {
			$total += $b29;
		}
		$total += $this->nextId() + $a0;
		$total += $this->nextId() + $a1;
		$total += $this->nextId() + $a2;
		$total += $this->nextId() + $a3;
		if ($hasB30) {
			$total += $b30;
		}
		$total += $this->nextId() + $a4;
		$total += $this->nextId() + $a5;
		$total += $this->nextId() + $a6;
		$total += $this->nextId() + $a7;
		if ($hasB31) {
			$total += $b31;
		}
		$total += $this->nextId() + $a8;
		$total += $this->nextId() + $a9;
		$total += $this->nextId() + $a10;
		$total += $this->nextId() + $a11;
		if ($hasB32) {
			$total += $b32;
		}
		$total += $this->nextId() + $a12;
		$total += $this->nextId() + $a13;
		$total += $this->nextId() + $a14;
		$total += $this->nextId() + $a15;
		if ($hasB33) {
			$total += $b33;
		}
		$total += $this->nextId() + $a16;
		$total += $this->nextId() + $a17;
		$total += $this->nextId() + $a18;
		$total += $this->nextId() + $a19;
		if ($hasB34) {
			$total += $b34;
		}
		$total += $this->nextId() + $a20;
		$total += $this->nextId() + $a21;
		$total += $this->nextId() + $a22;
		$total += $this->nextId() + $a23;
		if ($hasB35) {
			$total += $b35;
		}
		$total += $this->nextId() + $a24;
		$total += $this->nextId() + $a25;
		$total += $this->nextId() + $a26;
		$total += $this->nextId() + $a27;
		if ($hasB36) {
			$total += $b36;
		}
		$total += $this->nextId() + $a28;
		$total += $this->nextId() + $a29;
		$total += $this->nextId() + $a30;
		$total += $this->nextId() + $a31;
		if ($hasB37) {
			$total += $b37;
		}
		$total += $this->nextId() + $a32;
		$total += $this->nextId() + $a33;
		$total += $this->nextId() + $a34;
		$total += $this->nextId() + $a35;
		if ($hasB38) {
			$total += $b38;
		}
		$total += $this->nextId() + $a36;
		$total += $this->nextId() + $a37;
		$total += $this->nextId() + $a38;
		$total += $this->nextId() + $a39;
		if ($hasB39) {
			$total += $b39;
		}
		$total += $this->nextId() + $a40;
		$total += $this->nextId() + $a41;
		$total += $this->nextId() + $a42;
		$total += $this->nextId() + $a43;
		if ($hasB40) {
			$total += $b40;
		}
		$total += $this->nextId() + $a44;
		$total += $this->nextId() + $a45;
		$total += $this->nextId() + $a46;
		$total += $this->nextId() + $a47;
		if ($hasB41) {
			$total += $b41;
		}
		$total += $this->nextId() + $a48;
		$total += $this->nextId() + $a49;
		$total += $this->nextId() + $a50;
		$total += $this->nextId() + $a51;
		if ($hasB42) {
			$total += $b42;
		}
		$total += $this->nextId() + $a52;
		$total += $this->nextId() + $a53;
		$total += $this->nextId() + $a54;
		$total += $this->nextId() + $a55;
		if ($hasB43) {
			$total += $b43;
		}
		$total += $this->nextId() + $a56;
		$total += $this->nextId() + $a57;
		$total += $this->nextId() + $a58;
		$total += $this->nextId() + $a59;
		if ($hasB44) {
			$total += $b44;
		}
		$total += $this->nextId() + $a0;
		$total += $this->nextId() + $a1;
		$total += $this->nextId() + $a2;
		$total += $this->nextId() + $a3;
		if ($hasB45) {
			$total += $b45;
		}
		$total += $this->nextId() + $a4;
		$total += $this->nextId() + $a5;
		$total += $this->nextId() + $a6;
		$total += $this->nextId() + $a7;
		if ($hasB46) {
			$total += $b46;
		}
		$total += $this->nextId() + $a8;
		$total += $this->nextId() + $a9;
		$total += $this->nextId() + $a10;
		$total += $this->nextId() + $a11;
		if ($hasB47) {
			$total += $b47;
		}
		$total += $this->nextId() + $a12;
		$total += $this->nextId() + $a13;
		$total += $this->nextId() + $a14;
		$total += $this->nextId() + $a15;
		if ($hasB48) {
			$total += $b48;
		}
		$total += $this->nextId() + $a16;
		$total += $this->nextId() + $a17;
		$total += $this->nextId() + $a18;
		$total += $this->nextId() + $a19;
		if ($hasB49) {
			$total += $b49;
		}
		$total += $this->nextId() + $a20;
		$total += $this->nextId() + $a21;
		$total += $this->nextId() + $a22;
		$total += $this->nextId() + $a23;
		if ($hasB50) {
			$total += $b50;
		}
		$total += $this->nextId() + $a24;
		$total += $this->nextId() + $a25;
		$total += $this->nextId() + $a26;
		$total += $this->nextId() + $a27;
		if ($hasB51) {
			$total += $b51;
		}
		$total += $this->nextId() + $a28;
		$total += $this->nextId() + $a29;
		$total += $this->nextId() + $a30;
		$total += $this->nextId() + $a31;
		if ($hasB52) {
			$total += $b52;
		}
		$total += $this->nextId() + $a32;
		$total += $this->nextId() + $a33;
		$total += $this->nextId() + $a34;
		$total += $this->nextId() + $a35;
		if ($hasB53) {
			$total += $b53;
		}
		$total += $this->nextId() + $a36;
		$total += $this->nextId() + $a37;
		$total += $this->nextId() + $a38;
		$total += $this->nextId() + $a39;
		if ($hasB54) {
			$total += $b54;
		}
		$total += $this->nextId() + $a40;
		$total += $this->nextId() + $a41;
		$total += $this->nextId() + $a42;
		$total += $this->nextId() + $a43;
		if ($hasB55) {
			$total += $b55;
		}
		$total += $this->nextId() + $a44;
		$total += $this->nextId() + $a45;
		$total += $this->nextId() + $a46;
		$total += $this->nextId() + $a47;
		if ($hasB56) {
			$total += $b56;
		}
		$total += $this->nextId() + $a48;
		$total += $this->nextId() + $a49;
		$total += $this->nextId() + $a50;
		$total += $this->nextId() + $a51;
		if ($hasB57) {
			$total += $b57;
		}
		$total += $this->nextId() + $a52;
		$total += $this->nextId() + $a53;
		$total += $this->nextId() + $a54;
		$total += $this->nextId() + $a55;
		if ($hasB58) {
			$total += $b58;
		}
		$total += $this->nextId() + $a56;
		$total += $this->nextId() + $a57;
		$total += $this->nextId() + $a58;
		$total += $this->nextId() + $a59;
		if ($hasB59) {
			$total += $b59;
		}
		$total += $this->nextId() + $a0;
		$total += $this->nextId() + $a1;
		$total += $this->nextId() + $a2;
		$total += $this->nextId() + $a3;
		if ($hasB0) {
			$total += $b0;
		}
		$total += $this->nextId() + $a4;
		$total += $this->nextId() + $a5;
		$total += $this->nextId() + $a6;
		$total += $this->nextId() + $a7;
		if ($hasB1) {
			$total += $b1;
		}
		$total += $this->nextId() + $a8;
		$total += $this->nextId() + $a9;
		$total += $this->nextId() + $a10;
		$total += $this->nextId() + $a11;
		if ($hasB2) {
			$total += $b2;
		}
		$total += $this->nextId() + $a12;
		$total += $this->nextId() + $a13;
		$total += $this->nextId() + $a14;
		$total += $this->nextId() + $a15;
		if ($hasB3) {
			$total += $b3;
		}
		$total += $this->nextId() + $a16;
		$total += $this->nextId() + $a17;
		$total += $this->nextId() + $a18;
		$total += $this->nextId() + $a19;
		if ($hasB4) {
			$total += $b4;
		}
		$total += $this->nextId() + $a20;
		$total += $this->nextId() + $a21;
		$total += $this->nextId() + $a22;
		$total += $this->nextId() + $a23;
		if ($hasB5) {
			$total += $b5;
		}
		$total += $this->nextId() + $a24;
		$total += $this->nextId() + $a25;
		$total += $this->nextId() + $a26;
		$total += $this->nextId() + $a27;
		if ($hasB6) {
			$total += $b6;
		}
		$total += $this->nextId() + $a28;
		$total += $this->nextId() + $a29;
		$total += $this->nextId() + $a30;
		$total += $this->nextId() + $a31;
		if ($hasB7) {
			$total += $b7;
		}
		$total += $this->nextId() + $a32;
		$total += $this->nextId() + $a33;
		$total += $this->nextId() + $a34;
		$total += $this->nextId() + $a35;
		if ($hasB8) {
			$total += $b8;
		}
		$total += $this->nextId() + $a36;
		$total += $this->nextId() + $a37;
		$total += $this->nextId() + $a38;
		$total += $this->nextId() + $a39;
		if ($hasB9) {
			$total += $b9;
		}
		$total += $this->nextId() + $a40;
		$total += $this->nextId() + $a41;
		$total += $this->nextId() + $a42;
		$total += $this->nextId() + $a43;
		if ($hasB10) {
			$total += $b10;
		}
		$total += $this->nextId() + $a44;
		$total += $this->nextId() + $a45;
		$total += $this->nextId() + $a46;
		$total += $this->nextId() + $a47;
		if ($hasB11) {
			$total += $b11;
		}
		$total += $this->nextId() + $a48;
		$total += $this->nextId() + $a49;
		$total += $this->nextId() + $a50;
		$total += $this->nextId() + $a51;
		if ($hasB12) {
			$total += $b12;
		}
		$total += $this->nextId() + $a52;
		$total += $this->nextId() + $a53;
		$total += $this->nextId() + $a54;
		$total += $this->nextId() + $a55;
		if ($hasB13) {
			$total += $b13;
		}
		$total += $this->nextId() + $a56;
		$total += $this->nextId() + $a57;
		$total += $this->nextId() + $a58;
		$total += $this->nextId() + $a59;
		if ($hasB14) {
			$total += $b14;
		}
		$total += $this->nextId() + $a0;
		$total += $this->nextId() + $a1;
		$total += $this->nextId() + $a2;
		$total += $this->nextId() + $a3;
		if ($hasB15) {
			$total += $b15;
		}
		$total += $this->nextId() + $a4;
		$total += $this->nextId() + $a5;
		$total += $this->nextId() + $a6;
		$total += $this->nextId() + $a7;
		if ($hasB16) {
			$total += $b16;
		}
		$total += $this->nextId() + $a8;
		$total += $this->nextId() + $a9;
		$total += $this->nextId() + $a10;
		$total += $this->nextId() + $a11;
		if ($hasB17) {
			$total += $b17;
		}
		$total += $this->nextId() + $a12;
		$total += $this->nextId() + $a13;
		$total += $this->nextId() + $a14;
		$total += $this->nextId() + $a15;
		if ($hasB18) {
			$total += $b18;
		}
		$total += $this->nextId() + $a16;
		$total += $this->nextId() + $a17;
		$total += $this->nextId() + $a18;
		$total += $this->nextId() + $a19;
		if ($hasB19) {
			$total += $b19;
		}
		$total += $this->nextId() + $a20;
		$total += $this->nextId() + $a21;
		$total += $this->nextId() + $a22;
		$total += $this->nextId() + $a23;
		if ($hasB20) {
			$total += $b20;
		}
		$total += $this->nextId() + $a24;
		$total += $this->nextId() + $a25;
		$total += $this->nextId() + $a26;
		$total += $this->nextId() + $a27;
		if ($hasB21) {
			$total += $b21;
		}
		$total += $this->nextId() + $a28;
		$total += $this->nextId() + $a29;
		$total += $this->nextId() + $a30;
		$total += $this->nextId() + $a31;
		if ($hasB22) {
			$total += $b22;
		}
		$total += $this->nextId() + $a32;
		$total += $this->nextId() + $a33;
		$total += $this->nextId() + $a34;
		$total += $this->nextId() + $a35;
		if ($hasB23) {
			$total += $b23;
		}
		$total += $this->nextId() + $a36;
		$total += $this->nextId() + $a37;
		$total += $this->nextId() + $a38;
		$total += $this->nextId() + $a39;
		if ($hasB24) {
			$total += $b24;
		}
		$total += $this->nextId() + $a40;
		$total += $this->nextId() + $a41;
		$total += $this->nextId() + $a42;
		$total += $this->nextId() + $a43;
		if ($hasB25) {
			$total += $b25;
		}
		$total += $this->nextId() + $a44;
		$total += $this->nextId() + $a45;
		$total += $this->nextId() + $a46;
		$total += $this->nextId() + $a47;
		if ($hasB26) {
			$total += $b26;
		}
		$total += $this->nextId() + $a48;
		$total += $this->nextId() + $a49;
		$total += $this->nextId() + $a50;
		$total += $this->nextId() + $a51;
		if ($hasB27) {
			$total += $b27;
		}
		$total += $this->nextId() + $a52;
		$total += $this->nextId() + $a53;
		$total += $this->nextId() + $a54;
		$total += $this->nextId() + $a55;
		if ($hasB28) {
			$total += $b28;
		}
		$total += $this->nextId() + $a56;
		$total += $this->nextId() + $a57;
		$total += $this->nextId() + $a58;
		$total += $this->nextId() + $a59;
		if ($hasB29) {
			$total += $b29;
		}
		$total += $this->nextId() + $a0;
		$total += $this->nextId() + $a1;
		$total += $this->nextId() + $a2;
		$total += $this->nextId() + $a3;
		if ($hasB30) {
			$total += $b30;
		}
		$total += $this->nextId() + $a4;
		$total += $this->nextId() + $a5;
		$total += $this->nextId() + $a6;
		$total += $this->nextId() + $a7;
		if ($hasB31) {
			$total += $b31;
		}
		$total += $this->nextId() + $a8;
		$total += $this->nextId() + $a9;
		$total += $this->nextId() + $a10;
		$total += $this->nextId() + $a11;
		if ($hasB32) {
			$total += $b32;
		}
		$total += $this->nextId() + $a12;
		$total += $this->nextId() + $a13;
		$total += $this->nextId() + $a14;
		$total += $this->nextId() + $a15;
		if ($hasB33) {
			$total += $b33;
		}
		$total += $this->nextId() + $a16;
		$total += $this->nextId() + $a17;
		$total += $this->nextId() + $a18;
		$total += $this->nextId() + $a19;
		if ($hasB34) {
			$total += $b34;
		}
		$total += $this->nextId() + $a20;
		$total += $this->nextId() + $a21;
		$total += $this->nextId() + $a22;
		$total += $this->nextId() + $a23;
		if ($hasB35) {
			$total += $b35;
		}
		$total += $this->nextId() + $a24;
		$total += $this->nextId() + $a25;
		$total += $this->nextId() + $a26;
		$total += $this->nextId() + $a27;
		if ($hasB36) {
			$total += $b36;
		}
		$total += $this->nextId() + $a28;
		$total += $this->nextId() + $a29;
		$total += $this->nextId() + $a30;
		$total += $this->nextId() + $a31;
		if ($hasB37) {
			$total += $b37;
		}
		$total += $this->nextId() + $a32;
		$total += $this->nextId() + $a33;
		$total += $this->nextId() + $a34;
		$total += $this->nextId() + $a35;
		if ($hasB38) {
			$total += $b38;
		}
		$total += $this->nextId() + $a36;
		$total += $this->nextId() + $a37;
		$total += $this->nextId() + $a38;
		$total += $this->nextId() + $a39;
		if ($hasB39) {
			$total += $b39;
		}
		$total += $this->nextId() + $a40;
		$total += $this->nextId() + $a41;
		$total += $this->nextId() + $a42;
		$total += $this->nextId() + $a43;
		if ($hasB40) {
			$total += $b40;
		}
		$total += $this->nextId() + $a44;
		$total += $this->nextId() + $a45;
		$total += $this->nextId() + $a46;
		$total += $this->nextId() + $a47;
		if ($hasB41) {
			$total += $b41;
		}
		$total += $this->nextId() + $a48;
		$total += $this->nextId() + $a49;
		$total += $this->nextId() + $a50;
		$total += $this->nextId() + $a51;
		if ($hasB42) {
			$total += $b42;
		}
		$total += $this->nextId() + $a52;
		$total += $this->nextId() + $a53;
		$total += $this->nextId() + $a54;
		$total += $this->nextId() + $a55;
		if ($hasB43) {
			$total += $b43;
		}
		$total += $this->nextId() + $a56;
		$total += $this->nextId() + $a57;
		$total += $this->nextId() + $a58;
		$total += $this->nextId() + $a59;
		if ($hasB44) {
			$total += $b44;
		}
		$total += $this->nextId() + $a0;
		$total += $this->nextId() + $a1;
		$total += $this->nextId() + $a2;
		$total += $this->nextId() + $a3;
		if ($hasB45) {
			$total += $b45;
		}
		$total += $this->nextId() + $a4;
		$total += $this->nextId() + $a5;
		$total += $this->nextId() + $a6;
		$total += $this->nextId() + $a7;
		if ($hasB46) {
			$total += $b46;
		}
		$total += $this->nextId() + $a8;
		$total += $this->nextId() + $a9;
		$total += $this->nextId() + $a10;
		$total += $this->nextId() + $a11;
		if ($hasB47) {
			$total += $b47;
		}
		$total += $this->nextId() + $a12;
		$total += $this->nextId() + $a13;
		$total += $this->nextId() + $a14;
		$total += $this->nextId() + $a15;
		if ($hasB48) {
			$total += $b48;
		}
		$total += $this->nextId() + $a16;
		$total += $this->nextId() + $a17;
		$total += $this->nextId() + $a18;
		$total += $this->nextId() + $a19;
		if ($hasB49) {
			$total += $b49;
		}
		$total += $this->nextId() + $a20;
		$total += $this->nextId() + $a21;
		$total += $this->nextId() + $a22;
		$total += $this->nextId() + $a23;
		if ($hasB50) {
			$total += $b50;
		}
		$total += $this->nextId() + $a24;
		$total += $this->nextId() + $a25;
		$total += $this->nextId() + $a26;
		$total += $this->nextId() + $a27;
		if ($hasB51) {
			$total += $b51;
		}
		$total += $this->nextId() + $a28;
		$total += $this->nextId() + $a29;
		$total += $this->nextId() + $a30;
		$total += $this->nextId() + $a31;
		if ($hasB52) {
			$total += $b52;
		}
		$total += $this->nextId() + $a32;
		$total += $this->nextId() + $a33;
		$total += $this->nextId() + $a34;
		$total += $this->nextId() + $a35;
		if ($hasB53) {
			$total += $b53;
		}
		$total += $this->nextId() + $a36;
		$total += $this->nextId() + $a37;
		$total += $this->nextId() + $a38;
		$total += $this->nextId() + $a39;
		if ($hasB54) {
			$total += $b54;
		}
		$total += $this->nextId() + $a40;
		$total += $this->nextId() + $a41;
		$total += $this->nextId() + $a42;
		$total += $this->nextId() + $a43;
		if ($hasB55) {
			$total += $b55;
		}
		$total += $this->nextId() + $a44;
		$total += $this->nextId() + $a45;
		$total += $this->nextId() + $a46;
		$total += $this->nextId() + $a47;
		if ($hasB56) {
			$total += $b56;
		}
		$total += $this->nextId() + $a48;
		$total += $this->nextId() + $a49;
		$total += $this->nextId() + $a50;
		$total += $this->nextId() + $a51;
		if ($hasB57) {
			$total += $b57;
		}
		$total += $this->nextId() + $a52;
		$total += $this->nextId() + $a53;
		$total += $this->nextId() + $a54;
		$total += $this->nextId() + $a55;
		if ($hasB58) {
			$total += $b58;
		}
		$total += $this->nextId() + $a56;
		$total += $this->nextId() + $a57;
		$total += $this->nextId() + $a58;
		$total += $this->nextId() + $a59;
		if ($hasB59) {
			$total += $b59;
		}
		$total += $this->nextId() + $a0;
		$total += $this->nextId() + $a1;
		$total += $this->nextId() + $a2;
		$total += $this->nextId() + $a3;
		if ($hasB0) {
			$total += $b0;
		}
		$total += $this->nextId() + $a4;
		$total += $this->nextId() + $a5;
		$total += $this->nextId() + $a6;
		$total += $this->nextId() + $a7;
		if ($hasB1) {
			$total += $b1;
		}
		$total += $this->nextId() + $a8;
		$total += $this->nextId() + $a9;
		$total += $this->nextId() + $a10;
		$total += $this->nextId() + $a11;
		if ($hasB2) {
			$total += $b2;
		}
		$total += $this->nextId() + $a12;
		$total += $this->nextId() + $a13;
		$total += $this->nextId() + $a14;
		$total += $this->nextId() + $a15;
		if ($hasB3) {
			$total += $b3;
		}
		$total += $this->nextId() + $a16;
		$total += $this->nextId() + $a17;
		$total += $this->nextId() + $a18;
		$total += $this->nextId() + $a19;
		if ($hasB4) {
			$total += $b4;
		}
		$total += $this->nextId() + $a20;
		$total += $this->nextId() + $a21;
		$total += $this->nextId() + $a22;
		$total += $this->nextId() + $a23;
		if ($hasB5) {
			$total += $b5;
		}
		$total += $this->nextId() + $a24;
		$total += $this->nextId() + $a25;
		$total += $this->nextId() + $a26;
		$total += $this->nextId() + $a27;
		if ($hasB6) {
			$total += $b6;
		}
		$total += $this->nextId() + $a28;
		$total += $this->nextId() + $a29;
		$total += $this->nextId() + $a30;
		$total += $this->nextId() + $a31;
		if ($hasB7) {
			$total += $b7;
		}
		$total += $this->nextId() + $a32;
		$total += $this->nextId() + $a33;
		$total += $this->nextId() + $a34;
		$total += $this->nextId() + $a35;
		if ($hasB8) {
			$total += $b8;
		}
		$total += $this->nextId() + $a36;
		$total += $this->nextId() + $a37;
		$total += $this->nextId() + $a38;
		$total += $this->nextId() + $a39;
		if ($hasB9) {
			$total += $b9;
		}

		return $total;
	}

}
