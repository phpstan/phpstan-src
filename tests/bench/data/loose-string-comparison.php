<?php

/*
 * instructions: 10.22B
 * cycles: 12.25B
 * memory: 85.98MiB
 */

namespace LooseStringComparison;

/**
 * Loosely comparing a string with non-numeric constant strings is a string
 * comparison, so it resolves like `===` without computing the values each
 * side can loosely equal.
 */
function switchCases(string $op): bool
{
	switch ($op) {
		case 'x0':
		case 'x1':
		case 'x2':
		case 'x3':
		case 'x4':
		case 'x5':
		case 'x6':
		case 'x7':
		case 'x8':
		case 'x9':
		case 'x10':
		case 'x11':
		case 'x12':
		case 'x13':
		case 'x14':
		case 'x15':
		case 'x16':
		case 'x17':
		case 'x18':
		case 'x19':
		case 'x20':
		case 'x21':
		case 'x22':
		case 'x23':
		case 'x24':
		case 'x25':
		case 'x26':
		case 'x27':
		case 'x28':
		case 'x29':
		case 'x30':
		case 'x31':
		case 'x32':
		case 'x33':
		case 'x34':
		case 'x35':
		case 'x36':
		case 'x37':
		case 'x38':
		case 'x39':
		case 'x40':
		case 'x41':
		case 'x42':
		case 'x43':
		case 'x44':
		case 'x45':
		case 'x46':
		case 'x47':
		case 'x48':
		case 'x49':
		case 'x50':
		case 'x51':
		case 'x52':
		case 'x53':
		case 'x54':
		case 'x55':
		case 'x56':
		case 'x57':
		case 'x58':
		case 'x59':
		case 'x60':
		case 'x61':
		case 'x62':
		case 'x63':
		case 'x64':
		case 'x65':
		case 'x66':
		case 'x67':
		case 'x68':
		case 'x69':
		case 'x70':
		case 'x71':
		case 'x72':
		case 'x73':
		case 'x74':
		case 'x75':
		case 'x76':
		case 'x77':
		case 'x78':
		case 'x79':
		case 'x80':
		case 'x81':
		case 'x82':
		case 'x83':
		case 'x84':
		case 'x85':
		case 'x86':
		case 'x87':
		case 'x88':
		case 'x89':
		case 'x90':
		case 'x91':
		case 'x92':
		case 'x93':
		case 'x94':
		case 'x95':
		case 'x96':
		case 'x97':
		case 'x98':
		case 'x99':
		case 'x100':
		case 'x101':
		case 'x102':
		case 'x103':
		case 'x104':
		case 'x105':
		case 'x106':
		case 'x107':
		case 'x108':
		case 'x109':
		case 'x110':
		case 'x111':
		case 'x112':
		case 'x113':
		case 'x114':
		case 'x115':
		case 'x116':
		case 'x117':
		case 'x118':
		case 'x119':
		case 'x120':
		case 'x121':
		case 'x122':
		case 'x123':
		case 'x124':
		case 'x125':
		case 'x126':
		case 'x127':
		case 'x128':
		case 'x129':
		case 'x130':
		case 'x131':
		case 'x132':
		case 'x133':
		case 'x134':
		case 'x135':
		case 'x136':
		case 'x137':
		case 'x138':
		case 'x139':
		case 'x140':
		case 'x141':
		case 'x142':
		case 'x143':
		case 'x144':
		case 'x145':
		case 'x146':
		case 'x147':
		case 'x148':
		case 'x149':
		case 'x150':
		case 'x151':
		case 'x152':
		case 'x153':
		case 'x154':
		case 'x155':
		case 'x156':
		case 'x157':
		case 'x158':
		case 'x159':
		case 'x160':
		case 'x161':
		case 'x162':
		case 'x163':
		case 'x164':
		case 'x165':
		case 'x166':
		case 'x167':
		case 'x168':
		case 'x169':
		case 'x170':
		case 'x171':
		case 'x172':
		case 'x173':
		case 'x174':
		case 'x175':
		case 'x176':
		case 'x177':
		case 'x178':
		case 'x179':
		case 'x180':
		case 'x181':
		case 'x182':
		case 'x183':
		case 'x184':
		case 'x185':
		case 'x186':
		case 'x187':
		case 'x188':
		case 'x189':
		case 'x190':
		case 'x191':
		case 'x192':
		case 'x193':
		case 'x194':
		case 'x195':
		case 'x196':
		case 'x197':
		case 'x198':
		case 'x199':
		case 'x200':
		case 'x201':
		case 'x202':
		case 'x203':
		case 'x204':
		case 'x205':
		case 'x206':
		case 'x207':
		case 'x208':
		case 'x209':
		case 'x210':
		case 'x211':
		case 'x212':
		case 'x213':
		case 'x214':
		case 'x215':
		case 'x216':
		case 'x217':
		case 'x218':
		case 'x219':
		case 'x220':
		case 'x221':
		case 'x222':
		case 'x223':
		case 'x224':
		case 'x225':
		case 'x226':
		case 'x227':
		case 'x228':
		case 'x229':
		case 'x230':
		case 'x231':
		case 'x232':
		case 'x233':
		case 'x234':
		case 'x235':
		case 'x236':
		case 'x237':
		case 'x238':
		case 'x239':
		case 'x240':
		case 'x241':
		case 'x242':
		case 'x243':
		case 'x244':
		case 'x245':
		case 'x246':
		case 'x247':
		case 'x248':
		case 'x249':
		case 'x250':
		case 'x251':
		case 'x252':
		case 'x253':
		case 'x254':
		case 'x255':
		case 'x256':
		case 'x257':
		case 'x258':
		case 'x259':
		case 'x260':
		case 'x261':
		case 'x262':
		case 'x263':
		case 'x264':
		case 'x265':
		case 'x266':
		case 'x267':
		case 'x268':
		case 'x269':
		case 'x270':
		case 'x271':
		case 'x272':
		case 'x273':
		case 'x274':
		case 'x275':
		case 'x276':
		case 'x277':
		case 'x278':
		case 'x279':
		case 'x280':
		case 'x281':
		case 'x282':
		case 'x283':
		case 'x284':
		case 'x285':
		case 'x286':
		case 'x287':
		case 'x288':
		case 'x289':
		case 'x290':
		case 'x291':
		case 'x292':
		case 'x293':
		case 'x294':
		case 'x295':
		case 'x296':
		case 'x297':
		case 'x298':
		case 'x299':
		case 'x300':
		case 'x301':
		case 'x302':
		case 'x303':
		case 'x304':
		case 'x305':
		case 'x306':
		case 'x307':
		case 'x308':
		case 'x309':
		case 'x310':
		case 'x311':
		case 'x312':
		case 'x313':
		case 'x314':
		case 'x315':
		case 'x316':
		case 'x317':
		case 'x318':
		case 'x319':
		case 'x320':
		case 'x321':
		case 'x322':
		case 'x323':
		case 'x324':
		case 'x325':
		case 'x326':
		case 'x327':
		case 'x328':
		case 'x329':
		case 'x330':
		case 'x331':
		case 'x332':
		case 'x333':
		case 'x334':
		case 'x335':
		case 'x336':
		case 'x337':
		case 'x338':
		case 'x339':
		case 'x340':
		case 'x341':
		case 'x342':
		case 'x343':
		case 'x344':
		case 'x345':
		case 'x346':
		case 'x347':
		case 'x348':
		case 'x349':
		case 'x350':
		case 'x351':
		case 'x352':
		case 'x353':
		case 'x354':
		case 'x355':
		case 'x356':
		case 'x357':
		case 'x358':
		case 'x359':
		case 'x360':
		case 'x361':
		case 'x362':
		case 'x363':
		case 'x364':
		case 'x365':
		case 'x366':
		case 'x367':
		case 'x368':
		case 'x369':
		case 'x370':
		case 'x371':
		case 'x372':
		case 'x373':
		case 'x374':
		case 'x375':
		case 'x376':
		case 'x377':
		case 'x378':
		case 'x379':
		case 'x380':
		case 'x381':
		case 'x382':
		case 'x383':
		case 'x384':
		case 'x385':
		case 'x386':
		case 'x387':
		case 'x388':
		case 'x389':
		case 'x390':
		case 'x391':
		case 'x392':
		case 'x393':
		case 'x394':
		case 'x395':
		case 'x396':
		case 'x397':
		case 'x398':
		case 'x399':
		case 'x400':
		case 'x401':
		case 'x402':
		case 'x403':
		case 'x404':
		case 'x405':
		case 'x406':
		case 'x407':
		case 'x408':
		case 'x409':
		case 'x410':
		case 'x411':
		case 'x412':
		case 'x413':
		case 'x414':
		case 'x415':
		case 'x416':
		case 'x417':
		case 'x418':
		case 'x419':
		case 'x420':
		case 'x421':
		case 'x422':
		case 'x423':
		case 'x424':
		case 'x425':
		case 'x426':
		case 'x427':
		case 'x428':
		case 'x429':
		case 'x430':
		case 'x431':
		case 'x432':
		case 'x433':
		case 'x434':
		case 'x435':
		case 'x436':
		case 'x437':
		case 'x438':
		case 'x439':
		case 'x440':
		case 'x441':
		case 'x442':
		case 'x443':
		case 'x444':
		case 'x445':
		case 'x446':
		case 'x447':
		case 'x448':
		case 'x449':
		case 'x450':
		case 'x451':
		case 'x452':
		case 'x453':
		case 'x454':
		case 'x455':
		case 'x456':
		case 'x457':
		case 'x458':
		case 'x459':
		case 'x460':
		case 'x461':
		case 'x462':
		case 'x463':
		case 'x464':
		case 'x465':
		case 'x466':
		case 'x467':
		case 'x468':
		case 'x469':
		case 'x470':
		case 'x471':
		case 'x472':
		case 'x473':
		case 'x474':
		case 'x475':
		case 'x476':
		case 'x477':
		case 'x478':
		case 'x479':
		case 'x480':
		case 'x481':
		case 'x482':
		case 'x483':
		case 'x484':
		case 'x485':
		case 'x486':
		case 'x487':
		case 'x488':
		case 'x489':
		case 'x490':
		case 'x491':
		case 'x492':
		case 'x493':
		case 'x494':
		case 'x495':
		case 'x496':
		case 'x497':
		case 'x498':
		case 'x499':
		case 'x500':
		case 'x501':
		case 'x502':
		case 'x503':
		case 'x504':
		case 'x505':
		case 'x506':
		case 'x507':
		case 'x508':
		case 'x509':
		case 'x510':
		case 'x511':
		case 'x512':
		case 'x513':
		case 'x514':
		case 'x515':
		case 'x516':
		case 'x517':
		case 'x518':
		case 'x519':
		case 'x520':
		case 'x521':
		case 'x522':
		case 'x523':
		case 'x524':
		case 'x525':
		case 'x526':
		case 'x527':
		case 'x528':
		case 'x529':
		case 'x530':
		case 'x531':
		case 'x532':
		case 'x533':
		case 'x534':
		case 'x535':
		case 'x536':
		case 'x537':
		case 'x538':
		case 'x539':
		case 'x540':
		case 'x541':
		case 'x542':
		case 'x543':
		case 'x544':
		case 'x545':
		case 'x546':
		case 'x547':
		case 'x548':
		case 'x549':
		case 'x550':
		case 'x551':
		case 'x552':
		case 'x553':
		case 'x554':
		case 'x555':
		case 'x556':
		case 'x557':
		case 'x558':
		case 'x559':
		case 'x560':
		case 'x561':
		case 'x562':
		case 'x563':
		case 'x564':
		case 'x565':
		case 'x566':
		case 'x567':
		case 'x568':
		case 'x569':
		case 'x570':
		case 'x571':
		case 'x572':
		case 'x573':
		case 'x574':
		case 'x575':
		case 'x576':
		case 'x577':
		case 'x578':
		case 'x579':
		case 'x580':
		case 'x581':
		case 'x582':
		case 'x583':
		case 'x584':
		case 'x585':
		case 'x586':
		case 'x587':
		case 'x588':
		case 'x589':
		case 'x590':
		case 'x591':
		case 'x592':
		case 'x593':
		case 'x594':
		case 'x595':
		case 'x596':
		case 'x597':
		case 'x598':
		case 'x599':
		case 'x600':
		case 'x601':
		case 'x602':
		case 'x603':
		case 'x604':
		case 'x605':
		case 'x606':
		case 'x607':
		case 'x608':
		case 'x609':
		case 'x610':
		case 'x611':
		case 'x612':
		case 'x613':
		case 'x614':
		case 'x615':
		case 'x616':
		case 'x617':
		case 'x618':
		case 'x619':
		case 'x620':
		case 'x621':
		case 'x622':
		case 'x623':
		case 'x624':
		case 'x625':
		case 'x626':
		case 'x627':
		case 'x628':
		case 'x629':
		case 'x630':
		case 'x631':
		case 'x632':
		case 'x633':
		case 'x634':
		case 'x635':
		case 'x636':
		case 'x637':
		case 'x638':
		case 'x639':
		case 'x640':
		case 'x641':
		case 'x642':
		case 'x643':
		case 'x644':
		case 'x645':
		case 'x646':
		case 'x647':
		case 'x648':
		case 'x649':
		case 'x650':
		case 'x651':
		case 'x652':
		case 'x653':
		case 'x654':
		case 'x655':
		case 'x656':
		case 'x657':
		case 'x658':
		case 'x659':
		case 'x660':
		case 'x661':
		case 'x662':
		case 'x663':
		case 'x664':
		case 'x665':
		case 'x666':
		case 'x667':
		case 'x668':
		case 'x669':
		case 'x670':
		case 'x671':
		case 'x672':
		case 'x673':
		case 'x674':
		case 'x675':
		case 'x676':
		case 'x677':
		case 'x678':
		case 'x679':
		case 'x680':
		case 'x681':
		case 'x682':
		case 'x683':
		case 'x684':
		case 'x685':
		case 'x686':
		case 'x687':
		case 'x688':
		case 'x689':
		case 'x690':
		case 'x691':
		case 'x692':
		case 'x693':
		case 'x694':
		case 'x695':
		case 'x696':
		case 'x697':
		case 'x698':
		case 'x699':
		case 'x700':
		case 'x701':
		case 'x702':
		case 'x703':
		case 'x704':
		case 'x705':
		case 'x706':
		case 'x707':
		case 'x708':
		case 'x709':
		case 'x710':
		case 'x711':
		case 'x712':
		case 'x713':
		case 'x714':
		case 'x715':
		case 'x716':
		case 'x717':
		case 'x718':
		case 'x719':
		case 'x720':
		case 'x721':
		case 'x722':
		case 'x723':
		case 'x724':
		case 'x725':
		case 'x726':
		case 'x727':
		case 'x728':
		case 'x729':
		case 'x730':
		case 'x731':
		case 'x732':
		case 'x733':
		case 'x734':
		case 'x735':
		case 'x736':
		case 'x737':
		case 'x738':
		case 'x739':
		case 'x740':
		case 'x741':
		case 'x742':
		case 'x743':
		case 'x744':
		case 'x745':
		case 'x746':
		case 'x747':
		case 'x748':
		case 'x749':
		case 'x750':
		case 'x751':
		case 'x752':
		case 'x753':
		case 'x754':
		case 'x755':
		case 'x756':
		case 'x757':
		case 'x758':
		case 'x759':
		case 'x760':
		case 'x761':
		case 'x762':
		case 'x763':
		case 'x764':
		case 'x765':
		case 'x766':
		case 'x767':
		case 'x768':
		case 'x769':
		case 'x770':
		case 'x771':
		case 'x772':
		case 'x773':
		case 'x774':
		case 'x775':
		case 'x776':
		case 'x777':
		case 'x778':
		case 'x779':
		case 'x780':
		case 'x781':
		case 'x782':
		case 'x783':
		case 'x784':
		case 'x785':
		case 'x786':
		case 'x787':
		case 'x788':
		case 'x789':
		case 'x790':
		case 'x791':
		case 'x792':
		case 'x793':
		case 'x794':
		case 'x795':
		case 'x796':
		case 'x797':
		case 'x798':
		case 'x799':
		case 'x800':
		case 'x801':
		case 'x802':
		case 'x803':
		case 'x804':
		case 'x805':
		case 'x806':
		case 'x807':
		case 'x808':
		case 'x809':
		case 'x810':
		case 'x811':
		case 'x812':
		case 'x813':
		case 'x814':
		case 'x815':
		case 'x816':
		case 'x817':
		case 'x818':
		case 'x819':
		case 'x820':
		case 'x821':
		case 'x822':
		case 'x823':
		case 'x824':
		case 'x825':
		case 'x826':
		case 'x827':
		case 'x828':
		case 'x829':
		case 'x830':
		case 'x831':
		case 'x832':
		case 'x833':
		case 'x834':
		case 'x835':
		case 'x836':
		case 'x837':
		case 'x838':
		case 'x839':
		case 'x840':
		case 'x841':
		case 'x842':
		case 'x843':
		case 'x844':
		case 'x845':
		case 'x846':
		case 'x847':
		case 'x848':
		case 'x849':
		case 'x850':
		case 'x851':
		case 'x852':
		case 'x853':
		case 'x854':
		case 'x855':
		case 'x856':
		case 'x857':
		case 'x858':
		case 'x859':
		case 'x860':
		case 'x861':
		case 'x862':
		case 'x863':
		case 'x864':
		case 'x865':
		case 'x866':
		case 'x867':
		case 'x868':
		case 'x869':
		case 'x870':
		case 'x871':
		case 'x872':
		case 'x873':
		case 'x874':
		case 'x875':
		case 'x876':
		case 'x877':
		case 'x878':
		case 'x879':
		case 'x880':
		case 'x881':
		case 'x882':
		case 'x883':
		case 'x884':
		case 'x885':
		case 'x886':
		case 'x887':
		case 'x888':
		case 'x889':
		case 'x890':
		case 'x891':
		case 'x892':
		case 'x893':
		case 'x894':
		case 'x895':
		case 'x896':
		case 'x897':
		case 'x898':
		case 'x899':
		case 'x900':
		case 'x901':
		case 'x902':
		case 'x903':
		case 'x904':
		case 'x905':
		case 'x906':
		case 'x907':
		case 'x908':
		case 'x909':
		case 'x910':
		case 'x911':
		case 'x912':
		case 'x913':
		case 'x914':
		case 'x915':
		case 'x916':
		case 'x917':
		case 'x918':
		case 'x919':
		case 'x920':
		case 'x921':
		case 'x922':
		case 'x923':
		case 'x924':
		case 'x925':
		case 'x926':
		case 'x927':
		case 'x928':
		case 'x929':
		case 'x930':
		case 'x931':
		case 'x932':
		case 'x933':
		case 'x934':
		case 'x935':
		case 'x936':
		case 'x937':
		case 'x938':
		case 'x939':
		case 'x940':
		case 'x941':
		case 'x942':
		case 'x943':
		case 'x944':
		case 'x945':
		case 'x946':
		case 'x947':
		case 'x948':
		case 'x949':
		case 'x950':
		case 'x951':
		case 'x952':
		case 'x953':
		case 'x954':
		case 'x955':
		case 'x956':
		case 'x957':
		case 'x958':
		case 'x959':
		case 'x960':
		case 'x961':
		case 'x962':
		case 'x963':
		case 'x964':
		case 'x965':
		case 'x966':
		case 'x967':
		case 'x968':
		case 'x969':
		case 'x970':
		case 'x971':
		case 'x972':
		case 'x973':
		case 'x974':
		case 'x975':
		case 'x976':
		case 'x977':
		case 'x978':
		case 'x979':
		case 'x980':
		case 'x981':
		case 'x982':
		case 'x983':
		case 'x984':
		case 'x985':
		case 'x986':
		case 'x987':
		case 'x988':
		case 'x989':
		case 'x990':
		case 'x991':
		case 'x992':
		case 'x993':
		case 'x994':
		case 'x995':
		case 'x996':
		case 'x997':
		case 'x998':
		case 'x999':
			return true;
	}
	return false;
}

