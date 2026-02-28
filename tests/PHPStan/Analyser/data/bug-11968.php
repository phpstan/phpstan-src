<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug11968;

interface Renderable
{
	public function render(): string;
}

enum LargeEnum implements Renderable
{
	case Case0;
	case Case1;
	case Case2;
	case Case3;
	case Case4;
	case Case5;
	case Case6;
	case Case7;
	case Case8;
	case Case9;
	case Case10;
	case Case11;
	case Case12;
	case Case13;
	case Case14;
	case Case15;
	case Case16;
	case Case17;
	case Case18;
	case Case19;
	case Case20;
	case Case21;
	case Case22;
	case Case23;
	case Case24;
	case Case25;
	case Case26;
	case Case27;
	case Case28;
	case Case29;
	case Case30;
	case Case31;
	case Case32;
	case Case33;
	case Case34;
	case Case35;
	case Case36;
	case Case37;
	case Case38;
	case Case39;
	case Case40;
	case Case41;
	case Case42;
	case Case43;
	case Case44;
	case Case45;
	case Case46;
	case Case47;
	case Case48;
	case Case49;
	case Case50;
	case Case51;
	case Case52;
	case Case53;
	case Case54;
	case Case55;
	case Case56;
	case Case57;
	case Case58;
	case Case59;
	case Case60;
	case Case61;
	case Case62;
	case Case63;
	case Case64;
	case Case65;
	case Case66;
	case Case67;
	case Case68;
	case Case69;
	case Case70;
	case Case71;
	case Case72;
	case Case73;
	case Case74;
	case Case75;
	case Case76;
	case Case77;
	case Case78;
	case Case79;
	case Case80;
	case Case81;
	case Case82;
	case Case83;
	case Case84;
	case Case85;
	case Case86;
	case Case87;
	case Case88;
	case Case89;
	case Case90;
	case Case91;
	case Case92;
	case Case93;
	case Case94;
	case Case95;
	case Case96;
	case Case97;
	case Case98;
	case Case99;
	case Case100;
	case Case101;
	case Case102;
	case Case103;
	case Case104;
	case Case105;
	case Case106;
	case Case107;
	case Case108;
	case Case109;
	case Case110;
	case Case111;
	case Case112;
	case Case113;
	case Case114;
	case Case115;
	case Case116;
	case Case117;
	case Case118;
	case Case119;
	case Case120;
	case Case121;
	case Case122;
	case Case123;
	case Case124;
	case Case125;
	case Case126;
	case Case127;
	case Case128;
	case Case129;
	case Case130;
	case Case131;
	case Case132;
	case Case133;
	case Case134;
	case Case135;
	case Case136;
	case Case137;
	case Case138;
	case Case139;
	case Case140;
	case Case141;
	case Case142;
	case Case143;
	case Case144;
	case Case145;
	case Case146;
	case Case147;
	case Case148;
	case Case149;
	case Case150;
	case Case151;
	case Case152;
	case Case153;
	case Case154;
	case Case155;
	case Case156;
	case Case157;
	case Case158;
	case Case159;
	case Case160;
	case Case161;
	case Case162;
	case Case163;
	case Case164;
	case Case165;
	case Case166;
	case Case167;
	case Case168;
	case Case169;
	case Case170;
	case Case171;
	case Case172;
	case Case173;
	case Case174;
	case Case175;
	case Case176;
	case Case177;
	case Case178;
	case Case179;
	case Case180;
	case Case181;
	case Case182;
	case Case183;
	case Case184;
	case Case185;
	case Case186;
	case Case187;
	case Case188;
	case Case189;
	case Case190;
	case Case191;
	case Case192;
	case Case193;
	case Case194;
	case Case195;
	case Case196;
	case Case197;
	case Case198;
	case Case199;
	case Case200;
	case Case201;
	case Case202;
	case Case203;
	case Case204;
	case Case205;
	case Case206;
	case Case207;
	case Case208;
	case Case209;
	case Case210;
	case Case211;
	case Case212;
	case Case213;
	case Case214;
	case Case215;
	case Case216;
	case Case217;
	case Case218;
	case Case219;
	case Case220;
	case Case221;
	case Case222;
	case Case223;
	case Case224;
	case Case225;
	case Case226;
	case Case227;
	case Case228;
	case Case229;
	case Case230;
	case Case231;
	case Case232;
	case Case233;
	case Case234;
	case Case235;
	case Case236;
	case Case237;
	case Case238;
	case Case239;
	case Case240;
	case Case241;
	case Case242;
	case Case243;
	case Case244;
	case Case245;
	case Case246;
	case Case247;
	case Case248;
	case Case249;
	case Case250;
	case Case251;
	case Case252;
	case Case253;
	case Case254;
	case Case255;
	case Case256;
	case Case257;
	case Case258;
	case Case259;
	case Case260;
	case Case261;
	case Case262;
	case Case263;
	case Case264;
	case Case265;
	case Case266;
	case Case267;
	case Case268;
	case Case269;
	case Case270;
	case Case271;
	case Case272;
	case Case273;
	case Case274;
	case Case275;
	case Case276;
	case Case277;
	case Case278;
	case Case279;
	case Case280;
	case Case281;
	case Case282;
	case Case283;
	case Case284;
	case Case285;
	case Case286;
	case Case287;
	case Case288;
	case Case289;
	case Case290;
	case Case291;
	case Case292;
	case Case293;
	case Case294;
	case Case295;
	case Case296;
	case Case297;
	case Case298;
	case Case299;
	case Case300;
	case Case301;
	case Case302;
	case Case303;
	case Case304;
	case Case305;
	case Case306;
	case Case307;
	case Case308;
	case Case309;
	case Case310;
	case Case311;
	case Case312;
	case Case313;
	case Case314;
	case Case315;
	case Case316;
	case Case317;
	case Case318;
	case Case319;
	case Case320;
	case Case321;
	case Case322;
	case Case323;
	case Case324;
	case Case325;
	case Case326;
	case Case327;
	case Case328;
	case Case329;
	case Case330;
	case Case331;
	case Case332;
	case Case333;
	case Case334;
	case Case335;
	case Case336;
	case Case337;
	case Case338;
	case Case339;
	case Case340;
	case Case341;
	case Case342;
	case Case343;
	case Case344;
	case Case345;
	case Case346;
	case Case347;
	case Case348;
	case Case349;
	case Case350;
	case Case351;
	case Case352;
	case Case353;
	case Case354;
	case Case355;
	case Case356;
	case Case357;
	case Case358;
	case Case359;
	case Case360;
	case Case361;
	case Case362;
	case Case363;
	case Case364;
	case Case365;
	case Case366;
	case Case367;
	case Case368;
	case Case369;
	case Case370;
	case Case371;
	case Case372;
	case Case373;
	case Case374;
	case Case375;
	case Case376;
	case Case377;
	case Case378;
	case Case379;
	case Case380;
	case Case381;
	case Case382;
	case Case383;
	case Case384;
	case Case385;
	case Case386;
	case Case387;
	case Case388;
	case Case389;
	case Case390;
	case Case391;
	case Case392;
	case Case393;
	case Case394;
	case Case395;
	case Case396;
	case Case397;
	case Case398;
	case Case399;
	case Case400;
	case Case401;
	case Case402;
	case Case403;
	case Case404;
	case Case405;
	case Case406;
	case Case407;
	case Case408;
	case Case409;
	case Case410;
	case Case411;
	case Case412;
	case Case413;
	case Case414;
	case Case415;
	case Case416;
	case Case417;
	case Case418;
	case Case419;
	case Case420;
	case Case421;
	case Case422;
	case Case423;
	case Case424;
	case Case425;
	case Case426;
	case Case427;
	case Case428;
	case Case429;
	case Case430;
	case Case431;
	case Case432;
	case Case433;
	case Case434;
	case Case435;
	case Case436;
	case Case437;
	case Case438;
	case Case439;
	case Case440;
	case Case441;
	case Case442;
	case Case443;
	case Case444;
	case Case445;
	case Case446;
	case Case447;
	case Case448;
	case Case449;
	case Case450;
	case Case451;
	case Case452;
	case Case453;
	case Case454;
	case Case455;
	case Case456;
	case Case457;
	case Case458;
	case Case459;
	case Case460;
	case Case461;
	case Case462;
	case Case463;
	case Case464;
	case Case465;
	case Case466;
	case Case467;
	case Case468;
	case Case469;
	case Case470;
	case Case471;
	case Case472;
	case Case473;
	case Case474;
	case Case475;
	case Case476;
	case Case477;
	case Case478;
	case Case479;
	case Case480;
	case Case481;
	case Case482;
	case Case483;
	case Case484;
	case Case485;
	case Case486;
	case Case487;
	case Case488;
	case Case489;
	case Case490;
	case Case491;
	case Case492;
	case Case493;
	case Case494;
	case Case495;
	case Case496;
	case Case497;
	case Case498;
	case Case499;

