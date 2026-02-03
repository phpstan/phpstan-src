<?php

namespace ListCount;

use function PHPStan\Testing\assertType;

/**
 * @param list<int> $items
 */
function foo(array $items) {
	assertType('list<int>', $items);
	if (count($items) === 3) {
		assertType('array{int, int, int}', $items);
		array_shift($items);
		assertType('array{int, int}', $items);
	} elseif (count($items) === 0) {
		assertType('array{}', $items);
	} elseif (count($items) === 5) {
		assertType('array{int, int, int, int, int}', $items);
	} else {
		assertType('non-empty-list<int>', $items);
	}
	assertType('list<int>', $items);
}

/**
 * @param list<int> $items
 */
function modeCount(array $items, int $mode) {
	assertType('list<int>', $items);
	if (count($items, $mode) === 3) {
		assertType('array{int, int, int}', $items);
		array_shift($items);
		assertType('array{int, int}', $items);
	} elseif (count($items, $mode) === 0) {
		assertType('array{}', $items);
	} elseif (count($items, $mode) === 5) {
		assertType('array{int, int, int, int, int}', $items);
	} else {
		assertType('non-empty-list<int>', $items);
	}
	assertType('list<int>', $items);
}

/**
 * @param list<int|int[]> $items
 */
function modeCountOnMaybeArray(array $items, int $mode) {
	assertType('list<array<int>|int>', $items);
	if (count($items, $mode) === 3) {
		assertType('non-empty-list<array<int>|int>', $items);
		array_shift($items);
		assertType('list<array<int>|int>', $items);
	} elseif (count($items, $mode) === 0) {
		assertType('array{}', $items);
	} elseif (count($items, $mode) === 5) {
		assertType('non-empty-list<array<int>|int>', $items);
	} else {
		assertType('non-empty-list<array<int>|int>', $items);
	}
	assertType('list<array<int>|int>', $items);
}


/**
 * @param list<int> $items
 */
function normalCount(array $items) {
	assertType('list<int>', $items);
	if (count($items, COUNT_NORMAL) === 3) {
		assertType('array{int, int, int}', $items);
		array_shift($items);
		assertType('array{int, int}', $items);
	} elseif (count($items, COUNT_NORMAL) === 0) {
		assertType('array{}', $items);
	} elseif (count($items, COUNT_NORMAL) === 5) {
		assertType('array{int, int, int, int, int}', $items);
	} else {
		assertType('non-empty-list<int>', $items);
	}
	assertType('list<int>', $items);
}

/**
 * @param list<int|int[]> $items
 */
function recursiveCountOnMaybeArray(array $items):void {
	assertType('list<array<int>|int>', $items);
	if (count($items, COUNT_RECURSIVE) === 3) {
		assertType('non-empty-list<array<int>|int>', $items);
		array_shift($items);
		assertType('list<array<int>|int>', $items);
	} elseif (count($items, COUNT_RECURSIVE) === 0) {
		assertType('array{}', $items);
	} elseif (count($items, COUNT_RECURSIVE) === 5) {
		assertType('non-empty-list<array<int>|int>', $items);
	} else {
		assertType('non-empty-list<array<int>|int>', $items);
	}
	assertType('list<array<int>|int>', $items);
}

/**
 * @param list<int|int[]> $items
 */
function normalCountOnMaybeArray(array $items):void {
	assertType('list<array<int>|int>', $items);
	if (count($items, COUNT_NORMAL) === 3) {
		assertType('array{array<int>|int, array<int>|int, array<int>|int}', $items);
		array_shift($items);
		assertType('array{array<int>|int, array<int>|int}', $items);
	} elseif (count($items, COUNT_NORMAL) === 0) {
		assertType('array{}', $items);
	} elseif (count($items, COUNT_NORMAL) === 5) {
		assertType('array{array<int>|int, array<int>|int, array<int>|int, array<int>|int, array<int>|int}', $items);
	} else {
		assertType('non-empty-list<array<int>|int>', $items);
	}
	assertType('list<array<int>|int>', $items);
}

class A {}

/**
 * @param list<A> $items
 */
function cannotCountRecursive($items, int $mode)
{
	if (count($items) === 3) {
		assertType('array{ListCount\A, ListCount\A, ListCount\A}', $items);
	}
	if (count($items, COUNT_NORMAL) === 3) {
		assertType('array{ListCount\A, ListCount\A, ListCount\A}', $items);
	}
	if (count($items, COUNT_RECURSIVE) === 3) {
		assertType('array{ListCount\A, ListCount\A, ListCount\A}', $items);
	}
	if (count($items, $mode) === 3) {
		assertType('array{ListCount\A, ListCount\A, ListCount\A}', $items);
	}
}