function looseIfs(string $op): bool
{
	if ($op == 'x0') {
		return true;
	}
	if ($op == 'x1') {
		return true;
	}
	if ($op == 'x2') {
		return true;
	}
	if ($op == 'x3') {
		return true;
	}
	if ($op == 'x4') {
		return true;
	}
	if ($op == 'x5') {
		return true;
	}
	if ($op == 'x6') {
		return true;
	}
	if ($op == 'x7') {
		return true;
	}
	if ($op == 'x8') {
		return true;
	}
	if ($op == 'x9') {
		return true;
	}
	if ($op == 'x10') {
		return true;
	}
	if ($op == 'x11') {
		return true;
	}
	if ($op == 'x12') {
		return true;
	}
	if ($op == 'x13') {
		return true;
	}
	if ($op == 'x14') {
		return true;
	}
	if ($op == 'x15') {
		return true;
	}
	if ($op == 'x16') {
		return true;
	}
	if ($op == 'x17') {
		return true;
	}
	if ($op == 'x18') {
		return true;
	}
	if ($op == 'x19') {
		return true;
	}
	if ($op == 'x20') {
		return true;
	}
	if ($op == 'x21') {
		return true;
	}
	if ($op == 'x22') {
		return true;
	}
	if ($op == 'x23') {
		return true;
	}
	if ($op == 'x24') {
		return true;
	}
	if ($op == 'x25') {
		return true;
	}
	if ($op == 'x26') {
		return true;
	}
	if ($op == 'x27') {
		return true;
	}
	if ($op == 'x28') {
		return true;
	}
	if ($op == 'x29') {
		return true;
	}
	if ($op == 'x30') {
		return true;
	}
	if ($op == 'x31') {
		return true;
	}
	if ($op == 'x32') {
		return true;
	}
	if ($op == 'x33') {
		return true;
	}
	if ($op == 'x34') {
		return true;
	}
	if ($op == 'x35') {
		return true;
	}
	if ($op == 'x36') {
		return true;
	}
	if ($op == 'x37') {
		return true;
	}
	if ($op == 'x38') {
		return true;
	}
	if ($op == 'x39') {
		return true;
	}
	if ($op == 'x40') {
		return true;
	}
	if ($op == 'x41') {
		return true;
	}
	if ($op == 'x42') {
		return true;
	}
	if ($op == 'x43') {
		return true;
	}
	if ($op == 'x44') {
		return true;
	}
	if ($op == 'x45') {
		return true;
	}
	if ($op == 'x46') {
		return true;
	}
	if ($op == 'x47') {
		return true;
	}
	if ($op == 'x48') {
		return true;
	}
	if ($op == 'x49') {
		return true;
	}
	if ($op == 'x50') {
		return true;
	}
	if ($op == 'x51') {
		return true;
	}
	if ($op == 'x52') {
		return true;
	}
	if ($op == 'x53') {
		return true;
	}
	if ($op == 'x54') {
		return true;
	}
	if ($op == 'x55') {
		return true;
	}
	if ($op == 'x56') {
		return true;
	}
	if ($op == 'x57') {
		return true;
	}
	if ($op == 'x58') {
		return true;
	}
	if ($op == 'x59') {
		return true;
	}
	if ($op == 'x60') {
		return true;
	}
	if ($op == 'x61') {
		return true;
	}
	if ($op == 'x62') {
		return true;
	}
	if ($op == 'x63') {
		return true;
	}
	if ($op == 'x64') {
		return true;
	}
	if ($op == 'x65') {
		return true;
	}
	if ($op == 'x66') {
		return true;
	}
	if ($op == 'x67') {
		return true;
	}
	if ($op == 'x68') {
		return true;
	}
	if ($op == 'x69') {
		return true;
	}
	if ($op == 'x70') {
		return true;
	}
	if ($op == 'x71') {
		return true;
	}
	if ($op == 'x72') {
		return true;
	}
	if ($op == 'x73') {
		return true;
	}
	if ($op == 'x74') {
		return true;
	}
	if ($op == 'x75') {
		return true;
	}
	if ($op == 'x76') {
		return true;
	}
	if ($op == 'x77') {
		return true;
	}
	if ($op == 'x78') {
		return true;
	}
	if ($op == 'x79') {
		return true;
	}
	if ($op == 'x80') {
		return true;
	}
	if ($op == 'x81') {
		return true;
	}
	if ($op == 'x82') {
		return true;
	}
	if ($op == 'x83') {
		return true;
	}
	if ($op == 'x84') {
		return true;
	}
	if ($op == 'x85') {
		return true;
	}
	if ($op == 'x86') {
		return true;
	}
	if ($op == 'x87') {
		return true;
	}
	if ($op == 'x88') {
		return true;
	}
	if ($op == 'x89') {
		return true;
	}
	if ($op == 'x90') {
		return true;
	}
	if ($op == 'x91') {
		return true;
	}
	if ($op == 'x92') {
		return true;
	}
	if ($op == 'x93') {
		return true;
	}
	if ($op == 'x94') {
		return true;
	}
	if ($op == 'x95') {
		return true;
	}
	if ($op == 'x96') {
		return true;
	}
	if ($op == 'x97') {
		return true;
	}
	if ($op == 'x98') {
		return true;
	}
	if ($op == 'x99') {
		return true;
	}
	if ($op == 'x100') {
		return true;
	}
	if ($op == 'x101') {
		return true;
	}
	if ($op == 'x102') {
		return true;
	}
	if ($op == 'x103') {
		return true;
	}
	if ($op == 'x104') {
		return true;
	}
	if ($op == 'x105') {
		return true;
	}
	if ($op == 'x106') {
		return true;
	}
	if ($op == 'x107') {
		return true;
	}
	if ($op == 'x108') {
		return true;
	}
	if ($op == 'x109') {
		return true;
	}
	if ($op == 'x110') {
		return true;
	}
	if ($op == 'x111') {
		return true;
	}
	if ($op == 'x112') {
		return true;
	}
	if ($op == 'x113') {
		return true;
	}
	if ($op == 'x114') {
		return true;
	}
	if ($op == 'x115') {
		return true;
	}
	if ($op == 'x116') {
		return true;
	}
	if ($op == 'x117') {
		return true;
	}
	if ($op == 'x118') {
		return true;
	}
	if ($op == 'x119') {
		return true;
	}
	if ($op == 'x120') {
		return true;
	}
	if ($op == 'x121') {
		return true;
	}
	if ($op == 'x122') {
		return true;
	}
	if ($op == 'x123') {
		return true;
	}
	if ($op == 'x124') {
		return true;
	}
	if ($op == 'x125') {
		return true;
	}
	if ($op == 'x126') {
		return true;
	}
	if ($op == 'x127') {
		return true;
	}
	if ($op == 'x128') {
		return true;
	}
	if ($op == 'x129') {
		return true;
	}
	if ($op == 'x130') {
		return true;
	}
	if ($op == 'x131') {
		return true;
	}
	if ($op == 'x132') {
		return true;
	}
	if ($op == 'x133') {
		return true;
	}
	if ($op == 'x134') {
		return true;
	}
	if ($op == 'x135') {
		return true;
	}
	if ($op == 'x136') {
		return true;
	}
	if ($op == 'x137') {
		return true;
	}
	if ($op == 'x138') {
		return true;
	}
	if ($op == 'x139') {
		return true;
	}
	if ($op == 'x140') {
		return true;
	}
	if ($op == 'x141') {
		return true;
	}
	if ($op == 'x142') {
		return true;
	}
	if ($op == 'x143') {
		return true;
	}
	if ($op == 'x144') {
		return true;
	}
	if ($op == 'x145') {
		return true;
	}
	if ($op == 'x146') {
		return true;
	}
	if ($op == 'x147') {
		return true;
	}
	if ($op == 'x148') {
		return true;
	}
	if ($op == 'x149') {
		return true;
	}
	if ($op == 'x150') {
		return true;
	}
	if ($op == 'x151') {
		return true;
	}
	if ($op == 'x152') {
		return true;
	}
	if ($op == 'x153') {
		return true;
	}
	if ($op == 'x154') {
		return true;
	}
	if ($op == 'x155') {
		return true;
	}
	if ($op == 'x156') {
		return true;
	}
	if ($op == 'x157') {
		return true;
	}
	if ($op == 'x158') {
		return true;
	}
	if ($op == 'x159') {
		return true;
	}
	if ($op == 'x160') {
		return true;
	}
	if ($op == 'x161') {
		return true;
	}
	if ($op == 'x162') {
		return true;
	}
	if ($op == 'x163') {
		return true;
	}
	if ($op == 'x164') {
		return true;
	}
	if ($op == 'x165') {
		return true;
	}
	if ($op == 'x166') {
		return true;
	}
	if ($op == 'x167') {
		return true;
	}
	if ($op == 'x168') {
		return true;
	}
	if ($op == 'x169') {
		return true;
	}
	if ($op == 'x170') {
		return true;
	}
	if ($op == 'x171') {
		return true;
	}
	if ($op == 'x172') {
		return true;
	}
	if ($op == 'x173') {
		return true;
	}
	if ($op == 'x174') {
		return true;
	}
	if ($op == 'x175') {
		return true;
	}
	if ($op == 'x176') {
		return true;
	}
	if ($op == 'x177') {
		return true;
	}
	if ($op == 'x178') {
		return true;
	}
	if ($op == 'x179') {
		return true;
	}
	if ($op == 'x180') {
		return true;
	}
	if ($op == 'x181') {
		return true;
	}
	if ($op == 'x182') {
		return true;
	}
	if ($op == 'x183') {
		return true;
	}
	if ($op == 'x184') {
		return true;
	}
	if ($op == 'x185') {
		return true;
	}
	if ($op == 'x186') {
		return true;
	}
	if ($op == 'x187') {
		return true;
	}
	if ($op == 'x188') {
		return true;
	}
	if ($op == 'x189') {
		return true;
	}
	if ($op == 'x190') {
		return true;
	}
	if ($op == 'x191') {
		return true;
	}
	if ($op == 'x192') {
		return true;
	}
	if ($op == 'x193') {
		return true;
	}
	if ($op == 'x194') {
		return true;
	}
	if ($op == 'x195') {
		return true;
	}
	if ($op == 'x196') {
		return true;
	}
	if ($op == 'x197') {
		return true;
	}
	if ($op == 'x198') {
		return true;
	}
	if ($op == 'x199') {
		return true;
	}
	if ($op == 'x200') {
		return true;
	}
	if ($op == 'x201') {
		return true;
	}
	if ($op == 'x202') {
		return true;
	}
	if ($op == 'x203') {
		return true;
	}
	if ($op == 'x204') {
		return true;
	}
	if ($op == 'x205') {
		return true;
	}
	if ($op == 'x206') {
		return true;
	}
	if ($op == 'x207') {
		return true;
	}
	if ($op == 'x208') {
		return true;
	}
	if ($op == 'x209') {
		return true;
	}
	if ($op == 'x210') {
		return true;
	}
	if ($op == 'x211') {
		return true;
	}
	if ($op == 'x212') {
		return true;
	}
	if ($op == 'x213') {
		return true;
	}
	if ($op == 'x214') {
		return true;
	}
	if ($op == 'x215') {
		return true;
	}
	if ($op == 'x216') {
		return true;
	}
	if ($op == 'x217') {
		return true;
	}
	if ($op == 'x218') {
		return true;
	}
	if ($op == 'x219') {
		return true;
	}
	if ($op == 'x220') {
		return true;
	}
	if ($op == 'x221') {
		return true;
	}
	if ($op == 'x222') {
		return true;
	}
	if ($op == 'x223') {
		return true;
	}
	if ($op == 'x224') {
		return true;
	}
	if ($op == 'x225') {
		return true;
	}
	if ($op == 'x226') {
		return true;
	}
	if ($op == 'x227') {
		return true;
	}
	if ($op == 'x228') {
		return true;
	}
	if ($op == 'x229') {
		return true;
	}
	if ($op == 'x230') {
		return true;
	}
	if ($op == 'x231') {
		return true;
	}
	if ($op == 'x232') {
		return true;
	}
	if ($op == 'x233') {
		return true;
	}
	if ($op == 'x234') {
		return true;
	}
	if ($op == 'x235') {
		return true;
	}
	if ($op == 'x236') {
		return true;
	}
	if ($op == 'x237') {
		return true;
	}
	if ($op == 'x238') {
		return true;
	}
	if ($op == 'x239') {
		return true;
	}
	if ($op == 'x240') {
		return true;
	}
	if ($op == 'x241') {
		return true;
	}
	if ($op == 'x242') {
		return true;
	}
	if ($op == 'x243') {
		return true;
	}
	if ($op == 'x244') {
		return true;
	}
	if ($op == 'x245') {
		return true;
	}
	if ($op == 'x246') {
		return true;
	}
	if ($op == 'x247') {
		return true;
	}
	if ($op == 'x248') {
		return true;
	}
	if ($op == 'x249') {
		return true;
	}
	if ($op == 'x250') {
		return true;
	}
	if ($op == 'x251') {
		return true;
	}
	if ($op == 'x252') {
		return true;
	}
	if ($op == 'x253') {
		return true;
	}
	if ($op == 'x254') {
		return true;
	}
	if ($op == 'x255') {
		return true;
	}
	if ($op == 'x256') {
		return true;
	}
	if ($op == 'x257') {
		return true;
	}
	if ($op == 'x258') {
		return true;
	}
	if ($op == 'x259') {
		return true;
	}
	if ($op == 'x260') {
		return true;
	}
	if ($op == 'x261') {
		return true;
	}
	if ($op == 'x262') {
		return true;
	}
	if ($op == 'x263') {
		return true;
	}
	if ($op == 'x264') {
		return true;
	}
	if ($op == 'x265') {
		return true;
	}
	if ($op == 'x266') {
		return true;
	}
	if ($op == 'x267') {
		return true;
	}
	if ($op == 'x268') {
		return true;
	}
	if ($op == 'x269') {
		return true;
	}
	if ($op == 'x270') {
		return true;
	}
	if ($op == 'x271') {
		return true;
	}
	if ($op == 'x272') {
		return true;
	}
	if ($op == 'x273') {
		return true;
	}
	if ($op == 'x274') {
		return true;
	}
	if ($op == 'x275') {
		return true;
	}
	if ($op == 'x276') {
		return true;
	}
	if ($op == 'x277') {
		return true;
	}
	if ($op == 'x278') {
		return true;
	}
	if ($op == 'x279') {
		return true;
	}
	if ($op == 'x280') {
		return true;
	}
	if ($op == 'x281') {
		return true;
	}
	if ($op == 'x282') {
		return true;
	}
	if ($op == 'x283') {
		return true;
	}
	if ($op == 'x284') {
		return true;
	}
	if ($op == 'x285') {
		return true;
	}
	if ($op == 'x286') {
		return true;
	}
	if ($op == 'x287') {
		return true;
	}
	if ($op == 'x288') {
		return true;
	}
	if ($op == 'x289') {
		return true;
	}
	if ($op == 'x290') {
		return true;
	}
	if ($op == 'x291') {
		return true;
	}
	if ($op == 'x292') {
		return true;
	}
	if ($op == 'x293') {
		return true;
	}
	if ($op == 'x294') {
		return true;
	}
	if ($op == 'x295') {
		return true;
	}
	if ($op == 'x296') {
		return true;
	}
	if ($op == 'x297') {
		return true;
	}
	if ($op == 'x298') {
		return true;
	}
	if ($op == 'x299') {
		return true;
	}
	if ($op == 'x300') {
		return true;
	}
	if ($op == 'x301') {
		return true;
	}
	if ($op == 'x302') {
		return true;
	}
	if ($op == 'x303') {
		return true;
	}
	if ($op == 'x304') {
		return true;
	}
	if ($op == 'x305') {
		return true;
	}
	if ($op == 'x306') {
		return true;
	}
	if ($op == 'x307') {
		return true;
	}
	if ($op == 'x308') {
		return true;
	}
	if ($op == 'x309') {
		return true;
	}
	if ($op == 'x310') {
		return true;
	}
	if ($op == 'x311') {
		return true;
	}
	if ($op == 'x312') {
		return true;
	}
	if ($op == 'x313') {
		return true;
	}
	if ($op == 'x314') {
		return true;
	}
	if ($op == 'x315') {
		return true;
	}
	if ($op == 'x316') {
		return true;
	}
	if ($op == 'x317') {
		return true;
	}
	if ($op == 'x318') {
		return true;
	}
	if ($op == 'x319') {
		return true;
	}
	if ($op == 'x320') {
		return true;
	}
	if ($op == 'x321') {
		return true;
	}
	if ($op == 'x322') {
		return true;
	}
	if ($op == 'x323') {
		return true;
	}
	if ($op == 'x324') {
		return true;
	}
	if ($op == 'x325') {
		return true;
	}
	if ($op == 'x326') {
		return true;
	}
	if ($op == 'x327') {
		return true;
	}
	if ($op == 'x328') {
		return true;
	}
	if ($op == 'x329') {
		return true;
	}
	if ($op == 'x330') {
		return true;
	}
	if ($op == 'x331') {
		return true;
	}
	if ($op == 'x332') {
		return true;
	}
	if ($op == 'x333') {
		return true;
	}
	if ($op == 'x334') {
		return true;
	}
	if ($op == 'x335') {
		return true;
	}
	if ($op == 'x336') {
		return true;
	}
	if ($op == 'x337') {
		return true;
	}
	if ($op == 'x338') {
		return true;
	}
	if ($op == 'x339') {
		return true;
	}
	if ($op == 'x340') {
		return true;
	}
	if ($op == 'x341') {
		return true;
	}
	if ($op == 'x342') {
		return true;
	}
	if ($op == 'x343') {
		return true;
	}
	if ($op == 'x344') {
		return true;
	}
	if ($op == 'x345') {
		return true;
	}
	if ($op == 'x346') {
		return true;
	}
	if ($op == 'x347') {
		return true;
	}
	if ($op == 'x348') {
		return true;
	}
	if ($op == 'x349') {
		return true;
	}
	if ($op == 'x350') {
		return true;
	}
	if ($op == 'x351') {
		return true;
	}
	if ($op == 'x352') {
		return true;
	}
	if ($op == 'x353') {
		return true;
	}
	if ($op == 'x354') {
		return true;
	}
	if ($op == 'x355') {
		return true;
	}
	if ($op == 'x356') {
		return true;
	}
	if ($op == 'x357') {
		return true;
	}
	if ($op == 'x358') {
		return true;
	}
	if ($op == 'x359') {
		return true;
	}
	if ($op == 'x360') {
		return true;
	}
	if ($op == 'x361') {
		return true;
	}
	if ($op == 'x362') {
		return true;
	}
	if ($op == 'x363') {
		return true;
	}
	if ($op == 'x364') {
		return true;
	}
	if ($op == 'x365') {
		return true;
	}
	if ($op == 'x366') {
		return true;
	}
	if ($op == 'x367') {
		return true;
	}
	if ($op == 'x368') {
		return true;
	}
	if ($op == 'x369') {
		return true;
	}
	if ($op == 'x370') {
		return true;
	}
	if ($op == 'x371') {
		return true;
	}
	if ($op == 'x372') {
		return true;
	}
	if ($op == 'x373') {
		return true;
	}
	if ($op == 'x374') {
		return true;
	}
	if ($op == 'x375') {
		return true;
	}
	if ($op == 'x376') {
		return true;
	}
	if ($op == 'x377') {
		return true;
	}
	if ($op == 'x378') {
		return true;
	}
	if ($op == 'x379') {
		return true;
	}
	if ($op == 'x380') {
		return true;
	}
	if ($op == 'x381') {
		return true;
	}
	if ($op == 'x382') {
		return true;
	}
	if ($op == 'x383') {
		return true;
	}
	if ($op == 'x384') {
		return true;
	}
	if ($op == 'x385') {
		return true;
	}
	if ($op == 'x386') {
		return true;
	}
	if ($op == 'x387') {
		return true;
	}
	if ($op == 'x388') {
		return true;
	}
	if ($op == 'x389') {
		return true;
	}
	if ($op == 'x390') {
		return true;
	}
	if ($op == 'x391') {
		return true;
	}
	if ($op == 'x392') {
		return true;
	}
	if ($op == 'x393') {
		return true;
	}
	if ($op == 'x394') {
		return true;
	}
	if ($op == 'x395') {
		return true;
	}
	if ($op == 'x396') {
		return true;
	}
	if ($op == 'x397') {
		return true;
	}
	if ($op == 'x398') {
		return true;
	}
	if ($op == 'x399') {
		return true;
	}
	if ($op == 'x400') {
		return true;
	}
	if ($op == 'x401') {
		return true;
	}
	if ($op == 'x402') {
		return true;
	}
	if ($op == 'x403') {
		return true;
	}
	if ($op == 'x404') {
		return true;
	}
	if ($op == 'x405') {
		return true;
	}
	if ($op == 'x406') {
		return true;
	}
	if ($op == 'x407') {
		return true;
	}
	if ($op == 'x408') {
		return true;
	}
	if ($op == 'x409') {
		return true;
	}
	if ($op == 'x410') {
		return true;
	}
	if ($op == 'x411') {
		return true;
	}
	if ($op == 'x412') {
		return true;
	}
	if ($op == 'x413') {
		return true;
	}
	if ($op == 'x414') {
		return true;
	}
	if ($op == 'x415') {
		return true;
	}
	if ($op == 'x416') {
		return true;
	}
	if ($op == 'x417') {
		return true;
	}
	if ($op == 'x418') {
		return true;
	}
	if ($op == 'x419') {
		return true;
	}
	if ($op == 'x420') {
		return true;
	}
	if ($op == 'x421') {
		return true;
	}
	if ($op == 'x422') {
		return true;
	}
	if ($op == 'x423') {
		return true;
	}
	if ($op == 'x424') {
		return true;
	}
	if ($op == 'x425') {
		return true;
	}
	if ($op == 'x426') {
		return true;
	}
	if ($op == 'x427') {
		return true;
	}
	if ($op == 'x428') {
		return true;
	}
	if ($op == 'x429') {
		return true;
	}
	if ($op == 'x430') {
		return true;
	}
	if ($op == 'x431') {
		return true;
	}
	if ($op == 'x432') {
		return true;
	}
	if ($op == 'x433') {
		return true;
	}
	if ($op == 'x434') {
		return true;
	}
	if ($op == 'x435') {
		return true;
	}
	if ($op == 'x436') {
		return true;
	}
	if ($op == 'x437') {
		return true;
	}
	if ($op == 'x438') {
		return true;
	}
	if ($op == 'x439') {
		return true;
	}
	if ($op == 'x440') {
		return true;
	}
	if ($op == 'x441') {
		return true;
	}
	if ($op == 'x442') {
		return true;
	}
	if ($op == 'x443') {
		return true;
	}
	if ($op == 'x444') {
		return true;
	}
	if ($op == 'x445') {
		return true;
	}
	if ($op == 'x446') {
		return true;
	}
	if ($op == 'x447') {
		return true;
	}
	if ($op == 'x448') {
		return true;
	}
	if ($op == 'x449') {
		return true;
	}
	if ($op == 'x450') {
		return true;
	}
	if ($op == 'x451') {
		return true;
	}
	if ($op == 'x452') {
		return true;
	}
	if ($op == 'x453') {
		return true;
	}
	if ($op == 'x454') {
		return true;
	}
	if ($op == 'x455') {
		return true;
	}
	if ($op == 'x456') {
		return true;
	}
	if ($op == 'x457') {
		return true;
	}
	if ($op == 'x458') {
		return true;
	}
	if ($op == 'x459') {
		return true;
	}
	if ($op == 'x460') {
		return true;
	}
	if ($op == 'x461') {
		return true;
	}
	if ($op == 'x462') {
		return true;
	}
	if ($op == 'x463') {
		return true;
	}
	if ($op == 'x464') {
		return true;
	}
	if ($op == 'x465') {
		return true;
	}
	if ($op == 'x466') {
		return true;
	}
	if ($op == 'x467') {
		return true;
	}
	if ($op == 'x468') {
		return true;
	}
	if ($op == 'x469') {
		return true;
	}
	if ($op == 'x470') {
		return true;
	}
	if ($op == 'x471') {
		return true;
	}
	if ($op == 'x472') {
		return true;
	}
	if ($op == 'x473') {
		return true;
	}
	if ($op == 'x474') {
		return true;
	}
	if ($op == 'x475') {
		return true;
	}
	if ($op == 'x476') {
		return true;
	}
	if ($op == 'x477') {
		return true;
	}
	if ($op == 'x478') {
		return true;
	}
	if ($op == 'x479') {
		return true;
	}
	if ($op == 'x480') {
		return true;
	}
	if ($op == 'x481') {
		return true;
	}
	if ($op == 'x482') {
		return true;
	}
	if ($op == 'x483') {
		return true;
	}
	if ($op == 'x484') {
		return true;
	}
	if ($op == 'x485') {
		return true;
	}
	if ($op == 'x486') {
		return true;
	}
	if ($op == 'x487') {
		return true;
	}
	if ($op == 'x488') {
		return true;
	}
	if ($op == 'x489') {
		return true;
	}
	if ($op == 'x490') {
		return true;
	}
	if ($op == 'x491') {
		return true;
	}
	if ($op == 'x492') {
		return true;
	}
	if ($op == 'x493') {
		return true;
	}
	if ($op == 'x494') {
		return true;
	}
	if ($op == 'x495') {
		return true;
	}
	if ($op == 'x496') {
		return true;
	}
	if ($op == 'x497') {
		return true;
	}
	if ($op == 'x498') {
		return true;
	}
	if ($op == 'x499') {
		return true;
	}
	if ($op == 'x500') {
		return true;
	}
	if ($op == 'x501') {
		return true;
	}
	if ($op == 'x502') {
		return true;
	}
	if ($op == 'x503') {
		return true;
	}
	if ($op == 'x504') {
		return true;
	}
	if ($op == 'x505') {
		return true;
	}
	if ($op == 'x506') {
		return true;
	}
	if ($op == 'x507') {
		return true;
	}
	if ($op == 'x508') {
		return true;
	}
	if ($op == 'x509') {
		return true;
	}
	if ($op == 'x510') {
		return true;
	}
	if ($op == 'x511') {
		return true;
	}
	if ($op == 'x512') {
		return true;
	}
	if ($op == 'x513') {
		return true;
	}
	if ($op == 'x514') {
		return true;
	}
	if ($op == 'x515') {
		return true;
	}
	if ($op == 'x516') {
		return true;
	}
	if ($op == 'x517') {
		return true;
	}
	if ($op == 'x518') {
		return true;
	}
	if ($op == 'x519') {
		return true;
	}
	if ($op == 'x520') {
		return true;
	}
	if ($op == 'x521') {
		return true;
	}
	if ($op == 'x522') {
		return true;
	}
	if ($op == 'x523') {
		return true;
	}
	if ($op == 'x524') {
		return true;
	}
	if ($op == 'x525') {
		return true;
	}
	if ($op == 'x526') {
		return true;
	}
	if ($op == 'x527') {
		return true;
	}
	if ($op == 'x528') {
		return true;
	}
	if ($op == 'x529') {
		return true;
	}
	if ($op == 'x530') {
		return true;
	}
	if ($op == 'x531') {
		return true;
	}
	if ($op == 'x532') {
		return true;
	}
	if ($op == 'x533') {
		return true;
	}
	if ($op == 'x534') {
		return true;
	}
	if ($op == 'x535') {
		return true;
	}
	if ($op == 'x536') {
		return true;
	}
	if ($op == 'x537') {
		return true;
	}
	if ($op == 'x538') {
		return true;
	}
	if ($op == 'x539') {
		return true;
	}
	if ($op == 'x540') {
		return true;
	}
	if ($op == 'x541') {
		return true;
	}
	if ($op == 'x542') {
		return true;
	}
	if ($op == 'x543') {
		return true;
	}
	if ($op == 'x544') {
		return true;
	}
	if ($op == 'x545') {
		return true;
	}
	if ($op == 'x546') {
		return true;
	}
	if ($op == 'x547') {
		return true;
	}
	if ($op == 'x548') {
		return true;
	}
	if ($op == 'x549') {
		return true;
	}
	if ($op == 'x550') {
		return true;
	}
	if ($op == 'x551') {
		return true;
	}
	if ($op == 'x552') {
		return true;
	}
	if ($op == 'x553') {
		return true;
	}
	if ($op == 'x554') {
		return true;
	}
	if ($op == 'x555') {
		return true;
	}
	if ($op == 'x556') {
		return true;
	}
	if ($op == 'x557') {
		return true;
	}
	if ($op == 'x558') {
		return true;
	}
	if ($op == 'x559') {
		return true;
	}
	if ($op == 'x560') {
		return true;
	}
	if ($op == 'x561') {
		return true;
	}
	if ($op == 'x562') {
		return true;
	}
	if ($op == 'x563') {
		return true;
	}
	if ($op == 'x564') {
		return true;
	}
	if ($op == 'x565') {
		return true;
	}
	if ($op == 'x566') {
		return true;
	}
	if ($op == 'x567') {
		return true;
	}
	if ($op == 'x568') {
		return true;
	}
	if ($op == 'x569') {
		return true;
	}
	if ($op == 'x570') {
		return true;
	}
	if ($op == 'x571') {
		return true;
	}
	if ($op == 'x572') {
		return true;
	}
	if ($op == 'x573') {
		return true;
	}
	if ($op == 'x574') {
		return true;
	}
	if ($op == 'x575') {
		return true;
	}
	if ($op == 'x576') {
		return true;
	}
	if ($op == 'x577') {
		return true;
	}
	if ($op == 'x578') {
		return true;
	}
	if ($op == 'x579') {
		return true;
	}
	if ($op == 'x580') {
		return true;
	}
	if ($op == 'x581') {
		return true;
	}
	if ($op == 'x582') {
		return true;
	}
	if ($op == 'x583') {
		return true;
	}
	if ($op == 'x584') {
		return true;
	}
	if ($op == 'x585') {
		return true;
	}
	if ($op == 'x586') {
		return true;
	}
	if ($op == 'x587') {
		return true;
	}
	if ($op == 'x588') {
		return true;
	}
	if ($op == 'x589') {
		return true;
	}
	if ($op == 'x590') {
		return true;
	}
	if ($op == 'x591') {
		return true;
	}
	if ($op == 'x592') {
		return true;
	}
	if ($op == 'x593') {
		return true;
	}
	if ($op == 'x594') {
		return true;
	}
	if ($op == 'x595') {
		return true;
	}
	if ($op == 'x596') {
		return true;
	}
	if ($op == 'x597') {
		return true;
	}
	if ($op == 'x598') {
		return true;
	}
	if ($op == 'x599') {
		return true;
	}
	if ($op == 'x600') {
		return true;
	}
	if ($op == 'x601') {
		return true;
	}
	if ($op == 'x602') {
		return true;
	}
	if ($op == 'x603') {
		return true;
	}
	if ($op == 'x604') {
		return true;
	}
	if ($op == 'x605') {
		return true;
	}
	if ($op == 'x606') {
		return true;
	}
	if ($op == 'x607') {
		return true;
	}
	if ($op == 'x608') {
		return true;
	}
	if ($op == 'x609') {
		return true;
	}
	if ($op == 'x610') {
		return true;
	}
	if ($op == 'x611') {
		return true;
	}
	if ($op == 'x612') {
		return true;
	}
	if ($op == 'x613') {
		return true;
	}
	if ($op == 'x614') {
		return true;
	}
	if ($op == 'x615') {
		return true;
	}
	if ($op == 'x616') {
		return true;
	}
	if ($op == 'x617') {
		return true;
	}
	if ($op == 'x618') {
		return true;
	}
	if ($op == 'x619') {
		return true;
	}
	if ($op == 'x620') {
		return true;
	}
	if ($op == 'x621') {
		return true;
	}
	if ($op == 'x622') {
		return true;
	}
	if ($op == 'x623') {
		return true;
	}
	if ($op == 'x624') {
		return true;
	}
	if ($op == 'x625') {
		return true;
	}
	if ($op == 'x626') {
		return true;
	}
	if ($op == 'x627') {
		return true;
	}
	if ($op == 'x628') {
		return true;
	}
	if ($op == 'x629') {
		return true;
	}
	if ($op == 'x630') {
		return true;
	}
	if ($op == 'x631') {
		return true;
	}
	if ($op == 'x632') {
		return true;
	}
	if ($op == 'x633') {
		return true;
	}
	if ($op == 'x634') {
		return true;
	}
	if ($op == 'x635') {
		return true;
	}
	if ($op == 'x636') {
		return true;
	}
	if ($op == 'x637') {
		return true;
	}
	if ($op == 'x638') {
		return true;
	}
	if ($op == 'x639') {
		return true;
	}
	if ($op == 'x640') {
		return true;
	}
	if ($op == 'x641') {
		return true;
	}
	if ($op == 'x642') {
		return true;
	}
	if ($op == 'x643') {
		return true;
	}
	if ($op == 'x644') {
		return true;
	}
	if ($op == 'x645') {
		return true;
	}
	if ($op == 'x646') {
		return true;
	}
	if ($op == 'x647') {
		return true;
	}
	if ($op == 'x648') {
		return true;
	}
	if ($op == 'x649') {
		return true;
	}
	if ($op == 'x650') {
		return true;
	}
	if ($op == 'x651') {
		return true;
	}
	if ($op == 'x652') {
		return true;
	}
	if ($op == 'x653') {
		return true;
	}
	if ($op == 'x654') {
		return true;
	}
	if ($op == 'x655') {
		return true;
	}
	if ($op == 'x656') {
		return true;
	}
	if ($op == 'x657') {
		return true;
	}
	if ($op == 'x658') {
		return true;
	}
	if ($op == 'x659') {
		return true;
	}
	if ($op == 'x660') {
		return true;
	}
	if ($op == 'x661') {
		return true;
	}
	if ($op == 'x662') {
		return true;
	}
	if ($op == 'x663') {
		return true;
	}
	if ($op == 'x664') {
		return true;
	}
	if ($op == 'x665') {
		return true;
	}
	if ($op == 'x666') {
		return true;
	}
	if ($op == 'x667') {
		return true;
	}
	if ($op == 'x668') {
		return true;
	}
	if ($op == 'x669') {
		return true;
	}
	if ($op == 'x670') {
		return true;
	}
	if ($op == 'x671') {
		return true;
	}
	if ($op == 'x672') {
		return true;
	}
	if ($op == 'x673') {
		return true;
	}
	if ($op == 'x674') {
		return true;
	}
	if ($op == 'x675') {
		return true;
	}
	if ($op == 'x676') {
		return true;
	}
	if ($op == 'x677') {
		return true;
	}
	if ($op == 'x678') {
		return true;
	}
	if ($op == 'x679') {
		return true;
	}
	if ($op == 'x680') {
		return true;
	}
	if ($op == 'x681') {
		return true;
	}
	if ($op == 'x682') {
		return true;
	}
	if ($op == 'x683') {
		return true;
	}
	if ($op == 'x684') {
		return true;
	}
	if ($op == 'x685') {
		return true;
	}
	if ($op == 'x686') {
		return true;
	}
	if ($op == 'x687') {
		return true;
	}
	if ($op == 'x688') {
		return true;
	}
	if ($op == 'x689') {
		return true;
	}
	if ($op == 'x690') {
		return true;
	}
	if ($op == 'x691') {
		return true;
	}
	if ($op == 'x692') {
		return true;
	}
	if ($op == 'x693') {
		return true;
	}
	if ($op == 'x694') {
		return true;
	}
	if ($op == 'x695') {
		return true;
	}
	if ($op == 'x696') {
		return true;
	}
	if ($op == 'x697') {
		return true;
	}
	if ($op == 'x698') {
		return true;
	}
	if ($op == 'x699') {
		return true;
	}
	if ($op == 'x700') {
		return true;
	}
	if ($op == 'x701') {
		return true;
	}
	if ($op == 'x702') {
		return true;
	}
	if ($op == 'x703') {
		return true;
	}
	if ($op == 'x704') {
		return true;
	}
	if ($op == 'x705') {
		return true;
	}
	if ($op == 'x706') {
		return true;
	}
	if ($op == 'x707') {
		return true;
	}
	if ($op == 'x708') {
		return true;
	}
	if ($op == 'x709') {
		return true;
	}
	if ($op == 'x710') {
		return true;
	}
	if ($op == 'x711') {
		return true;
	}
	if ($op == 'x712') {
		return true;
	}
	if ($op == 'x713') {
		return true;
	}
	if ($op == 'x714') {
		return true;
	}
	if ($op == 'x715') {
		return true;
	}
	if ($op == 'x716') {
		return true;
	}
	if ($op == 'x717') {
		return true;
	}
	if ($op == 'x718') {
		return true;
	}
	if ($op == 'x719') {
		return true;
	}
	if ($op == 'x720') {
		return true;
	}
	if ($op == 'x721') {
		return true;
	}
	if ($op == 'x722') {
		return true;
	}
	if ($op == 'x723') {
		return true;
	}
	if ($op == 'x724') {
		return true;
	}
	if ($op == 'x725') {
		return true;
	}
	if ($op == 'x726') {
		return true;
	}
	if ($op == 'x727') {
		return true;
	}
	if ($op == 'x728') {
		return true;
	}
	if ($op == 'x729') {
		return true;
	}
	if ($op == 'x730') {
		return true;
	}
	if ($op == 'x731') {
		return true;
	}
	if ($op == 'x732') {
		return true;
	}
	if ($op == 'x733') {
		return true;
	}
	if ($op == 'x734') {
		return true;
	}
	if ($op == 'x735') {
		return true;
	}
	if ($op == 'x736') {
		return true;
	}
	if ($op == 'x737') {
		return true;
	}
	if ($op == 'x738') {
		return true;
	}
	if ($op == 'x739') {
		return true;
	}
	if ($op == 'x740') {
		return true;
	}
	if ($op == 'x741') {
		return true;
	}
	if ($op == 'x742') {
		return true;
	}
	if ($op == 'x743') {
		return true;
	}
	if ($op == 'x744') {
		return true;
	}
	if ($op == 'x745') {
		return true;
	}
	if ($op == 'x746') {
		return true;
	}
	if ($op == 'x747') {
		return true;
	}
	if ($op == 'x748') {
		return true;
	}
	if ($op == 'x749') {
		return true;
	}
	if ($op == 'x750') {
		return true;
	}
	if ($op == 'x751') {
		return true;
	}
	if ($op == 'x752') {
		return true;
	}
	if ($op == 'x753') {
		return true;
	}
	if ($op == 'x754') {
		return true;
	}
	if ($op == 'x755') {
		return true;
	}
	if ($op == 'x756') {
		return true;
	}
	if ($op == 'x757') {
		return true;
	}
	if ($op == 'x758') {
		return true;
	}
	if ($op == 'x759') {
		return true;
	}
	if ($op == 'x760') {
		return true;
	}
	if ($op == 'x761') {
		return true;
	}
	if ($op == 'x762') {
		return true;
	}
	if ($op == 'x763') {
		return true;
	}
	if ($op == 'x764') {
		return true;
	}
	if ($op == 'x765') {
		return true;
	}
	if ($op == 'x766') {
		return true;
	}
	if ($op == 'x767') {
		return true;
	}
	if ($op == 'x768') {
		return true;
	}
	if ($op == 'x769') {
		return true;
	}
	if ($op == 'x770') {
		return true;
	}
	if ($op == 'x771') {
		return true;
	}
	if ($op == 'x772') {
		return true;
	}
	if ($op == 'x773') {
		return true;
	}
	if ($op == 'x774') {
		return true;
	}
	if ($op == 'x775') {
		return true;
	}
	if ($op == 'x776') {
		return true;
	}
	if ($op == 'x777') {
		return true;
	}
	if ($op == 'x778') {
		return true;
	}
	if ($op == 'x779') {
		return true;
	}
	if ($op == 'x780') {
		return true;
	}
	if ($op == 'x781') {
		return true;
	}
	if ($op == 'x782') {
		return true;
	}
	if ($op == 'x783') {
		return true;
	}
	if ($op == 'x784') {
		return true;
	}
	if ($op == 'x785') {
		return true;
	}
	if ($op == 'x786') {
		return true;
	}
	if ($op == 'x787') {
		return true;
	}
	if ($op == 'x788') {
		return true;
	}
	if ($op == 'x789') {
		return true;
	}
	if ($op == 'x790') {
		return true;
	}
	if ($op == 'x791') {
		return true;
	}
	if ($op == 'x792') {
		return true;
	}
	if ($op == 'x793') {
		return true;
	}
	if ($op == 'x794') {
		return true;
	}
	if ($op == 'x795') {
		return true;
	}
	if ($op == 'x796') {
		return true;
	}
	if ($op == 'x797') {
		return true;
	}
	if ($op == 'x798') {
		return true;
	}
	if ($op == 'x799') {
		return true;
	}
	if ($op == 'x800') {
		return true;
	}
	if ($op == 'x801') {
		return true;
	}
	if ($op == 'x802') {
		return true;
	}
	if ($op == 'x803') {
		return true;
	}
	if ($op == 'x804') {
		return true;
	}
	if ($op == 'x805') {
		return true;
	}
	if ($op == 'x806') {
		return true;
	}
	if ($op == 'x807') {
		return true;
	}
	if ($op == 'x808') {
		return true;
	}
	if ($op == 'x809') {
		return true;
	}
	if ($op == 'x810') {
		return true;
	}
	if ($op == 'x811') {
		return true;
	}
	if ($op == 'x812') {
		return true;
	}
	if ($op == 'x813') {
		return true;
	}
	if ($op == 'x814') {
		return true;
	}
	if ($op == 'x815') {
		return true;
	}
	if ($op == 'x816') {
		return true;
	}
	if ($op == 'x817') {
		return true;
	}
	if ($op == 'x818') {
		return true;
	}
	if ($op == 'x819') {
		return true;
	}
	if ($op == 'x820') {
		return true;
	}
	if ($op == 'x821') {
		return true;
	}
	if ($op == 'x822') {
		return true;
	}
	if ($op == 'x823') {
		return true;
	}
	if ($op == 'x824') {
		return true;
	}
	if ($op == 'x825') {
		return true;
	}
	if ($op == 'x826') {
		return true;
	}
	if ($op == 'x827') {
		return true;
	}
	if ($op == 'x828') {
		return true;
	}
	if ($op == 'x829') {
		return true;
	}
	if ($op == 'x830') {
		return true;
	}
	if ($op == 'x831') {
		return true;
	}
	if ($op == 'x832') {
		return true;
	}
	if ($op == 'x833') {
		return true;
	}
	if ($op == 'x834') {
		return true;
	}
	if ($op == 'x835') {
		return true;
	}
	if ($op == 'x836') {
		return true;
	}
	if ($op == 'x837') {
		return true;
	}
	if ($op == 'x838') {
		return true;
	}
	if ($op == 'x839') {
		return true;
	}
	if ($op == 'x840') {
		return true;
	}
	if ($op == 'x841') {
		return true;
	}
	if ($op == 'x842') {
		return true;
	}
	if ($op == 'x843') {
		return true;
	}
	if ($op == 'x844') {
		return true;
	}
	if ($op == 'x845') {
		return true;
	}
	if ($op == 'x846') {
		return true;
	}
	if ($op == 'x847') {
		return true;
	}
	if ($op == 'x848') {
		return true;
	}
	if ($op == 'x849') {
		return true;
	}
	if ($op == 'x850') {
		return true;
	}
	if ($op == 'x851') {
		return true;
	}
	if ($op == 'x852') {
		return true;
	}
	if ($op == 'x853') {
		return true;
	}
	if ($op == 'x854') {
		return true;
	}
	if ($op == 'x855') {
		return true;
	}
	if ($op == 'x856') {
		return true;
	}
	if ($op == 'x857') {
		return true;
	}
	if ($op == 'x858') {
		return true;
	}
	if ($op == 'x859') {
		return true;
	}
	if ($op == 'x860') {
		return true;
	}
	if ($op == 'x861') {
		return true;
	}
	if ($op == 'x862') {
		return true;
	}
	if ($op == 'x863') {
		return true;
	}
	if ($op == 'x864') {
		return true;
	}
	if ($op == 'x865') {
		return true;
	}
	if ($op == 'x866') {
		return true;
	}
	if ($op == 'x867') {
		return true;
	}
	if ($op == 'x868') {
		return true;
	}
	if ($op == 'x869') {
		return true;
	}
	if ($op == 'x870') {
		return true;
	}
	if ($op == 'x871') {
		return true;
	}
	if ($op == 'x872') {
		return true;
	}
	if ($op == 'x873') {
		return true;
	}
	if ($op == 'x874') {
		return true;
	}
	if ($op == 'x875') {
		return true;
	}
	if ($op == 'x876') {
		return true;
	}
	if ($op == 'x877') {
		return true;
	}
	if ($op == 'x878') {
		return true;
	}
	if ($op == 'x879') {
		return true;
	}
	if ($op == 'x880') {
		return true;
	}
	if ($op == 'x881') {
		return true;
	}
	if ($op == 'x882') {
		return true;
	}
	if ($op == 'x883') {
		return true;
	}
	if ($op == 'x884') {
		return true;
	}
	if ($op == 'x885') {
		return true;
	}
	if ($op == 'x886') {
		return true;
	}
	if ($op == 'x887') {
		return true;
	}
	if ($op == 'x888') {
		return true;
	}
	if ($op == 'x889') {
		return true;
	}
	if ($op == 'x890') {
		return true;
	}
	if ($op == 'x891') {
		return true;
	}
	if ($op == 'x892') {
		return true;
	}
	if ($op == 'x893') {
		return true;
	}
	if ($op == 'x894') {
		return true;
	}
	if ($op == 'x895') {
		return true;
	}
	if ($op == 'x896') {
		return true;
	}
	if ($op == 'x897') {
		return true;
	}
	if ($op == 'x898') {
		return true;
	}
	if ($op == 'x899') {
		return true;
	}
	if ($op == 'x900') {
		return true;
	}
	if ($op == 'x901') {
		return true;
	}
	if ($op == 'x902') {
		return true;
	}
	if ($op == 'x903') {
		return true;
	}
	if ($op == 'x904') {
		return true;
	}
	if ($op == 'x905') {
		return true;
	}
	if ($op == 'x906') {
		return true;
	}
	if ($op == 'x907') {
		return true;
	}
	if ($op == 'x908') {
		return true;
	}
	if ($op == 'x909') {
		return true;
	}
	if ($op == 'x910') {
		return true;
	}
	if ($op == 'x911') {
		return true;
	}
	if ($op == 'x912') {
		return true;
	}
	if ($op == 'x913') {
		return true;
	}
	if ($op == 'x914') {
		return true;
	}
	if ($op == 'x915') {
		return true;
	}
	if ($op == 'x916') {
		return true;
	}
	if ($op == 'x917') {
		return true;
	}
	if ($op == 'x918') {
		return true;
	}
	if ($op == 'x919') {
		return true;
	}
	if ($op == 'x920') {
		return true;
	}
	if ($op == 'x921') {
		return true;
	}
	if ($op == 'x922') {
		return true;
	}
	if ($op == 'x923') {
		return true;
	}
	if ($op == 'x924') {
		return true;
	}
	if ($op == 'x925') {
		return true;
	}
	if ($op == 'x926') {
		return true;
	}
	if ($op == 'x927') {
		return true;
	}
	if ($op == 'x928') {
		return true;
	}
	if ($op == 'x929') {
		return true;
	}
	if ($op == 'x930') {
		return true;
	}
	if ($op == 'x931') {
		return true;
	}
	if ($op == 'x932') {
		return true;
	}
	if ($op == 'x933') {
		return true;
	}
	if ($op == 'x934') {
		return true;
	}
	if ($op == 'x935') {
		return true;
	}
	if ($op == 'x936') {
		return true;
	}
	if ($op == 'x937') {
		return true;
	}
	if ($op == 'x938') {
		return true;
	}
	if ($op == 'x939') {
		return true;
	}
	if ($op == 'x940') {
		return true;
	}
	if ($op == 'x941') {
		return true;
	}
	if ($op == 'x942') {
		return true;
	}
	if ($op == 'x943') {
		return true;
	}
	if ($op == 'x944') {
		return true;
	}
	if ($op == 'x945') {
		return true;
	}
	if ($op == 'x946') {
		return true;
	}
	if ($op == 'x947') {
		return true;
	}
	if ($op == 'x948') {
		return true;
	}
	if ($op == 'x949') {
		return true;
	}
	if ($op == 'x950') {
		return true;
	}
	if ($op == 'x951') {
		return true;
	}
	if ($op == 'x952') {
		return true;
	}
	if ($op == 'x953') {
		return true;
	}
	if ($op == 'x954') {
		return true;
	}
	if ($op == 'x955') {
		return true;
	}
	if ($op == 'x956') {
		return true;
	}
	if ($op == 'x957') {
		return true;
	}
	if ($op == 'x958') {
		return true;
	}
	if ($op == 'x959') {
		return true;
	}
	if ($op == 'x960') {
		return true;
	}
	if ($op == 'x961') {
		return true;
	}
	if ($op == 'x962') {
		return true;
	}
	if ($op == 'x963') {
		return true;
	}
	if ($op == 'x964') {
		return true;
	}
	if ($op == 'x965') {
		return true;
	}
	if ($op == 'x966') {
		return true;
	}
	if ($op == 'x967') {
		return true;
	}
	if ($op == 'x968') {
		return true;
	}
	if ($op == 'x969') {
		return true;
	}
	if ($op == 'x970') {
		return true;
	}
	if ($op == 'x971') {
		return true;
	}
	if ($op == 'x972') {
		return true;
	}
	if ($op == 'x973') {
		return true;
	}
	if ($op == 'x974') {
		return true;
	}
	if ($op == 'x975') {
		return true;
	}
	if ($op == 'x976') {
		return true;
	}
	if ($op == 'x977') {
		return true;
	}
	if ($op == 'x978') {
		return true;
	}
	if ($op == 'x979') {
		return true;
	}
	if ($op == 'x980') {
		return true;
	}
	if ($op == 'x981') {
		return true;
	}
	if ($op == 'x982') {
		return true;
	}
	if ($op == 'x983') {
		return true;
	}
	if ($op == 'x984') {
		return true;
	}
	if ($op == 'x985') {
		return true;
	}
	if ($op == 'x986') {
		return true;
	}
	if ($op == 'x987') {
		return true;
	}
	if ($op == 'x988') {
		return true;
	}
	if ($op == 'x989') {
		return true;
	}
	if ($op == 'x990') {
		return true;
	}
	if ($op == 'x991') {
		return true;
	}
	if ($op == 'x992') {
		return true;
	}
	if ($op == 'x993') {
		return true;
	}
	if ($op == 'x994') {
		return true;
	}
	if ($op == 'x995') {
		return true;
	}
	if ($op == 'x996') {
		return true;
	}
	if ($op == 'x997') {
		return true;
	}
	if ($op == 'x998') {
		return true;
	}
	if ($op == 'x999') {
		return true;
	}
	return false;
}
