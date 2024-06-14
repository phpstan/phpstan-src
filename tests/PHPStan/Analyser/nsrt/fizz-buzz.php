<?php

namespace FizzBuzz;

use function PHPStan\Testing\assertType;

final class Fizz{}
final class Buzz{}
final class FizzBuzz{}
abstract class Num{
	/** @return numeric-string */
	public function __toString() { return filter_var(static::class, FizzBuzz\FILTER_SANITIZE_NUMBER_INT); } // @phpstan-ignore-line
}

/**
 * @template N of Num
 * @template FIZZ of n3|n6|n9|n12|n18|n21|n24|n27|n33|n36|n39|n42|n48|n51|n54|n57|n63|n66|n69|n72|n78|n81|n84|n87|n93|n96|n99
 * @template BUZZ of n5|n10|n20|n25|n35|n40|n50|n55|n65|n70|n80|n85|n95|n100
 * @template FIZZBUZZ of n15|n30|n45|n60|n75|n90
 * @param N $n
 * @return ($n is FIZZBUZZ ? FizzBuzz : ($n is BUZZ ? Buzz : ($n is FIZZ ? Fizz : N)))
 */
function fizzbuzz(Num $n) // @phpstan-ignore-line
{
	return match($n % 3) {
		0 => match($n % 5) {
			0 => new FizzBuzz,
			default => new Fizz,
		},
		default => match($n % 5) {
			0 => new Buzz,
			default => $n,
		},
	};
}

assertType('array{FizzBuzz\n1, FizzBuzz\n2, FizzBuzz\Fizz, FizzBuzz\n4, FizzBuzz\Buzz, FizzBuzz\Fizz, FizzBuzz\n7, FizzBuzz\n8, FizzBuzz\Fizz, FizzBuzz\Buzz, FizzBuzz\n11, FizzBuzz\Fizz, FizzBuzz\n13, FizzBuzz\n14, FizzBuzz\FizzBuzz, FizzBuzz\n16, FizzBuzz\n17, FizzBuzz\Fizz, FizzBuzz\n19, FizzBuzz\Buzz, FizzBuzz\Fizz, FizzBuzz\n22, FizzBuzz\n23, FizzBuzz\Fizz, FizzBuzz\Buzz, FizzBuzz\n26, FizzBuzz\Fizz, FizzBuzz\n28, FizzBuzz\n29, FizzBuzz\FizzBuzz, FizzBuzz\n31, FizzBuzz\n32, FizzBuzz\Fizz, FizzBuzz\n34, FizzBuzz\Buzz, FizzBuzz\Fizz, FizzBuzz\n37, FizzBuzz\n38, FizzBuzz\Fizz, FizzBuzz\Buzz, FizzBuzz\n41, FizzBuzz\Fizz, FizzBuzz\n43, FizzBuzz\n44, FizzBuzz\FizzBuzz, FizzBuzz\n46, FizzBuzz\n47, FizzBuzz\Fizz, FizzBuzz\n49, FizzBuzz\Buzz, FizzBuzz\Fizz, FizzBuzz\n52, FizzBuzz\n53, FizzBuzz\Fizz, FizzBuzz\Buzz, FizzBuzz\n56, FizzBuzz\Fizz, FizzBuzz\n58, FizzBuzz\n59, FizzBuzz\FizzBuzz, FizzBuzz\n61, FizzBuzz\n62, FizzBuzz\Fizz, FizzBuzz\n64, FizzBuzz\Buzz, FizzBuzz\Fizz, FizzBuzz\n67, FizzBuzz\n68, FizzBuzz\Fizz, FizzBuzz\Buzz, FizzBuzz\n71, FizzBuzz\Fizz, FizzBuzz\n73, FizzBuzz\n74, FizzBuzz\FizzBuzz, FizzBuzz\n76, FizzBuzz\n77, FizzBuzz\Fizz, FizzBuzz\n79, FizzBuzz\Buzz, FizzBuzz\Fizz, FizzBuzz\n82, FizzBuzz\n83, FizzBuzz\Fizz, FizzBuzz\Buzz, FizzBuzz\n86, FizzBuzz\Fizz, FizzBuzz\n88, FizzBuzz\n89, FizzBuzz\FizzBuzz, FizzBuzz\n91, FizzBuzz\n92, FizzBuzz\Fizz, FizzBuzz\n94, FizzBuzz\Buzz, FizzBuzz\Fizz, FizzBuzz\n97, FizzBuzz\n98, FizzBuzz\Fizz, FizzBuzz\Buzz}', [
	fizzbuzz(new n1),
	fizzbuzz(new n2),
	fizzbuzz(new n3),
	fizzbuzz(new n4),
	fizzbuzz(new n5),
	fizzbuzz(new n6),
	fizzbuzz(new n7),
	fizzbuzz(new n8),
	fizzbuzz(new n9),
	fizzbuzz(new n10),
	fizzbuzz(new n11),
	fizzbuzz(new n12),
	fizzbuzz(new n13),
	fizzbuzz(new n14),
	fizzbuzz(new n15),
	fizzbuzz(new n16),
	fizzbuzz(new n17),
	fizzbuzz(new n18),
	fizzbuzz(new n19),
	fizzbuzz(new n20),
	fizzbuzz(new n21),
	fizzbuzz(new n22),
	fizzbuzz(new n23),
	fizzbuzz(new n24),
	fizzbuzz(new n25),
	fizzbuzz(new n26),
	fizzbuzz(new n27),
	fizzbuzz(new n28),
	fizzbuzz(new n29),
	fizzbuzz(new n30),
	fizzbuzz(new n31),
	fizzbuzz(new n32),
	fizzbuzz(new n33),
	fizzbuzz(new n34),
	fizzbuzz(new n35),
	fizzbuzz(new n36),
	fizzbuzz(new n37),
	fizzbuzz(new n38),
	fizzbuzz(new n39),
	fizzbuzz(new n40),
	fizzbuzz(new n41),
	fizzbuzz(new n42),
	fizzbuzz(new n43),
	fizzbuzz(new n44),
	fizzbuzz(new n45),
	fizzbuzz(new n46),
	fizzbuzz(new n47),
	fizzbuzz(new n48),
	fizzbuzz(new n49),
	fizzbuzz(new n50),
	fizzbuzz(new n51),
	fizzbuzz(new n52),
	fizzbuzz(new n53),
	fizzbuzz(new n54),
	fizzbuzz(new n55),
	fizzbuzz(new n56),
	fizzbuzz(new n57),
	fizzbuzz(new n58),
	fizzbuzz(new n59),
	fizzbuzz(new n60),
	fizzbuzz(new n61),
	fizzbuzz(new n62),
	fizzbuzz(new n63),
	fizzbuzz(new n64),
	fizzbuzz(new n65),
	fizzbuzz(new n66),
	fizzbuzz(new n67),
	fizzbuzz(new n68),
	fizzbuzz(new n69),
	fizzbuzz(new n70),
	fizzbuzz(new n71),
	fizzbuzz(new n72),
	fizzbuzz(new n73),
	fizzbuzz(new n74),
	fizzbuzz(new n75),
	fizzbuzz(new n76),
	fizzbuzz(new n77),
	fizzbuzz(new n78),
	fizzbuzz(new n79),
	fizzbuzz(new n80),
	fizzbuzz(new n81),
	fizzbuzz(new n82),
	fizzbuzz(new n83),
	fizzbuzz(new n84),
	fizzbuzz(new n85),
	fizzbuzz(new n86),
	fizzbuzz(new n87),
	fizzbuzz(new n88),
	fizzbuzz(new n89),
	fizzbuzz(new n90),
	fizzbuzz(new n91),
	fizzbuzz(new n92),
	fizzbuzz(new n93),
	fizzbuzz(new n94),
	fizzbuzz(new n95),
	fizzbuzz(new n96),
	fizzbuzz(new n97),
	fizzbuzz(new n98),
	fizzbuzz(new n99),
	fizzbuzz(new n100)
]);