/**
 * @param list<array<A>> $items
 */
function cannotCountRecursiveNestedArray($items, int $mode)
{
	if (count($items) === 3) {
		assertType('array{array<ListCount\A>, array<ListCount\A>, array<ListCount\A>}', $items);
	}
	if (count($items, COUNT_NORMAL) === 3) {
		assertType('array{array<ListCount\A>, array<ListCount\A>, array<ListCount\A>}', $items);
	}
	if (count($items, COUNT_RECURSIVE) === 3) {
		assertType('non-empty-list<array<ListCount\A>>', $items);
	}
	if (count($items, $mode) === 3) {
		assertType('non-empty-list<array<ListCount\A>>', $items);
	}
}

class CountableFoo implements \Countable
{
	public function count(): int
	{
		return 3;
	}
}

/**
 * @param list<CountableFoo> $items
 */
function cannotCountRecursiveCountable($items, int $mode)
{
	if (count($items) === 3) {
		assertType('array{ListCount\CountableFoo, ListCount\CountableFoo, ListCount\CountableFoo}', $items);
	}
	if (count($items, COUNT_NORMAL) === 3) {
		assertType('array{ListCount\CountableFoo, ListCount\CountableFoo, ListCount\CountableFoo}', $items);
	}
	if (count($items, COUNT_RECURSIVE) === 3) {
		assertType('array{ListCount\CountableFoo, ListCount\CountableFoo, ListCount\CountableFoo}', $items);
	}
	if (count($items, $mode) === 3) {
		assertType('array{ListCount\CountableFoo, ListCount\CountableFoo, ListCount\CountableFoo}', $items);
	}
}

function countCountable(CountableFoo $x, int $mode)
{
	if (count($x) === 3) {
		assertType('ListCount\CountableFoo', $x);
	} else {
		assertType('ListCount\CountableFoo', $x);
	}
	assertType('ListCount\CountableFoo', $x);

	if (count($x, COUNT_NORMAL) === 3) {
		assertType('ListCount\CountableFoo', $x);
	} else {
		assertType('ListCount\CountableFoo', $x);
	}
	assertType('ListCount\CountableFoo', $x);

	if (count($x, COUNT_RECURSIVE) === 3) {
		assertType('ListCount\CountableFoo', $x);
	} else {
		assertType('ListCount\CountableFoo', $x);
	}
	assertType('ListCount\CountableFoo', $x);

	if (count($x, $mode) === 3) {
		assertType('ListCount\CountableFoo', $x);
	} else {
		assertType('ListCount\CountableFoo', $x);
	}
	assertType('ListCount\CountableFoo', $x);
}

class CountWithOptionalKeys
{
	/**
	 * @param array{0: mixed, 1?: string|null} $row
	 */
	protected function testOptionalKeys($row): void
	{
		if (count($row) === 0) {
			assertType('*NEVER*', $row);
		} else {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}

		if (count($row) === 1) {
			assertType('array{mixed}', $row);
		} else {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}

		if (count($row) === 2) {
			assertType('array{mixed, string|null}', $row);
		} else {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}

		if (count($row) === 3) {
			assertType('*NEVER*', $row);
		} else {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}
	}

	/**
	 * @param array{mixed}|array{0: mixed, 1?: string|null} $row
	 */
	protected function testOptionalKeysInUnion($row): void
	{
		if (count($row) === 0) {
			assertType('*NEVER*', $row);
		} else {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}

		if (count($row) === 1) {
			assertType('array{mixed}', $row);
		} else {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}

		if (count($row) === 2) {
			assertType('array{mixed, string|null}', $row);
		} else {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}

		if (count($row) === 3) {
			assertType('*NEVER*', $row);
		} else {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}
	}

	/**
	 * @param array{string}|array{0: int, 1?: string|null} $row
	 */
	protected function testOptionalKeysInListsOfTaggedUnion($row): void
	{
		if (count($row) === 0) {
			assertType('*NEVER*', $row);
		} else {
			assertType('array{0: int, 1?: string|null}|array{string}', $row);
		}

		if (count($row) === 1) {
			assertType('array{0: int, 1?: string|null}|array{string}', $row);
		} else {
			assertType('array{0: int, 1?: string|null}', $row);
		}

		if (count($row) === 2) {
			assertType('array{int, string|null}', $row);
		} else {
			assertType('array{0: int, 1?: string|null}|array{string}', $row);
		}

		if (count($row) === 3) {
			assertType('*NEVER*', $row);
		} else {
			assertType('array{0: int, 1?: string|null}|array{string}', $row);
		}
	}