	public function data(): string
	{
		return match($this) {
			self::Case0 => "value0",
			self::Case1 => "value1",
			self::Case2 => "value2",
			self::Case3 => "value3",
			self::Case4 => "value4",
			self::Case5 => "value5",
			self::Case6 => "value6",
			self::Case7 => "value7",
			self::Case8 => "value8",
			self::Case9 => "value9",
			self::Case10 => "value10",
			self::Case11 => "value11",
			self::Case12 => "value12",
			self::Case13 => "value13",
			self::Case14 => "value14",
			self::Case15 => "value15",
			self::Case16 => "value16",
			self::Case17 => "value17",
			self::Case18 => "value18",
			self::Case19 => "value19",
			self::Case20 => "value20",
			self::Case21 => "value21",
			self::Case22 => "value22",
			self::Case23 => "value23",
			self::Case24 => "value24",
			self::Case25 => "value25",
			self::Case26 => "value26",
			self::Case27 => "value27",
			self::Case28 => "value28",
			self::Case29 => "value29",
			self::Case30 => "value30",
			self::Case31 => "value31",
			self::Case32 => "value32",
			self::Case33 => "value33",
			self::Case34 => "value34",
			self::Case35 => "value35",
			self::Case36 => "value36",
			self::Case37 => "value37",
			self::Case38 => "value38",
			self::Case39 => "value39",
			self::Case40 => "value40",
			self::Case41 => "value41",
			self::Case42 => "value42",
			self::Case43 => "value43",
			self::Case44 => "value44",
			self::Case45 => "value45",
			self::Case46 => "value46",
			self::Case47 => "value47",
			self::Case48 => "value48",
			self::Case49 => "value49",
			self::Case50 => "value50",
			self::Case51 => "value51",
			self::Case52 => "value52",
			self::Case53 => "value53",
			self::Case54 => "value54",
			self::Case55 => "value55",
			self::Case56 => "value56",
			self::Case57 => "value57",
			self::Case58 => "value58",
			self::Case59 => "value59",
			self::Case60 => "value60",
			self::Case61 => "value61",
			self::Case62 => "value62",
			self::Case63 => "value63",
			self::Case64 => "value64",
			self::Case65 => "value65",
			self::Case66 => "value66",
			self::Case67 => "value67",
			self::Case68 => "value68",
			self::Case69 => "value69",
			self::Case70 => "value70",
			self::Case71 => "value71",
			self::Case72 => "value72",
			self::Case73 => "value73",
			self::Case74 => "value74",
			self::Case75 => "value75",
			self::Case76 => "value76",
			self::Case77 => "value77",
			self::Case78 => "value78",
			self::Case79 => "value79",
			self::Case80 => "value80",
			self::Case81 => "value81",
			self::Case82 => "value82",
			self::Case83 => "value83",
			self::Case84 => "value84",
			self::Case85 => "value85",
			self::Case86 => "value86",
			self::Case87 => "value87",
			self::Case88 => "value88",
			self::Case89 => "value89",
			self::Case90 => "value90",
			self::Case91 => "value91",
			self::Case92 => "value92",
			self::Case93 => "value93",
			self::Case94 => "value94",
			self::Case95 => "value95",
			self::Case96 => "value96",
			self::Case97 => "value97",
			self::Case98 => "value98",
			self::Case99 => "value99",
			self::Case100 => "value100",
			self::Case101 => "value101",
			self::Case102 => "value102",
			self::Case103 => "value103",
			self::Case104 => "value104",
			self::Case105 => "value105",
			self::Case106 => "value106",
			self::Case107 => "value107",
			self::Case108 => "value108",
			self::Case109 => "value109",
			self::Case110 => "value110",
			self::Case111 => "value111",
			self::Case112 => "value112",
			self::Case113 => "value113",
			self::Case114 => "value114",
			self::Case115 => "value115",
			self::Case116 => "value116",
			self::Case117 => "value117",
			self::Case118 => "value118",
			self::Case119 => "value119",
			self::Case120 => "value120",
			self::Case121 => "value121",
			self::Case122 => "value122",
			self::Case123 => "value123",
			self::Case124 => "value124",
			self::Case125 => "value125",
			self::Case126 => "value126",
			self::Case127 => "value127",
			self::Case128 => "value128",
			self::Case129 => "value129",
			self::Case130 => "value130",
			self::Case131 => "value131",
			self::Case132 => "value132",
			self::Case133 => "value133",
			self::Case134 => "value134",
			self::Case135 => "value135",
			self::Case136 => "value136",
			self::Case137 => "value137",
			self::Case138 => "value138",
			self::Case139 => "value139",
			self::Case140 => "value140",
			self::Case141 => "value141",
			self::Case142 => "value142",
			self::Case143 => "value143",
			self::Case144 => "value144",
			self::Case145 => "value145",
			self::Case146 => "value146",
			self::Case147 => "value147",
			self::Case148 => "value148",
			self::Case149 => "value149",
			self::Case150 => "value150",
			self::Case151 => "value151",
			self::Case152 => "value152",
			self::Case153 => "value153",
			self::Case154 => "value154",
			self::Case155 => "value155",
			self::Case156 => "value156",
			self::Case157 => "value157",
			self::Case158 => "value158",
			self::Case159 => "value159",
			self::Case160 => "value160",
			self::Case161 => "value161",
			self::Case162 => "value162",
			self::Case163 => "value163",
			self::Case164 => "value164",
			self::Case165 => "value165",
			self::Case166 => "value166",
			self::Case167 => "value167",
			self::Case168 => "value168",
			self::Case169 => "value169",
			self::Case170 => "value170",
			self::Case171 => "value171",
			self::Case172 => "value172",
			self::Case173 => "value173",
			self::Case174 => "value174",
			self::Case175 => "value175",
			self::Case176 => "value176",
			self::Case177 => "value177",
			self::Case178 => "value178",
			self::Case179 => "value179",
			self::Case180 => "value180",
			self::Case181 => "value181",
			self::Case182 => "value182",
			self::Case183 => "value183",
			self::Case184 => "value184",
			self::Case185 => "value185",
			self::Case186 => "value186",
			self::Case187 => "value187",
			self::Case188 => "value188",
			self::Case189 => "value189",
			self::Case190 => "value190",
			self::Case191 => "value191",
			self::Case192 => "value192",
			self::Case193 => "value193",
			self::Case194 => "value194",
			self::Case195 => "value195",
			self::Case196 => "value196",
			self::Case197 => "value197",
			self::Case198 => "value198",
			self::Case199 => "value199",
			self::Case200 => "value200",
			self::Case201 => "value201",
			self::Case202 => "value202",
			self::Case203 => "value203",
			self::Case204 => "value204",
			self::Case205 => "value205",
			self::Case206 => "value206",
			self::Case207 => "value207",
			self::Case208 => "value208",
			self::Case209 => "value209",
			self::Case210 => "value210",
			self::Case211 => "value211",
			self::Case212 => "value212",
			self::Case213 => "value213",
			self::Case214 => "value214",
			self::Case215 => "value215",
			self::Case216 => "value216",
			self::Case217 => "value217",
			self::Case218 => "value218",
			self::Case219 => "value219",
			self::Case220 => "value220",
			self::Case221 => "value221",
			self::Case222 => "value222",
			self::Case223 => "value223",
			self::Case224 => "value224",
			self::Case225 => "value225",
			self::Case226 => "value226",
			self::Case227 => "value227",
			self::Case228 => "value228",
			self::Case229 => "value229",
			self::Case230 => "value230",
			self::Case231 => "value231",
			self::Case232 => "value232",
			self::Case233 => "value233",
			self::Case234 => "value234",
			self::Case235 => "value235",
			self::Case236 => "value236",
			self::Case237 => "value237",
			self::Case238 => "value238",
			self::Case239 => "value239",
			self::Case240 => "value240",
			self::Case241 => "value241",
			self::Case242 => "value242",
			self::Case243 => "value243",
			self::Case244 => "value244",
			self::Case245 => "value245",
			self::Case246 => "value246",
			self::Case247 => "value247",
			self::Case248 => "value248",
			self::Case249 => "value249",
			self::Case250 => "value250",
			self::Case251 => "value251",
			self::Case252 => "value252",
			self::Case253 => "value253",
			self::Case254 => "value254",
			self::Case255 => "value255",
			self::Case256 => "value256",
			self::Case257 => "value257",
			self::Case258 => "value258",
			self::Case259 => "value259",
			self::Case260 => "value260",
			self::Case261 => "value261",
			self::Case262 => "value262",
			self::Case263 => "value263",
			self::Case264 => "value264",
			self::Case265 => "value265",
			self::Case266 => "value266",
			self::Case267 => "value267",
			self::Case268 => "value268",
			self::Case269 => "value269",
			self::Case270 => "value270",
			self::Case271 => "value271",
			self::Case272 => "value272",
			self::Case273 => "value273",
			self::Case274 => "value274",
			self::Case275 => "value275",
			self::Case276 => "value276",
			self::Case277 => "value277",
			self::Case278 => "value278",
			self::Case279 => "value279",
			self::Case280 => "value280",
			self::Case281 => "value281",
			self::Case282 => "value282",
			self::Case283 => "value283",
			self::Case284 => "value284",
			self::Case285 => "value285",
			self::Case286 => "value286",
			self::Case287 => "value287",
			self::Case288 => "value288",
			self::Case289 => "value289",
			self::Case290 => "value290",
			self::Case291 => "value291",
			self::Case292 => "value292",
			self::Case293 => "value293",
			self::Case294 => "value294",
			self::Case295 => "value295",
			self::Case296 => "value296",
			self::Case297 => "value297",
			self::Case298 => "value298",
			self::Case299 => "value299",
			self::Case300 => "value300",
			self::Case301 => "value301",
			self::Case302 => "value302",
			self::Case303 => "value303",
			self::Case304 => "value304",
			self::Case305 => "value305",
			self::Case306 => "value306",
			self::Case307 => "value307",
			self::Case308 => "value308",
			self::Case309 => "value309",
			self::Case310 => "value310",
			self::Case311 => "value311",
			self::Case312 => "value312",
			self::Case313 => "value313",
			self::Case314 => "value314",
			self::Case315 => "value315",
			self::Case316 => "value316",
			self::Case317 => "value317",
			self::Case318 => "value318",
			self::Case319 => "value319",
			self::Case320 => "value320",
			self::Case321 => "value321",
			self::Case322 => "value322",
			self::Case323 => "value323",
			self::Case324 => "value324",
			self::Case325 => "value325",
			self::Case326 => "value326",
			self::Case327 => "value327",
			self::Case328 => "value328",
			self::Case329 => "value329",
			self::Case330 => "value330",
			self::Case331 => "value331",
			self::Case332 => "value332",
			self::Case333 => "value333",
			self::Case334 => "value334",
			self::Case335 => "value335",
			self::Case336 => "value336",
			self::Case337 => "value337",
			self::Case338 => "value338",
			self::Case339 => "value339",
			self::Case340 => "value340",
			self::Case341 => "value341",
			self::Case342 => "value342",
			self::Case343 => "value343",
			self::Case344 => "value344",
			self::Case345 => "value345",
			self::Case346 => "value346",
			self::Case347 => "value347",
			self::Case348 => "value348",
			self::Case349 => "value349",
			self::Case350 => "value350",
			self::Case351 => "value351",
			self::Case352 => "value352",
			self::Case353 => "value353",
			self::Case354 => "value354",
			self::Case355 => "value355",
			self::Case356 => "value356",
			self::Case357 => "value357",
			self::Case358 => "value358",
			self::Case359 => "value359",
			self::Case360 => "value360",
			self::Case361 => "value361",
			self::Case362 => "value362",
			self::Case363 => "value363",
			self::Case364 => "value364",
			self::Case365 => "value365",
			self::Case366 => "value366",
			self::Case367 => "value367",
			self::Case368 => "value368",
			self::Case369 => "value369",
			self::Case370 => "value370",
			self::Case371 => "value371",
			self::Case372 => "value372",
			self::Case373 => "value373",
			self::Case374 => "value374",
			self::Case375 => "value375",
			self::Case376 => "value376",
			self::Case377 => "value377",
			self::Case378 => "value378",
			self::Case379 => "value379",
			self::Case380 => "value380",
			self::Case381 => "value381",
			self::Case382 => "value382",
			self::Case383 => "value383",
			self::Case384 => "value384",
			self::Case385 => "value385",
			self::Case386 => "value386",
			self::Case387 => "value387",
			self::Case388 => "value388",
			self::Case389 => "value389",
			self::Case390 => "value390",
			self::Case391 => "value391",
			self::Case392 => "value392",
			self::Case393 => "value393",
			self::Case394 => "value394",
			self::Case395 => "value395",
			self::Case396 => "value396",
			self::Case397 => "value397",
			self::Case398 => "value398",
			self::Case399 => "value399",
			self::Case400 => "value400",
			self::Case401 => "value401",
			self::Case402 => "value402",
			self::Case403 => "value403",
			self::Case404 => "value404",
			self::Case405 => "value405",
			self::Case406 => "value406",
			self::Case407 => "value407",
			self::Case408 => "value408",
			self::Case409 => "value409",
			self::Case410 => "value410",
			self::Case411 => "value411",
			self::Case412 => "value412",
			self::Case413 => "value413",
			self::Case414 => "value414",
			self::Case415 => "value415",
			self::Case416 => "value416",
			self::Case417 => "value417",
			self::Case418 => "value418",
			self::Case419 => "value419",
			self::Case420 => "value420",
			self::Case421 => "value421",
			self::Case422 => "value422",
			self::Case423 => "value423",
			self::Case424 => "value424",
			self::Case425 => "value425",
			self::Case426 => "value426",
			self::Case427 => "value427",
			self::Case428 => "value428",
			self::Case429 => "value429",
			self::Case430 => "value430",
			self::Case431 => "value431",
			self::Case432 => "value432",
			self::Case433 => "value433",
			self::Case434 => "value434",
			self::Case435 => "value435",
			self::Case436 => "value436",
			self::Case437 => "value437",
			self::Case438 => "value438",
			self::Case439 => "value439",
			self::Case440 => "value440",
			self::Case441 => "value441",
			self::Case442 => "value442",
			self::Case443 => "value443",
			self::Case444 => "value444",
			self::Case445 => "value445",
			self::Case446 => "value446",
			self::Case447 => "value447",
			self::Case448 => "value448",
			self::Case449 => "value449",
			self::Case450 => "value450",
			self::Case451 => "value451",
			self::Case452 => "value452",
			self::Case453 => "value453",
			self::Case454 => "value454",
			self::Case455 => "value455",
			self::Case456 => "value456",
			self::Case457 => "value457",
			self::Case458 => "value458",
			self::Case459 => "value459",
			self::Case460 => "value460",
			self::Case461 => "value461",
			self::Case462 => "value462",
			self::Case463 => "value463",
			self::Case464 => "value464",
			self::Case465 => "value465",
			self::Case466 => "value466",
			self::Case467 => "value467",
			self::Case468 => "value468",
			self::Case469 => "value469",
			self::Case470 => "value470",
			self::Case471 => "value471",
			self::Case472 => "value472",
			self::Case473 => "value473",
			self::Case474 => "value474",
			self::Case475 => "value475",
			self::Case476 => "value476",
			self::Case477 => "value477",
			self::Case478 => "value478",
			self::Case479 => "value479",
			self::Case480 => "value480",
			self::Case481 => "value481",
			self::Case482 => "value482",
			self::Case483 => "value483",
			self::Case484 => "value484",
			self::Case485 => "value485",
			self::Case486 => "value486",
			self::Case487 => "value487",
			self::Case488 => "value488",
			self::Case489 => "value489",
			self::Case490 => "value490",
			self::Case491 => "value491",
			self::Case492 => "value492",
			self::Case493 => "value493",
			self::Case494 => "value494",
			self::Case495 => "value495",
			self::Case496 => "value496",
			self::Case497 => "value497",
			self::Case498 => "value498",
			self::Case499 => "value499",
		};
	}

	public function render(): string
	{
		return $this->data();
	}
}

function processRenderable(Renderable $r): string
{
	if ($r instanceof LargeEnum) {
		return $r->data();
	}
	return $r->render();
}

/** @param list<LargeEnum> $enums */
function checkEnum(LargeEnum $e, array $enums): bool
{
	return in_array($e, $enums, true);
}