final class n1 extends Num {}
final class n2 extends Num {}
final class n3 extends Num {}
final class n4 extends Num {}
final class n5 extends Num {}
final class n6 extends Num {}
final class n7 extends Num {}
final class n8 extends Num {}
final class n9 extends Num {}
final class n10 extends Num {}
final class n11 extends Num {}
final class n12 extends Num {}
final class n13 extends Num {}
final class n14 extends Num {}
final class n15 extends Num {}
final class n16 extends Num {}
final class n17 extends Num {}
final class n18 extends Num {}
final class n19 extends Num {}
final class n20 extends Num {}
final class n21 extends Num {}
final class n22 extends Num {}
final class n23 extends Num {}
final class n24 extends Num {}
final class n25 extends Num {}
final class n26 extends Num {}
final class n27 extends Num {}
final class n28 extends Num {}
final class n29 extends Num {}
final class n30 extends Num {}
final class n31 extends Num {}
final class n32 extends Num {}
final class n33 extends Num {}
final class n34 extends Num {}
final class n35 extends Num {}
final class n36 extends Num {}
final class n37 extends Num {}
final class n38 extends Num {}
final class n39 extends Num {}
final class n40 extends Num {}
final class n41 extends Num {}
final class n42 extends Num {}
final class n43 extends Num {}
final class n44 extends Num {}
final class n45 extends Num {}
final class n46 extends Num {}
final class n47 extends Num {}
final class n48 extends Num {}
final class n49 extends Num {}
final class n50 extends Num {}
final class n51 extends Num {}
final class n52 extends Num {}
final class n53 extends Num {}
final class n54 extends Num {}
final class n55 extends Num {}
final class n56 extends Num {}
final class n57 extends Num {}
final class n58 extends Num {}
final class n59 extends Num {}
final class n60 extends Num {}
final class n61 extends Num {}
final class n62 extends Num {}
final class n63 extends Num {}
final class n64 extends Num {}
final class n65 extends Num {}
final class n66 extends Num {}
final class n67 extends Num {}
final class n68 extends Num {}
final class n69 extends Num {}
final class n70 extends Num {}
final class n71 extends Num {}
final class n72 extends Num {}
final class n73 extends Num {}
final class n74 extends Num {}
final class n75 extends Num {}
final class n76 extends Num {}
final class n77 extends Num {}
final class n78 extends Num {}
final class n79 extends Num {}
final class n80 extends Num {}
final class n81 extends Num {}
final class n82 extends Num {}
final class n83 extends Num {}
final class n84 extends Num {}
final class n85 extends Num {}
final class n86 extends Num {}
final class n87 extends Num {}
final class n88 extends Num {}
final class n89 extends Num {}
final class n90 extends Num {}
final class n91 extends Num {}
final class n92 extends Num {}
final class n93 extends Num {}
final class n94 extends Num {}
final class n95 extends Num {}
final class n96 extends Num {}
final class n97 extends Num {}
final class n98 extends Num {}
final class n99 extends Num {}
final class n100 extends Num {}