	/**
	 * @param array{string}|array{0: int, 3?: string|null} $row
	 */
	protected function testOptionalKeysInUnionArray($row): void
	{
		if (count($row) === 0) {
			assertType('*NEVER*', $row);
		} else {
			assertType('array{0: int, 3?: string|null}|array{string}', $row);
		}

		if (count($row) === 1) {
			assertType('array{0: int, 3?: string|null}|array{string}', $row);
		} else {
			assertType('array{0: int, 3?: string|null}', $row);
		}

		if (count($row) === 2) {
			assertType('array{0: int, 3?: string|null}', $row);
		} else {
			assertType('array{0: int, 3?: string|null}|array{string}', $row);
		}

		if (count($row) === 3) {
			assertType('*NEVER*', $row);
		} else {
			assertType('array{0: int, 3?: string|null}|array{string}', $row);
		}
	}

	/**
	 * @param array{string}|list{0: int, 1?: string|null, 2?: int|null, 3?: float|null} $row
	 * @param list<string> $listRow
	 * @param int<2, 3> $twoOrThree
	 * @param int<2, max> $twoOrMore
	 * @param int<min, 3> $maxThree
	 * @param int<10, 11> $tenOrEleven
	 * @param int<3, 32> $threeOrMoreInRangeLimit
	 * @param int<3, 512> $threeOrMoreOverRangeLimit
	 */
	protected function testOptionalKeysInUnionListWithIntRange($row, $listRow, $twoOrThree, $twoOrMore, int $maxThree, $tenOrEleven, $threeOrMoreInRangeLimit, $threeOrMoreOverRangeLimit): void
	{
		if (count($row) >= $twoOrThree) {
			assertType('array{0: int, 1: string|null, 2?: int|null}', $row);
		} else {
			assertType('array{string}|list{0: int, 1?: string|null, 2?: int|null, 3?: float|null}', $row);
		}

		if (count($row) >= $tenOrEleven) {
			assertType('*NEVER*', $row);
		} else {
			assertType('array{string}|list{0: int, 1?: string|null, 2?: int|null, 3?: float|null}', $row);
		}

		if (count($row) >= $twoOrMore) {
			assertType('list{0: int, 1: string|null, 2?: int|null, 3?: float|null}', $row);
		} else {
			assertType('array{string}|list{0: int, 1?: string|null, 2?: int|null, 3?: float|null}', $row);
		}

		if (count($row) >= $maxThree) {
			assertType('array{string}|list{0: int, 1?: string|null, 2?: int|null, 3?: float|null}', $row);
		} else {
			assertType('array{string}|list{0: int, 1?: string|null, 2?: int|null, 3?: float|null}', $row);
		}

		if (count($row) >= $threeOrMoreInRangeLimit) {
			assertType('list{0: int, 1?: string|null, 2?: int|null, 3?: float|null}', $row);
		} else {
			assertType('array{string}|list{0: int, 1?: string|null, 2?: int|null, 3?: float|null}', $row);
		}

		if (count($listRow) >= $threeOrMoreInRangeLimit) {
			assertType('list{0: string, 1: string, 2: string, 3?: string, 4?: string, 5?: string, 6?: string, 7?: string, 8?: string, 9?: string, 10?: string, 11?: string, 12?: string, 13?: string, 14?: string, 15?: string, 16?: string, 17?: string, 18?: string, 19?: string, 20?: string, 21?: string, 22?: string, 23?: string, 24?: string, 25?: string, 26?: string, 27?: string, 28?: string, 29?: string, 30?: string, 31?: string}', $listRow);
		} else {
			assertType('list<string>', $listRow);
		}

		if (count($row) >= $threeOrMoreOverRangeLimit) {
			assertType('list{0: int, 1?: string|null, 2?: int|null, 3?: float|null}', $row);
		} else {
			assertType('array{string}|list{0: int, 1?: string|null, 2?: int|null, 3?: float|null}', $row);
		}

		if (count($listRow) >= $threeOrMoreOverRangeLimit) {
			assertType('list{0: string, 1: string, 2: string, 3?: string, 4?: string, 5?: string, 6?: string, 7?: string, 8?: string, 9?: string, 10?: string, 11?: string, 12?: string, 13?: string, 14?: string, 15?: string, 16?: string, 17?: string, 18?: string, 19?: string, 20?: string, 21?: string, 22?: string, 23?: string, 24?: string, 25?: string, 26?: string, 27?: string, 28?: string, 29?: string, 30?: string, 31?: string, 32?: string, 33?: string, 34?: string, 35?: string, 36?: string, 37?: string, 38?: string, 39?: string, 40?: string, 41?: string, 42?: string, 43?: string, 44?: string, 45?: string, 46?: string, 47?: string, 48?: string, 49?: string, 50?: string, 51?: string, 52?: string, 53?: string, 54?: string, 55?: string, 56?: string, 57?: string, 58?: string, 59?: string, 60?: string, 61?: string, 62?: string, 63?: string, 64?: string, 65?: string, 66?: string, 67?: string, 68?: string, 69?: string, 70?: string, 71?: string, 72?: string, 73?: string, 74?: string, 75?: string, 76?: string, 77?: string, 78?: string, 79?: string, 80?: string, 81?: string, 82?: string, 83?: string, 84?: string, 85?: string, 86?: string, 87?: string, 88?: string, 89?: string, 90?: string, 91?: string, 92?: string, 93?: string, 94?: string, 95?: string, 96?: string, 97?: string, 98?: string, 99?: string, 100?: string, 101?: string, 102?: string, 103?: string, 104?: string, 105?: string, 106?: string, 107?: string, 108?: string, 109?: string, 110?: string, 111?: string, 112?: string, 113?: string, 114?: string, 115?: string, 116?: string, 117?: string, 118?: string, 119?: string, 120?: string, 121?: string, 122?: string, 123?: string, 124?: string, 125?: string, 126?: string, 127?: string, 128?: string, 129?: string, 130?: string, 131?: string, 132?: string, 133?: string, 134?: string, 135?: string, 136?: string, 137?: string, 138?: string, 139?: string, 140?: string, 141?: string, 142?: string, 143?: string, 144?: string, 145?: string, 146?: string, 147?: string, 148?: string, 149?: string, 150?: string, 151?: string, 152?: string, 153?: string, 154?: string, 155?: string, 156?: string, 157?: string, 158?: string, 159?: string, 160?: string, 161?: string, 162?: string, 163?: string, 164?: string, 165?: string, 166?: string, 167?: string, 168?: string, 169?: string, 170?: string, 171?: string, 172?: string, 173?: string, 174?: string, 175?: string, 176?: string, 177?: string, 178?: string, 179?: string, 180?: string, 181?: string, 182?: string, 183?: string, 184?: string, 185?: string, 186?: string, 187?: string, 188?: string, 189?: string, 190?: string, 191?: string, 192?: string, 193?: string, 194?: string, 195?: string, 196?: string, 197?: string, 198?: string, 199?: string, 200?: string, 201?: string, 202?: string, 203?: string, 204?: string, 205?: string, 206?: string, 207?: string, 208?: string, 209?: string, 210?: string, 211?: string, 212?: string, 213?: string, 214?: string, 215?: string, 216?: string, 217?: string, 218?: string, 219?: string, 220?: string, 221?: string, 222?: string, 223?: string, 224?: string, 225?: string, 226?: string, 227?: string, 228?: string, 229?: string, 230?: string, 231?: string, 232?: string, 233?: string, 234?: string, 235?: string, 236?: string, 237?: string, 238?: string, 239?: string, 240?: string, 241?: string, 242?: string, 243?: string, 244?: string, 245?: string, 246?: string, 247?: string, 248?: string, 249?: string, 250?: string, 251?: string, 252?: string, 253?: string, 254?: string, 255?: string, 256?: string, 257?: string, 258?: string, 259?: string, 260?: string, 261?: string, 262?: string, 263?: string, 264?: string, 265?: string, 266?: string, 267?: string, 268?: string, 269?: string, 270?: string, 271?: string, 272?: string, 273?: string, 274?: string, 275?: string, 276?: string, 277?: string, 278?: string, 279?: string, 280?: string, 281?: string, 282?: string, 283?: string, 284?: string, 285?: string, 286?: string, 287?: string, 288?: string, 289?: string, 290?: string, 291?: string, 292?: string, 293?: string, 294?: string, 295?: string, 296?: string, 297?: string, 298?: string, 299?: string, 300?: string, 301?: string, 302?: string, 303?: string, 304?: string, 305?: string, 306?: string, 307?: string, 308?: string, 309?: string, 310?: string, 311?: string, 312?: string, 313?: string, 314?: string, 315?: string, 316?: string, 317?: string, 318?: string, 319?: string, 320?: string, 321?: string, 322?: string, 323?: string, 324?: string, 325?: string, 326?: string, 327?: string, 328?: string, 329?: string, 330?: string, 331?: string, 332?: string, 333?: string, 334?: string, 335?: string, 336?: string, 337?: string, 338?: string, 339?: string, 340?: string, 341?: string, 342?: string, 343?: string, 344?: string, 345?: string, 346?: string, 347?: string, 348?: string, 349?: string, 350?: string, 351?: string, 352?: string, 353?: string, 354?: string, 355?: string, 356?: string, 357?: string, 358?: string, 359?: string, 360?: string, 361?: string, 362?: string, 363?: string, 364?: string, 365?: string, 366?: string, 367?: string, 368?: string, 369?: string, 370?: string, 371?: string, 372?: string, 373?: string, 374?: string, 375?: string, 376?: string, 377?: string, 378?: string, 379?: string, 380?: string, 381?: string, 382?: string, 383?: string, 384?: string, 385?: string, 386?: string, 387?: string, 388?: string, 389?: string, 390?: string, 391?: string, 392?: string, 393?: string, 394?: string, 395?: string, 396?: string, 397?: string, 398?: string, 399?: string, 400?: string, 401?: string, 402?: string, 403?: string, 404?: string, 405?: string, 406?: string, 407?: string, 408?: string, 409?: string, 410?: string, 411?: string, 412?: string, 413?: string, 414?: string, 415?: string, 416?: string, 417?: string, 418?: string, 419?: string, 420?: string, 421?: string, 422?: string, 423?: string, 424?: string, 425?: string, 426?: string, 427?: string, 428?: string, 429?: string, 430?: string, 431?: string, 432?: string, 433?: string, 434?: string, 435?: string, 436?: string, 437?: string, 438?: string, 439?: string, 440?: string, 441?: string, 442?: string, 443?: string, 444?: string, 445?: string, 446?: string, 447?: string, 448?: string, 449?: string, 450?: string, 451?: string, 452?: string, 453?: string, 454?: string, 455?: string, 456?: string, 457?: string, 458?: string, 459?: string, 460?: string, 461?: string, 462?: string, 463?: string, 464?: string, 465?: string, 466?: string, 467?: string, 468?: string, 469?: string, 470?: string, 471?: string, 472?: string, 473?: string, 474?: string, 475?: string, 476?: string, 477?: string, 478?: string, 479?: string, 480?: string, 481?: string, 482?: string, 483?: string, 484?: string, 485?: string, 486?: string, 487?: string, 488?: string, 489?: string, 490?: string, 491?: string, 492?: string, 493?: string, 494?: string, 495?: string, 496?: string, 497?: string, 498?: string, 499?: string, 500?: string, 501?: string, 502?: string, 503?: string, 504?: string, 505?: string, 506?: string, 507?: string, 508?: string, 509?: string, 510?: string, 511?: string}', $listRow);
		} else {
			assertType('list<string>', $listRow);
		}
	}

