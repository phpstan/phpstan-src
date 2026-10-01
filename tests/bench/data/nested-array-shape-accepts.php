<?php declare(strict_types = 1);

namespace BenchNestedArrayShapeAccepts;

/**
 * Passing an array shape on to a parameter of the same shape checks every key, including the
 * keys that refer to the nested shape.
 *
 * @phpstan-type Value = 'a'|'b'|'c'|'d'|string
 * @phpstan-type Inner = array{p0?: Value, p1?: Value, p2?: Value, p3?: Value, p4?: Value, p5?: Value, p6?: Value, p7?: Value, p8?: Value, p9?: Value, p10?: Value, p11?: Value, p12?: Value, p13?: Value, p14?: Value, p15?: Value, p16?: Value, p17?: Value, p18?: Value, p19?: Value, p20?: Value, p21?: Value, p22?: Value, p23?: Value, p24?: Value, p25?: Value, p26?: Value, p27?: Value, p28?: Value, p29?: Value, p30?: Value, p31?: Value, p32?: Value, p33?: Value, p34?: Value, p35?: Value, p36?: Value, p37?: Value, p38?: Value, p39?: Value, p40?: Value, p41?: Value, p42?: Value, p43?: Value, p44?: Value, p45?: Value, p46?: Value, p47?: Value, p48?: Value, p49?: Value, p50?: Value, p51?: Value, p52?: Value, p53?: Value, p54?: Value, p55?: Value, p56?: Value, p57?: Value, p58?: Value, p59?: Value, p60?: Value, p61?: Value, p62?: Value, p63?: Value, p64?: Value, p65?: Value, p66?: Value, p67?: Value, p68?: Value, p69?: Value, p70?: Value, p71?: Value, p72?: Value, p73?: Value, p74?: Value, p75?: Value, p76?: Value, p77?: Value, p78?: Value, p79?: Value, p80?: Value, p81?: Value, p82?: Value, p83?: Value, p84?: Value, p85?: Value, p86?: Value, p87?: Value, p88?: Value, p89?: Value, p90?: Value, p91?: Value, p92?: Value, p93?: Value, p94?: Value, p95?: Value, p96?: Value, p97?: Value, p98?: Value, p99?: Value, p100?: Value, p101?: Value, p102?: Value, p103?: Value, p104?: Value, p105?: Value, p106?: Value, p107?: Value, p108?: Value, p109?: Value, p110?: Value, p111?: Value, p112?: Value, p113?: Value, p114?: Value, p115?: Value, p116?: Value, p117?: Value, p118?: Value, p119?: Value, p120?: Value, p121?: Value, p122?: Value, p123?: Value, p124?: Value, p125?: Value, p126?: Value, p127?: Value, p128?: Value, p129?: Value, p130?: Value, p131?: Value, p132?: Value, p133?: Value, p134?: Value, p135?: Value, p136?: Value, p137?: Value, p138?: Value, p139?: Value, p140?: Value, p141?: Value, p142?: Value, p143?: Value, p144?: Value, p145?: Value, p146?: Value, p147?: Value, p148?: Value, p149?: Value}
 * @phpstan-type Outer = array{p0?: Value, p1?: Value, p2?: Value, p3?: Value, p4?: Value, p5?: Value, p6?: Value, p7?: Value, p8?: Value, p9?: Value, p10?: Value, p11?: Value, p12?: Value, p13?: Value, p14?: Value, p15?: Value, p16?: Value, p17?: Value, p18?: Value, p19?: Value, p20?: Value, p21?: Value, p22?: Value, p23?: Value, p24?: Value, p25?: Value, p26?: Value, p27?: Value, p28?: Value, p29?: Value, p30?: Value, p31?: Value, p32?: Value, p33?: Value, p34?: Value, p35?: Value, p36?: Value, p37?: Value, p38?: Value, p39?: Value, p40?: Value, p41?: Value, p42?: Value, p43?: Value, p44?: Value, p45?: Value, p46?: Value, p47?: Value, p48?: Value, p49?: Value, p50?: Value, p51?: Value, p52?: Value, p53?: Value, p54?: Value, p55?: Value, p56?: Value, p57?: Value, p58?: Value, p59?: Value, p60?: Value, p61?: Value, p62?: Value, p63?: Value, p64?: Value, p65?: Value, p66?: Value, p67?: Value, p68?: Value, p69?: Value, p70?: Value, p71?: Value, p72?: Value, p73?: Value, p74?: Value, p75?: Value, p76?: Value, p77?: Value, p78?: Value, p79?: Value, p80?: Value, p81?: Value, p82?: Value, p83?: Value, p84?: Value, p85?: Value, p86?: Value, p87?: Value, p88?: Value, p89?: Value, p90?: Value, p91?: Value, p92?: Value, p93?: Value, p94?: Value, p95?: Value, p96?: Value, p97?: Value, p98?: Value, p99?: Value, p100?: Value, p101?: Value, p102?: Value, p103?: Value, p104?: Value, p105?: Value, p106?: Value, p107?: Value, p108?: Value, p109?: Value, p110?: Value, p111?: Value, p112?: Value, p113?: Value, p114?: Value, p115?: Value, p116?: Value, p117?: Value, p118?: Value, p119?: Value, p120?: Value, p121?: Value, p122?: Value, p123?: Value, p124?: Value, p125?: Value, p126?: Value, p127?: Value, p128?: Value, p129?: Value, p130?: Value, p131?: Value, p132?: Value, p133?: Value, p134?: Value, p135?: Value, p136?: Value, p137?: Value, p138?: Value, p139?: Value, p140?: Value, p141?: Value, p142?: Value, p143?: Value, p144?: Value, p145?: Value, p146?: Value, p147?: Value, p148?: Value, p149?: Value, c0?: Inner, c1?: Inner, c2?: Inner, c3?: Inner, c4?: Inner, c5?: Inner, c6?: Inner, c7?: Inner, c8?: Inner, c9?: Inner, c10?: Inner, c11?: Inner, c12?: Inner, c13?: Inner, c14?: Inner, c15?: Inner, c16?: Inner, c17?: Inner, c18?: Inner, c19?: Inner, c20?: Inner, c21?: Inner, c22?: Inner, c23?: Inner, c24?: Inner, c25?: Inner, c26?: Inner, c27?: Inner, c28?: Inner, c29?: Inner, c30?: Inner, c31?: Inner, c32?: Inner, c33?: Inner, c34?: Inner, c35?: Inner, c36?: Inner, c37?: Inner, c38?: Inner, c39?: Inner}
 */
final class Styles
{

	/** @param Outer|null $styles */
	public function css(?array $styles): string
	{
		return '';
	}

	/** @param Outer $styles */
	public function classes(array $styles): string
	{
		return $this->css($styles);
	}

}