	/**
	 * @param array{string}|array{0: int, 1?: string|null, 2?: int|null, 3?: float|null} $row
	 * @param int<2, 3> $twoOrThree
	 */
	protected function testOptionalKeysInUnionArrayWithIntRange($row, $twoOrThree): void
	{
		if (count($row) >= $twoOrThree) {
			assertType('array{0: int, 1?: string|null, 2?: int|null, 3?: float|null}', $row);
		} else {
			assertType('array{0: int, 1?: string|null, 2?: int|null, 3?: float|null}|array{string}', $row);
		}
	}
}

class FooBug
{
	public int $totalExpectedRows = 0;

	/** @var list<\stdClass> */
	public array $importedDaySummaryRows = [];

	public function sayHello(): void
	{
		assertType('int', $this->totalExpectedRows);
		assertType('list<stdClass>', $this->importedDaySummaryRows);
		if ($this->totalExpectedRows !== count($this->importedDaySummaryRows)) {
			assertType('int', $this->totalExpectedRows);
			assertType('list<stdClass>', $this->importedDaySummaryRows);
		}
		assertType('int', $this->totalExpectedRows);
		assertType('list<stdClass>', $this->importedDaySummaryRows);
	}
}

class FooBugPositiveInt
{
	/**
	 * @var positive-int
	 */
	public int $totalExpectedRows = 1;

	/** @var list<\stdClass> */
	public array $importedDaySummaryRows = [];

	public function sayHello(): void
	{
		assertType('int<1, max>', $this->totalExpectedRows);
		assertType('list<stdClass>', $this->importedDaySummaryRows);
		if ($this->totalExpectedRows !== count($this->importedDaySummaryRows)) {
			assertType('int<1, max>', $this->totalExpectedRows);
			assertType('list<stdClass>', $this->importedDaySummaryRows);
		}
		assertType('int<1, max>', $this->totalExpectedRows);
		assertType('list<stdClass>', $this->importedDaySummaryRows);
	}
}
