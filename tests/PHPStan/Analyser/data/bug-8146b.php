<?php declare(strict_types=1);

namespace Bug8146bNoError;

class X{}

class LocationFixtures
{
	/** @return array<non-empty-string, array<non-empty-string, array{constituencies: non-empty-list<non-empty-string>, coordinates: array{lat: float, lng: float}}>> */
	public function getData(): array
	{
		return [
			'Bács-Kiskun' => [
				'Ágasegyháza' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.8386043, 'lng' => 19.4502899],
				],
				'Akasztó' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.6898175, 'lng' => 19.205086],
				],
				'Apostag' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.8812652, 'lng' => 18.9648478],
				],
				'Bácsalmás' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.1250396, 'lng' => 19.3357509],
				],
				'Bácsbokod' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.1234737, 'lng' => 19.155708],
				],
				'Bácsborsód' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.0989373, 'lng' => 19.1566725],
				],
				'Bácsszentgyörgy' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 45.9746039, 'lng' => 19.0398066],
				],
				'Bácsszőlős' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.1352003, 'lng' => 19.4215997],
				],
				'Baja' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.1817951, 'lng' => 18.9543051],
				],
				'Ballószög' => [
					'constituencies' => ['Bács-Kiskun 2.'],
					'coordinates' => ['lat' => 46.8619947, 'lng' => 19.5726144],
				],
				'Balotaszállás' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.3512041, 'lng' => 19.5403558],
				],
				'Bátmonostor' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.1057304, 'lng' => 18.9238311],
				],
				'Bátya' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.4891741, 'lng' => 18.9579127],
				],
				'Bócsa' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.6113504, 'lng' => 19.4826419],
				],
				'Borota' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.2657107, 'lng' => 19.2233598],
				],
				'Bugac' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.6883076, 'lng' => 19.6833655],
				],
				'Bugacpusztaháza' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.7022143, 'lng' => 19.6356538],
				],
				'Császártöltés' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.4222869, 'lng' => 19.1815532],
				],
				'Csátalja' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.0363238, 'lng' => 18.9469006],
				],
				'Csávoly' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.1912599, 'lng' => 19.1451178],
				],
				'Csengőd' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.71532, 'lng' => 19.2660933],
				],
				'Csikéria' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.121679, 'lng' => 19.473777],
				],
				'Csólyospálos' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.4180837, 'lng' => 19.8402638],
				],
				'Dávod' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 45.9976187, 'lng' => 18.9176479],
				],
				'Drágszél' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.4653889, 'lng' => 19.0382659],
				],
				'Dunaegyháza' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.8383215, 'lng' => 18.9605216],
				],
				'Dunafalva' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.081562, 'lng' => 18.7782526],
				],
				'Dunapataj' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.6422106, 'lng' => 18.9989393],
				],
				'Dunaszentbenedek' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.593856, 'lng' => 18.8935322],
				],
				'Dunatetétlen' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.7578624, 'lng' => 19.0932563],
				],
				'Dunavecse' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.9133047, 'lng' => 18.9731873],
				],
				'Dusnok' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.3893659, 'lng' => 18.960842],
				],
				'Érsekcsanád' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.2541554, 'lng' => 18.9835293],
				],
				'Érsekhalma' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.3472701, 'lng' => 19.1247379],
				],
				'Fajsz' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.4157936, 'lng' => 18.9191954],
				],
				'Felsőlajos' => [
					'constituencies' => ['Bács-Kiskun 1.'],
					'coordinates' => ['lat' => 47.0647473, 'lng' => 19.4944348],
				],
				'Felsőszentiván' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.1966179, 'lng' => 19.1873616],
				],
				'Foktő' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.5268759, 'lng' => 18.9196874],
				],
				'Fülöpháza' => [
					'constituencies' => ['Bács-Kiskun 1.'],
					'coordinates' => ['lat' => 46.8914016, 'lng' => 19.4432493],
				],
				'Fülöpjakab' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.742058, 'lng' => 19.7227232],
				],
				'Fülöpszállás' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.8195701, 'lng' => 19.2372115],
				],
				'Gara' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.0349999, 'lng' => 19.0393411],
				],
				'Gátér' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.680435, 'lng' => 19.9596412],
				],
				'Géderlak' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.6072512, 'lng' => 18.9135762],
				],
				'Hajós' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.4001409, 'lng' => 19.1193255],
				],
				'Harkakötöny' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.4634053, 'lng' => 19.6069951],
				],
				'Harta' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.6960997, 'lng' => 19.0328195],
				],
				'Helvécia' => [
					'constituencies' => ['Bács-Kiskun 2.'],
					'coordinates' => ['lat' => 46.8360977, 'lng' => 19.620438],
				],
				'Hercegszántó' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 45.9482057, 'lng' => 18.9389127],
				],
				'Homokmégy' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.4892762, 'lng' => 19.0730421],
				],
				'Imrehegy' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.4867668, 'lng' => 19.3056372],
				],
				'Izsák' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.8020009, 'lng' => 19.3546225],
				],
				'Jakabszállás' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.7602785, 'lng' => 19.6055301],
				],
				'Jánoshalma' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.2974544, 'lng' => 19.3250656],
				],
				'Jászszentlászló' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.5672659, 'lng' => 19.7590541],
				],
				'Kalocsa' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.5281229, 'lng' => 18.9840376],
				],
				'Kaskantyú' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.6711891, 'lng' => 19.3895391],
				],
				'Katymár' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.0344636, 'lng' => 19.2087609],
				],
				'Kecel' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.5243135, 'lng' => 19.2451963],
				],
				'Kecskemét' => [
					'constituencies' => ['Bács-Kiskun 2.', 'Bács-Kiskun 1.'],
					'coordinates' => ['lat' => 46.8963711, 'lng' => 19.6896861],
				],
				'Kelebia' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.1958608, 'lng' => 19.6066291],
				],
				'Kéleshalom' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.3641795, 'lng' => 19.2831241],
				],
				'Kerekegyháza' => [
					'constituencies' => ['Bács-Kiskun 1.'],
					'coordinates' => ['lat' => 46.9385747, 'lng' => 19.4770208],
				],
				'Kiskőrös' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.6224967, 'lng' => 19.2874568],
				],
				'Kiskunfélegyháza' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.7112802, 'lng' => 19.8515196],
				],
				'Kiskunhalas' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.4354409, 'lng' => 19.4834284],
				],
				'Kiskunmajsa' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.4904848, 'lng' => 19.7366569],
				],
				'Kisszállás' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.2791272, 'lng' => 19.4908079],
				],
				'Kömpöc' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.4640167, 'lng' => 19.8665681],
				],
				'Kunadacs' => [
					'constituencies' => ['Bács-Kiskun 1.'],
					'coordinates' => ['lat' => 46.956503, 'lng' => 19.2880496],
				],
				'Kunbaja' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.0848391, 'lng' => 19.4213713],
				],
				'Kunbaracs' => [
					'constituencies' => ['Bács-Kiskun 1.'],
					'coordinates' => ['lat' => 46.9891493, 'lng' => 19.3999584],
				],
				'Kunfehértó' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.362671, 'lng' => 19.4141949],
				],
				'Kunpeszér' => [
					'constituencies' => ['Bács-Kiskun 1.'],
					'coordinates' => ['lat' => 47.0611502, 'lng' => 19.2753764],
				],
				'Kunszállás' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.7627801, 'lng' => 19.7532925],
				],
				'Kunszentmiklós' => [
					'constituencies' => ['Bács-Kiskun 1.'],
					'coordinates' => ['lat' => 47.0244473, 'lng' => 19.1235997],
				],
				'Ladánybene' => [
					'constituencies' => ['Bács-Kiskun 1.'],
					'coordinates' => ['lat' => 47.0344239, 'lng' => 19.456807],
				],
				'Lajosmizse' => [
					'constituencies' => ['Bács-Kiskun 1.'],
					'coordinates' => ['lat' => 47.0248225, 'lng' => 19.5559232],
				],
				'Lakitelek' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.8710339, 'lng' => 19.9930216],
				],
				'Madaras' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.0554833, 'lng' => 19.2633403],
				],
				'Mátételke' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.1614675, 'lng' => 19.2802263],
				],
				'Mélykút' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.2132295, 'lng' => 19.3814176],
				],
				'Miske' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.4434918, 'lng' => 19.0315752],
				],
				'Móricgát' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.6233704, 'lng' => 19.6885382],
				],
				'Nagybaracska' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.0444015, 'lng' => 18.9048387],
				],
				'Nemesnádudvar' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.3348444, 'lng' => 19.0542114],
				],
				'Nyárlőrinc' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.8611255, 'lng' => 19.8773125],
				],
				'Ordas' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.6364524, 'lng' => 18.9504602],
				],
				'Öregcsertő' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.515272, 'lng' => 19.1090595],
				],
				'Orgovány' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.7497582, 'lng' => 19.4746024],
				],
				'Páhi' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.7136232, 'lng' => 19.3856937],
				],
				'Pálmonostora' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.6265115, 'lng' => 19.9425525],
				],
				'Petőfiszállás' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.6243457, 'lng' => 19.8596537],
				],
				'Pirtó' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.5139604, 'lng' => 19.4301958],
				],
				'Rém' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.2470804, 'lng' => 19.1416684],
				],
				'Solt' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.8021967, 'lng' => 19.0108147],
				],
				'Soltszentimre' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.769786, 'lng' => 19.2840433],
				],
				'Soltvadkert' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.5789287, 'lng' => 19.3938029],
				],
				'Sükösd' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.2832039, 'lng' => 18.9942907],
				],
				'Szabadszállás' => [
					'constituencies' => ['Bács-Kiskun 1.'],
					'coordinates' => ['lat' => 46.8763076, 'lng' => 19.2232539],
				],
				'Szakmár' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.5543652, 'lng' => 19.0742847],
				],
				'Szalkszentmárton' => [
					'constituencies' => ['Bács-Kiskun 1.'],
					'coordinates' => ['lat' => 46.9754928, 'lng' => 19.0171018],
				],
				'Szank' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.5557842, 'lng' => 19.6668956],
				],
				'Szentkirály' => [
					'constituencies' => ['Bács-Kiskun 2.'],
					'coordinates' => ['lat' => 46.9169398, 'lng' => 19.9175371],
				],
				'Szeremle' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.1436504, 'lng' => 18.8810207],
				],
				'Tabdi' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.6818019, 'lng' => 19.3042672],
				],
				'Tass' => [
					'constituencies' => ['Bács-Kiskun 1.'],
					'coordinates' => ['lat' => 47.0184485, 'lng' => 19.0281253],
				],
				'Tataháza' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.173167, 'lng' => 19.3024716],
				],
				'Tázlár' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.5509533, 'lng' => 19.5159844],
				],
				'Tiszaalpár' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.8140236, 'lng' => 19.9936556],
				],
				'Tiszakécske' => [
					'constituencies' => ['Bács-Kiskun 2.'],
					'coordinates' => ['lat' => 46.9358726, 'lng' => 20.0969279],
				],
				'Tiszaug' => [
					'constituencies' => ['Bács-Kiskun 4.'],
					'coordinates' => ['lat' => 46.8537215, 'lng' => 20.052921],
				],
				'Tompa' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.2060507, 'lng' => 19.5389553],
				],
				'Újsolt' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.8706098, 'lng' => 19.1186222],
				],
				'Újtelek' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.5911716, 'lng' => 19.0564597],
				],
				'Uszód' => [
					'constituencies' => ['Bács-Kiskun 3.'],
					'coordinates' => ['lat' => 46.5704972, 'lng' => 18.9038275],
				],
				'Városföld' => [
					'constituencies' => ['Bács-Kiskun 2.'],
					'coordinates' => ['lat' => 46.8174844, 'lng' => 19.7597893],
				],
				'Vaskút' => [
					'constituencies' => ['Bács-Kiskun 6.'],
					'coordinates' => ['lat' => 46.1080968, 'lng' => 18.9861524],
				],
				'Zsana' => [
					'constituencies' => ['Bács-Kiskun 5.'],
					'coordinates' => ['lat' => 46.3802847, 'lng' => 19.6600846],
				],
			],
			'Baranya' => [
				'Abaliget' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.1428711, 'lng' => 18.1152298],
				],
				'Adorjás' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8509119, 'lng' => 18.0617924],
				],
				'Ág' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.2962836, 'lng' => 18.2023275],
				],
				'Almamellék' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.1603198, 'lng' => 17.8765681],
				],
				'Almáskeresztúr' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.1199547, 'lng' => 17.8958453],
				],
				'Alsómocsolád' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.313518, 'lng' => 18.2481993],
				],
				'Alsószentmárton' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.7912208, 'lng' => 18.3065816],
				],
				'Apátvarasd' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.1856469, 'lng' => 18.47932],
				],
				'Aranyosgadány' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.007757, 'lng' => 18.1195466],
				],
				'Áta' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9367366, 'lng' => 18.2985608],
				],
				'Babarc' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.0042229, 'lng' => 18.5527511],
				],
				'Babarcszőlős' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.898699, 'lng' => 18.1360284],
				],
				'Bakóca' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.2074891, 'lng' => 18.0002016],
				],
				'Bakonya' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0850942, 'lng' => 18.082286],
				],
				'Baksa' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9554293, 'lng' => 18.0909794],
				],
				'Bánfa' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.994691, 'lng' => 17.8798792],
				],
				'Bár' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.0482419, 'lng' => 18.7119502],
				],
				'Baranyahídvég' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8461886, 'lng' => 18.0229597],
				],
				'Baranyajenő' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.2734519, 'lng' => 18.0469416],
				],
				'Baranyaszentgyörgy' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.2461345, 'lng' => 18.0119839],
				],
				'Basal' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0734372, 'lng' => 17.7832659],
				],
				'Belvárdgyula' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9750659, 'lng' => 18.4288438],
				],
				'Beremend' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.7877528, 'lng' => 18.4322322],
				],
				'Berkesd' => [
					'constituencies' => ['Baranya 2.'],
					'coordinates' => ['lat' => 46.0766759, 'lng' => 18.4078442],
				],
				'Besence' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8956421, 'lng' => 17.9654588],
				],
				'Bezedek' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.8653948, 'lng' => 18.5854023],
				],
				'Bicsérd' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0216488, 'lng' => 18.0779429],
				],
				'Bikal' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.3329154, 'lng' => 18.2845332],
				],
				'Birján' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.0007461, 'lng' => 18.3739733],
				],
				'Bisse' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9082449, 'lng' => 18.2603363],
				],
				'Boda' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0796449, 'lng' => 18.0477749],
				],
				'Bodolyabér' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.196906, 'lng' => 18.1189705],
				],
				'Bogád' => [
					'constituencies' => ['Baranya 2.'],
					'coordinates' => ['lat' => 46.0858618, 'lng' => 18.3215439],
				],
				'Bogádmindszent' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9069292, 'lng' => 18.0382456],
				],
				'Bogdása' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8756825, 'lng' => 17.7892759],
				],
				'Boldogasszonyfa' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.1826055, 'lng' => 17.8379176],
				],
				'Bóly' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9654045, 'lng' => 18.5166166],
				],
				'Borjád' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9356423, 'lng' => 18.4708549],
				],
				'Bosta' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9500492, 'lng' => 18.2104193],
				],
				'Botykapeterd' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0499466, 'lng' => 17.8662441],
				],
				'Bükkösd' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.1100188, 'lng' => 17.9925218],
				],
				'Bürüs' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9653278, 'lng' => 17.7591739],
				],
				'Csányoszró' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8810774, 'lng' => 17.9101381],
				],
				'Csarnóta' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8949174, 'lng' => 18.2163121],
				],
				'Csebény' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.1893582, 'lng' => 17.9275209],
				],
				'Cserdi' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0808529, 'lng' => 17.9911191],
				],
				'Cserkút' => [
					'constituencies' => ['Baranya 2.'],
					'coordinates' => ['lat' => 46.0756664, 'lng' => 18.1340119],
				],
				'Csertő' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.093457, 'lng' => 17.8034587],
				],
				'Csonkamindszent' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0518017, 'lng' => 17.9658056],
				],
				'Cún' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8122974, 'lng' => 18.0678543],
				],
				'Dencsháza' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.993512, 'lng' => 17.8347772],
				],
				'Dinnyeberki' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0972962, 'lng' => 17.9563165],
				],
				'Diósviszló' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8774861, 'lng' => 18.1640495],
				],
				'Drávacsehi' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8130167, 'lng' => 18.1666181],
				],
				'Drávacsepely' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8308297, 'lng' => 18.1352308],
				],
				'Drávafok' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8860365, 'lng' => 17.7636317],
				],
				'Drávaiványi' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8470684, 'lng' => 17.8159164],
				],
				'Drávakeresztúr' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8386967, 'lng' => 17.7580104],
				],
				'Drávapalkonya' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8033438, 'lng' => 18.1790753],
				],
				'Drávapiski' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8396577, 'lng' => 18.0989657],
				],
				'Drávaszabolcs' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.803275, 'lng' => 18.2093234],
				],
				'Drávaszerdahely' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8363562, 'lng' => 18.1638527],
				],
				'Drávasztára' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8230964, 'lng' => 17.8220692],
				],
				'Dunaszekcső' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.0854783, 'lng' => 18.7542203],
				],
				'Egerág' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9834452, 'lng' => 18.3039561],
				],
				'Egyházasharaszti' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.8097356, 'lng' => 18.3314381],
				],
				'Egyházaskozár' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.3319023, 'lng' => 18.3178591],
				],
				'Ellend' => [
					'constituencies' => ['Baranya 2.'],
					'coordinates' => ['lat' => 46.0580138, 'lng' => 18.3760682],
				],
				'Endrőc' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9296401, 'lng' => 17.7621758],
				],
				'Erdősmárok' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.055568, 'lng' => 18.5458091],
				],
				'Erdősmecske' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.1768439, 'lng' => 18.5109755],
				],
				'Erzsébet' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.1004339, 'lng' => 18.4587621],
				],
				'Fazekasboda' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.1230108, 'lng' => 18.4850924],
				],
				'Feked' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.1626797, 'lng' => 18.5588015],
				],
				'Felsőegerszeg' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.2539122, 'lng' => 18.1335751],
				],
				'Felsőszentmárton' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8513101, 'lng' => 17.7034033],
				],
				'Garé' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9180881, 'lng' => 18.1956808],
				],
				'Gerde' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9904428, 'lng' => 18.0255496],
				],
				'Gerényes' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.3070289, 'lng' => 18.1848981],
				],
				'Geresdlak' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.1107897, 'lng' => 18.5268599],
				],
				'Gilvánfa' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9184356, 'lng' => 17.9622098],
				],
				'Gödre' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.2899579, 'lng' => 17.9723779],
				],
				'Görcsöny' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9709725, 'lng' => 18.133486],
				],
				'Görcsönydoboka' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.0709275, 'lng' => 18.6275109],
				],
				'Gordisa' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.7970748, 'lng' => 18.2354868],
				],
				'Gyód' => [
					'constituencies' => ['Baranya 1.'],
					'coordinates' => ['lat' => 45.9979549, 'lng' => 18.1781638],
				],
				'Gyöngyfa' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9601196, 'lng' => 17.9506649],
				],
				'Gyöngyösmellék' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9868644, 'lng' => 17.7014751],
				],
				'Harkány' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8534053, 'lng' => 18.2348372],
				],
				'Hásságy' => [
					'constituencies' => ['Baranya 2.'],
					'coordinates' => ['lat' => 46.0330172, 'lng' => 18.388848],
				],
				'Hegyhátmaróc' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.3109929, 'lng' => 18.3362487],
				],
				'Hegyszentmárton' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9036373, 'lng' => 18.086797],
				],
				'Helesfa' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0894523, 'lng' => 17.9770167],
				],
				'Hetvehely' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.1332155, 'lng' => 18.0432466],
				],
				'Hidas' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.2574631, 'lng' => 18.4937015],
				],
				'Himesháza' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.0797595, 'lng' => 18.5805933],
				],
				'Hirics' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8247516, 'lng' => 17.9934259],
				],
				'Hobol' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0197823, 'lng' => 17.7724266],
				],
				'Homorúd' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.981847, 'lng' => 18.7887766],
				],
				'Horváthertelend' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.1751748, 'lng' => 17.9272893],
				],
				'Hosszúhetény' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.1583167, 'lng' => 18.3520974],
				],
				'Husztót' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.1711511, 'lng' => 18.0932139],
				],
				'Ibafa' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.1552456, 'lng' => 17.9179873],
				],
				'Illocska' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.800591, 'lng' => 18.5233576],
				],
				'Ipacsfa' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8345382, 'lng' => 18.2055561],
				],
				'Ivánbattyán' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9077809, 'lng' => 18.4176354],
				],
				'Ivándárda' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.831643, 'lng' => 18.5922589],
				],
				'Kacsóta' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0390809, 'lng' => 17.9544689],
				],
				'Kákics' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9028359, 'lng' => 17.8568313],
				],
				'Kárász' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.2667559, 'lng' => 18.3188548],
				],
				'Kásád' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.7793743, 'lng' => 18.3991912],
				],
				'Katádfa' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9970924, 'lng' => 17.8692171],
				],
				'Kátoly' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.0634292, 'lng' => 18.4496796],
				],
				'Kékesd' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.1007579, 'lng' => 18.4720006],
				],
				'Kémes' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8241919, 'lng' => 18.1031607],
				],
				'Kemse' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8237775, 'lng' => 17.9119613],
				],
				'Keszü' => [
					'constituencies' => ['Baranya 1.'],
					'coordinates' => ['lat' => 46.0160053, 'lng' => 18.1918765],
				],
				'Kétújfalu' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9643465, 'lng' => 17.7128738],
				],
				'Királyegyháza' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9975029, 'lng' => 17.9670799],
				],
				'Kisasszonyfa' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9467478, 'lng' => 18.0062386],
				],
				'Kisbeszterce' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.2054937, 'lng' => 18.033257],
				],
				'Kisbudmér' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9132933, 'lng' => 18.4468642],
				],
				'Kisdér' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9397014, 'lng' => 18.1280256],
				],
				'Kisdobsza' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0279686, 'lng' => 17.654966],
				],
				'Kishajmás' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.2000972, 'lng' => 18.0807394],
				],
				'Kisharsány' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.8597428, 'lng' => 18.3628602],
				],
				'Kisherend' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9657006, 'lng' => 18.3308199],
				],
				'Kisjakabfalva' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.8961294, 'lng' => 18.4347874],
				],
				'Kiskassa' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9532763, 'lng' => 18.3984025],
				],
				'Kislippó' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.8309942, 'lng' => 18.5387451],
				],
				'Kisnyárád' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.0369956, 'lng' => 18.5642298],
				],
				'Kisszentmárton' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8245119, 'lng' => 18.0223384],
				],
				'Kistamási' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0118086, 'lng' => 17.7210893],
				],
				'Kistapolca' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.8215113, 'lng' => 18.383003],
				],
				'Kistótfalu' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9080691, 'lng' => 18.3097841],
				],
				'Kisvaszar' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.2748571, 'lng' => 18.2126962],
				],
				'Köblény' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.2948258, 'lng' => 18.303697],
				],
				'Kökény' => [
					'constituencies' => ['Baranya 1.'],
					'coordinates' => ['lat' => 45.9995372, 'lng' => 18.2057648],
				],
				'Kölked' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9489796, 'lng' => 18.7058024],
				],
				'Komló' => [
					'constituencies' => ['Baranya 2.'],
					'coordinates' => ['lat' => 46.1929788, 'lng' => 18.2512139],
				],
				'Kórós' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8666591, 'lng' => 18.0818986],
				],
				'Kovácshida' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8322528, 'lng' => 18.1852847],
				],
				'Kovácsszénája' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.1714525, 'lng' => 18.1099753],
				],
				'Kővágószőlős' => [
					'constituencies' => ['Baranya 2.'],
					'coordinates' => ['lat' => 46.0824433, 'lng' => 18.1242335],
				],
				'Kővágótöttös' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0859181, 'lng' => 18.1005597],
				],
				'Kozármisleny' => [
					'constituencies' => ['Baranya 2.'],
					'coordinates' => ['lat' => 46.0412574, 'lng' => 18.2872228],
				],
				'Lánycsók' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.0073964, 'lng' => 18.624077],
				],
				'Lapáncsa' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.8187417, 'lng' => 18.4965793],
				],
				'Liget' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.2346633, 'lng' => 18.1924669],
				],
				'Lippó' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.863493, 'lng' => 18.5702136],
				],
				'Liptód' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.044203, 'lng' => 18.5153709],
				],
				'Lothárd' => [
					'constituencies' => ['Baranya 2.'],
					'coordinates' => ['lat' => 46.0015129, 'lng' => 18.3534664],
				],
				'Lovászhetény' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.1573687, 'lng' => 18.4736022],
				],
				'Lúzsok' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8386895, 'lng' => 17.9448893],
				],
				'Mágocs' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.3507989, 'lng' => 18.2282954],
				],
				'Magyarbóly' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.8424536, 'lng' => 18.4905327],
				],
				'Magyaregregy' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.2497645, 'lng' => 18.3080926],
				],
				'Magyarhertelend' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.1887919, 'lng' => 18.1496193],
				],
				'Magyarlukafa' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.1692382, 'lng' => 17.7566367],
				],
				'Magyarmecske' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9444333, 'lng' => 17.963957],
				],
				'Magyarsarlós' => [
					'constituencies' => ['Baranya 2.'],
					'coordinates' => ['lat' => 46.0412482, 'lng' => 18.3527956],
				],
				'Magyarszék' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.1966719, 'lng' => 18.1955889],
				],
				'Magyartelek' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9438384, 'lng' => 17.9834231],
				],
				'Majs' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9090894, 'lng' => 18.59764],
				],
				'Mánfa' => [
					'constituencies' => ['Baranya 2.'],
					'coordinates' => ['lat' => 46.1620219, 'lng' => 18.2424376],
				],
				'Maráza' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.0767639, 'lng' => 18.5102704],
				],
				'Márfa' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8597093, 'lng' => 18.184506],
				],
				'Máriakéménd' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.0275242, 'lng' => 18.4616888],
				],
				'Markóc' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8633597, 'lng' => 17.7628134],
				],
				'Marócsa' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9143499, 'lng' => 17.8155625],
				],
				'Márok' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.8776725, 'lng' => 18.5052153],
				],
				'Martonfa' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.1162762, 'lng' => 18.373108],
				],
				'Matty' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.7959854, 'lng' => 18.2646823],
				],
				'Máza' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.2674701, 'lng' => 18.3987184],
				],
				'Mecseknádasd' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.22466, 'lng' => 18.4653855],
				],
				'Mecsekpölöske' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.2232838, 'lng' => 18.2117379],
				],
				'Mekényes' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.3905907, 'lng' => 18.3338629],
				],
				'Merenye' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.069313, 'lng' => 17.6981454],
				],
				'Meződ' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.2898147, 'lng' => 18.1028572],
				],
				'Mindszentgodisa' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.2270491, 'lng' => 18.070952],
				],
				'Mohács' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.0046295, 'lng' => 18.6794304],
				],
				'Molvány' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0294158, 'lng' => 17.7455964],
				],
				'Monyoród' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.0115276, 'lng' => 18.4781726],
				],
				'Mozsgó' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.1148249, 'lng' => 17.8457585],
				],
				'Nagybudmér' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9378397, 'lng' => 18.4443309],
				],
				'Nagycsány' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.871837, 'lng' => 17.9441308],
				],
				'Nagydobsza' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0290366, 'lng' => 17.6672107],
				],
				'Nagyhajmás' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.372206, 'lng' => 18.2898052],
				],
				'Nagyharsány' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.8466947, 'lng' => 18.3947776],
				],
				'Nagykozár' => [
					'constituencies' => ['Baranya 2.'],
					'coordinates' => ['lat' => 46.067814, 'lng' => 18.316561],
				],
				'Nagynyárád' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9447148, 'lng' => 18.578055],
				],
				'Nagypall' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.1474016, 'lng' => 18.4539234],
				],
				'Nagypeterd' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0459728, 'lng' => 17.8979423],
				],
				'Nagytótfalu' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.8638406, 'lng' => 18.3426767],
				],
				'Nagyváty' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0617075, 'lng' => 17.93209],
				],
				'Nemeske' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.020198, 'lng' => 17.7129695],
				],
				'Nyugotszenterzsébet' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0747959, 'lng' => 17.9096635],
				],
				'Óbánya' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.2220338, 'lng' => 18.4084838],
				],
				'Ócsárd' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9341296, 'lng' => 18.1533436],
				],
				'Ófalu' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.2210918, 'lng' => 18.534029],
				],
				'Okorág' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9262423, 'lng' => 17.8761913],
				],
				'Okorvölgy' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.15235, 'lng' => 18.0600392],
				],
				'Olasz' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.0128298, 'lng' => 18.4122965],
				],
				'Old' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.7893924, 'lng' => 18.3526547],
				],
				'Orfű' => [
					'constituencies' => ['Baranya 2.'],
					'coordinates' => ['lat' => 46.1504207, 'lng' => 18.1423992],
				],
				'Oroszló' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.2201904, 'lng' => 18.122659],
				],
				'Ózdfalu' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9288431, 'lng' => 18.0210679],
				],
				'Palé' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.2603608, 'lng' => 18.0690432],
				],
				'Palkonya' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.8968607, 'lng' => 18.3899099],
				],
				'Palotabozsok' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.1275672, 'lng' => 18.6416844],
				],
				'Páprád' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8927275, 'lng' => 18.0103745],
				],
				'Patapoklosi' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0753051, 'lng' => 17.7415323],
				],
				'Pécs' => [
					'constituencies' => ['Baranya 2.', 'Baranya 1.'],
					'coordinates' => ['lat' => 46.0727345, 'lng' => 18.232266],
				],
				'Pécsbagota' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9906469, 'lng' => 18.0728758],
				],
				'Pécsdevecser' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9585177, 'lng' => 18.3839237],
				],
				'Pécsudvard' => [
					'constituencies' => ['Baranya 1.'],
					'coordinates' => ['lat' => 46.0108323, 'lng' => 18.2750737],
				],
				'Pécsvárad' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.1591341, 'lng' => 18.4185199],
				],
				'Pellérd' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.034172, 'lng' => 18.1551531],
				],
				'Pereked' => [
					'constituencies' => ['Baranya 2.'],
					'coordinates' => ['lat' => 46.0940085, 'lng' => 18.3768639],
				],
				'Peterd' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9726228, 'lng' => 18.3606704],
				],
				'Pettend' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0001576, 'lng' => 17.7011535],
				],
				'Piskó' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8112973, 'lng' => 17.9384454],
				],
				'Pócsa' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9100922, 'lng' => 18.4699792],
				],
				'Pogány' => [
					'constituencies' => ['Baranya 1.'],
					'coordinates' => ['lat' => 45.9827333, 'lng' => 18.2568939],
				],
				'Rádfalva' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8598624, 'lng' => 18.1252323],
				],
				'Regenye' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.969783, 'lng' => 18.1685228],
				],
				'Romonya' => [
					'constituencies' => ['Baranya 2.'],
					'coordinates' => ['lat' => 46.0871177, 'lng' => 18.3391112],
				],
				'Rózsafa' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0227215, 'lng' => 17.8889708],
				],
				'Sámod' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8536384, 'lng' => 18.0384521],
				],
				'Sárok' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.8414254, 'lng' => 18.6119412],
				],
				'Sásd' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.2563232, 'lng' => 18.1024778],
				],
				'Sátorhely' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9417452, 'lng' => 18.6330768],
				],
				'Sellye' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.873291, 'lng' => 17.8494986],
				],
				'Siklós' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8555814, 'lng' => 18.2979721],
				],
				'Siklósbodony' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9105251, 'lng' => 18.1202589],
				],
				'Siklósnagyfalu' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.820428, 'lng' => 18.3636246],
				],
				'Somberek' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.0812348, 'lng' => 18.6586781],
				],
				'Somogyapáti' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0920041, 'lng' => 17.7506787],
				],
				'Somogyhárságy' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.1623103, 'lng' => 17.7731873],
				],
				'Somogyhatvan' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.1120284, 'lng' => 17.7126553],
				],
				'Somogyviszló' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.1146313, 'lng' => 17.7636375],
				],
				'Sósvertike' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8340815, 'lng' => 17.8614028],
				],
				'Sumony' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9675435, 'lng' => 17.9146319],
				],
				'Szabadszentkirály' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0059012, 'lng' => 18.0435247],
				],
				'Szágy' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.2244706, 'lng' => 17.9469817],
				],
				'Szajk' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9921175, 'lng' => 18.5328986],
				],
				'Szalánta' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9471908, 'lng' => 18.2376181],
				],
				'Szalatnak' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.2903675, 'lng' => 18.2809735],
				],
				'Szaporca' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8135724, 'lng' => 18.1045054],
				],
				'Szárász' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.3487743, 'lng' => 18.3727487],
				],
				'Szászvár' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.2739639, 'lng' => 18.3774781],
				],
				'Szava' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9024581, 'lng' => 18.1738569],
				],
				'Szebény' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.1296283, 'lng' => 18.5879918],
				],
				'Szederkény' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9986735, 'lng' => 18.4530663],
				],
				'Székelyszabar' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.0471326, 'lng' => 18.6012321],
				],
				'Szellő' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.0744167, 'lng' => 18.4609549],
				],
				'Szemely' => [
					'constituencies' => ['Baranya 2.'],
					'coordinates' => ['lat' => 46.0083381, 'lng' => 18.3256717],
				],
				'Szentdénes' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0079644, 'lng' => 17.9271651],
				],
				'Szentegát' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9754975, 'lng' => 17.8244079],
				],
				'Szentkatalin' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.174384, 'lng' => 18.0505714],
				],
				'Szentlászló' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.1540417, 'lng' => 17.8331512],
				],
				'Szentlőrinc' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0403123, 'lng' => 17.9897756],
				],
				'Szigetvár' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0487727, 'lng' => 17.7983466],
				],
				'Szilágy' => [
					'constituencies' => ['Baranya 2.'],
					'coordinates' => ['lat' => 46.1009525, 'lng' => 18.4065405],
				],
				'Szilvás' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9616358, 'lng' => 18.1981701],
				],
				'Szőke' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9604273, 'lng' => 18.1867423],
				],
				'Szőkéd' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9645154, 'lng' => 18.2884592],
				],
				'Szörény' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9683861, 'lng' => 17.6819713],
				],
				'Szulimán' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.1264433, 'lng' => 17.805449],
				],
				'Szűr' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.099254, 'lng' => 18.5809615],
				],
				'Tarrós' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.2806564, 'lng' => 18.1425225],
				],
				'Tékes' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.2866262, 'lng' => 18.1744149],
				],
				'Teklafalu' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9493136, 'lng' => 17.7287585],
				],
				'Tengeri' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9263477, 'lng' => 18.087938],
				],
				'Tésenfa' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8127763, 'lng' => 18.1178921],
				],
				'Téseny' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9515499, 'lng' => 18.0479966],
				],
				'Tófű' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.3094872, 'lng' => 18.3576794],
				],
				'Tormás' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.2309543, 'lng' => 17.9937201],
				],
				'Tótszentgyörgy' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0521798, 'lng' => 17.7178541],
				],
				'Töttös' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9150433, 'lng' => 18.5407584],
				],
				'Túrony' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9054082, 'lng' => 18.2309533],
				],
				'Udvar' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.900472, 'lng' => 18.6594842],
				],
				'Újpetre' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.934779, 'lng' => 18.3636323],
				],
				'Vajszló' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8592442, 'lng' => 17.9868205],
				],
				'Várad' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9743574, 'lng' => 17.7456586],
				],
				'Varga' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.2475508, 'lng' => 18.1424694],
				],
				'Vásárosbéc' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.1825351, 'lng' => 17.7246441],
				],
				'Vásárosdombó' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.3064752, 'lng' => 18.1334675],
				],
				'Vázsnok' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.2653395, 'lng' => 18.1253751],
				],
				'Vejti' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8096089, 'lng' => 17.9682522],
				],
				'Vékény' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.2695945, 'lng' => 18.3423454],
				],
				'Velény' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9807601, 'lng' => 18.0514344],
				],
				'Véménd' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.1551161, 'lng' => 18.6190866],
				],
				'Versend' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9953039, 'lng' => 18.5115869],
				],
				'Villány' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.8700399, 'lng' => 18.453201],
				],
				'Villánykövesd' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.8823189, 'lng' => 18.425812],
				],
				'Vokány' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 45.9133714, 'lng' => 18.3364685],
				],
				'Zádor' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.9623692, 'lng' => 17.6579278],
				],
				'Zaláta' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 45.8111976, 'lng' => 17.8901202],
				],
				'Zengővárkony' => [
					'constituencies' => ['Baranya 3.'],
					'coordinates' => ['lat' => 46.1728638, 'lng' => 18.4320077],
				],
				'Zók' => [
					'constituencies' => ['Baranya 4.'],
					'coordinates' => ['lat' => 46.0104261, 'lng' => 18.0965422],
				],
			],
			'Békés' => [
				'Almáskamarás' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.4617785, 'lng' => 21.092448],
				],
				'Battonya' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.2902462, 'lng' => 21.0199215],
				],
				'Békés' => [
					'constituencies' => ['Békés 2.'],
					'coordinates' => ['lat' => 46.6704899, 'lng' => 21.0434996],
				],
				'Békéscsaba' => [
					'constituencies' => ['Békés 1.'],
					'coordinates' => ['lat' => 46.6735939, 'lng' => 21.0877309],
				],
				'Békéssámson' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.4208677, 'lng' => 20.6176498],
				],
				'Békésszentandrás' => [
					'constituencies' => ['Békés 2.'],
					'coordinates' => ['lat' => 46.8715996, 'lng' => 20.48336],
				],
				'Bélmegyer' => [
					'constituencies' => ['Békés 3.'],
					'coordinates' => ['lat' => 46.8726019, 'lng' => 21.1832832],
				],
				'Biharugra' => [
					'constituencies' => ['Békés 3.'],
					'coordinates' => ['lat' => 46.9691009, 'lng' => 21.5987651],
				],
				'Bucsa' => [
					'constituencies' => ['Békés 2.'],
					'coordinates' => ['lat' => 47.2047017, 'lng' => 20.9970391],
				],
				'Csabacsűd' => [
					'constituencies' => ['Békés 2.'],
					'coordinates' => ['lat' => 46.8244161, 'lng' => 20.6485242],
				],
				'Csabaszabadi' => [
					'constituencies' => ['Békés 1.'],
					'coordinates' => ['lat' => 46.574811, 'lng' => 20.951145],
				],
				'Csanádapáca' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.5409397, 'lng' => 20.8852553],
				],
				'Csárdaszállás' => [
					'constituencies' => ['Békés 2.'],
					'coordinates' => ['lat' => 46.8647568, 'lng' => 20.9374853],
				],
				'Csorvás' => [
					'constituencies' => ['Békés 1.'],
					'coordinates' => ['lat' => 46.6308376, 'lng' => 20.8340929],
				],
				'Dévaványa' => [
					'constituencies' => ['Békés 2.'],
					'coordinates' => ['lat' => 47.0313217, 'lng' => 20.9595443],
				],
				'Doboz' => [
					'constituencies' => ['Békés 3.'],
					'coordinates' => ['lat' => 46.7343152, 'lng' => 21.2420659],
				],
				'Dombegyház' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.3415879, 'lng' => 21.1342664],
				],
				'Dombiratos' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.4195218, 'lng' => 21.1178789],
				],
				'Ecsegfalva' => [
					'constituencies' => ['Békés 2.'],
					'coordinates' => ['lat' => 47.14789, 'lng' => 20.9239261],
				],
				'Elek' => [
					'constituencies' => ['Békés 3.'],
					'coordinates' => ['lat' => 46.5291929, 'lng' => 21.2487556],
				],
				'Füzesgyarmat' => [
					'constituencies' => ['Békés 3.'],
					'coordinates' => ['lat' => 47.1051107, 'lng' => 21.2108329],
				],
				'Gádoros' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.6667476, 'lng' => 20.5961159],
				],
				'Gerendás' => [
					'constituencies' => ['Békés 1.'],
					'coordinates' => ['lat' => 46.5969212, 'lng' => 20.8593687],
				],
				'Geszt' => [
					'constituencies' => ['Békés 3.'],
					'coordinates' => ['lat' => 46.8831763, 'lng' => 21.5794915],
				],
				'Gyomaendrőd' => [
					'constituencies' => ['Békés 2.'],
					'coordinates' => ['lat' => 46.9317797, 'lng' => 20.8113125],
				],
				'Gyula' => [
					'constituencies' => ['Békés 3.'],
					'coordinates' => ['lat' => 46.6473027, 'lng' => 21.2784255],
				],
				'Hunya' => [
					'constituencies' => ['Békés 2.'],
					'coordinates' => ['lat' => 46.812869, 'lng' => 20.8458337],
				],
				'Kamut' => [
					'constituencies' => ['Békés 2.'],
					'coordinates' => ['lat' => 46.7619186, 'lng' => 20.9798143],
				],
				'Kardos' => [
					'constituencies' => ['Békés 2.'],
					'coordinates' => ['lat' => 46.7941712, 'lng' => 20.715629],
				],
				'Kardoskút' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.498573, 'lng' => 20.7040158],
				],
				'Kaszaper' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.4598817, 'lng' => 20.8251944],
				],
				'Kertészsziget' => [
					'constituencies' => ['Békés 2.'],
					'coordinates' => ['lat' => 47.1542945, 'lng' => 21.0610234],
				],
				'Kétegyháza' => [
					'constituencies' => ['Békés 1.'],
					'coordinates' => ['lat' => 46.5417887, 'lng' => 21.1810736],
				],
				'Kétsoprony' => [
					'constituencies' => ['Békés 1.'],
					'coordinates' => ['lat' => 46.7208319, 'lng' => 20.8870273],
				],
				'Kevermes' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.4167579, 'lng' => 21.1818484],
				],
				'Kisdombegyház' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.3693244, 'lng' => 21.0996778],
				],
				'Kondoros' => [
					'constituencies' => ['Békés 1.'],
					'coordinates' => ['lat' => 46.7574628, 'lng' => 20.7972363],
				],
				'Körösladány' => [
					'constituencies' => ['Békés 2.'],
					'coordinates' => ['lat' => 46.9607513, 'lng' => 21.0767574],
				],
				'Körösnagyharsány' => [
					'constituencies' => ['Békés 3.'],
					'coordinates' => ['lat' => 47.0080391, 'lng' => 21.6417355],
				],
				'Köröstarcsa' => [
					'constituencies' => ['Békés 2.'],
					'coordinates' => ['lat' => 46.8780314, 'lng' => 21.02402],
				],
				'Körösújfalu' => [
					'constituencies' => ['Békés 3.'],
					'coordinates' => ['lat' => 46.9659419, 'lng' => 21.3988486],
				],
				'Kötegyán' => [
					'constituencies' => ['Békés 3.'],
					'coordinates' => ['lat' => 46.738284, 'lng' => 21.481692],
				],
				'Kunágota' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.4234015, 'lng' => 21.0467553],
				],
				'Lőkösháza' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.4297019, 'lng' => 21.2318793],
				],
				'Magyarbánhegyes' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.4577279, 'lng' => 20.968734],
				],
				'Magyardombegyház' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.3794548, 'lng' => 21.0743712],
				],
				'Medgyesbodzás' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.5186797, 'lng' => 20.9596371],
				],
				'Medgyesegyháza' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.4967576, 'lng' => 21.0271996],
				],
				'Méhkerék' => [
					'constituencies' => ['Békés 3.'],
					'coordinates' => ['lat' => 46.7735176, 'lng' => 21.4435935],
				],
				'Mezőberény' => [
					'constituencies' => ['Békés 2.'],
					'coordinates' => ['lat' => 46.825687, 'lng' => 21.0243614],
				],
				'Mezőgyán' => [
					'constituencies' => ['Békés 3.'],
					'coordinates' => ['lat' => 46.8709809, 'lng' => 21.5257366],
				],
				'Mezőhegyes' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.3172449, 'lng' => 20.8173892],
				],
				'Mezőkovácsháza' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.4093003, 'lng' => 20.9112692],
				],
				'Murony' => [
					'constituencies' => ['Békés 2.'],
					'coordinates' => ['lat' => 46.760463, 'lng' => 21.0411739],
				],
				'Nagybánhegyes' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.460095, 'lng' => 20.902578],
				],
				'Nagykamarás' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.4727168, 'lng' => 21.1213871],
				],
				'Nagyszénás' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.6722161, 'lng' => 20.6734381],
				],
				'Okány' => [
					'constituencies' => ['Békés 3.'],
					'coordinates' => ['lat' => 46.8982798, 'lng' => 21.3467384],
				],
				'Örménykút' => [
					'constituencies' => ['Békés 2.'],
					'coordinates' => ['lat' => 46.830573, 'lng' => 20.7344497],
				],
				'Orosháza' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.5684222, 'lng' => 20.6544927],
				],
				'Pusztaföldvár' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.5251751, 'lng' => 20.8024526],
				],
				'Pusztaottlaka' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.5386606, 'lng' => 21.0060316],
				],
				'Sarkad' => [
					'constituencies' => ['Békés 3.'],
					'coordinates' => ['lat' => 46.7374245, 'lng' => 21.3810771],
				],
				'Sarkadkeresztúr' => [
					'constituencies' => ['Békés 3.'],
					'coordinates' => ['lat' => 46.8107081, 'lng' => 21.3841932],
				],
				'Szabadkígyós' => [
					'constituencies' => ['Békés 1.'],
					'coordinates' => ['lat' => 46.601522, 'lng' => 21.0753003],
				],
				'Szarvas' => [
					'constituencies' => ['Békés 2.'],
					'coordinates' => ['lat' => 46.8635641, 'lng' => 20.5526535],
				],
				'Szeghalom' => [
					'constituencies' => ['Békés 3.'],
					'coordinates' => ['lat' => 47.0239347, 'lng' => 21.1666571],
				],
				'Tarhos' => [
					'constituencies' => ['Békés 3.'],
					'coordinates' => ['lat' => 46.8132012, 'lng' => 21.2109597],
				],
				'Telekgerendás' => [
					'constituencies' => ['Békés 1.'],
					'coordinates' => ['lat' => 46.6566167, 'lng' => 20.9496242],
				],
				'Tótkomlós' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.4107596, 'lng' => 20.7363644],
				],
				'Újkígyós' => [
					'constituencies' => ['Békés 1.'],
					'coordinates' => ['lat' => 46.5899757, 'lng' => 21.0242728],
				],
				'Újszalonta' => [
					'constituencies' => ['Békés 3.'],
					'coordinates' => ['lat' => 46.8128247, 'lng' => 21.4908762],
				],
				'Végegyháza' => [
					'constituencies' => ['Békés 4.'],
					'coordinates' => ['lat' => 46.3882623, 'lng' => 20.8699923],
				],
				'Vésztő' => [
					'constituencies' => ['Békés 3.'],
					'coordinates' => ['lat' => 46.9244546, 'lng' => 21.2628502],
				],
				'Zsadány' => [
					'constituencies' => ['Békés 3.'],
					'coordinates' => ['lat' => 46.9230248, 'lng' => 21.4873156],
				],
			],
			'Borsod-Abaúj-Zemplén' => [
				'Abaújalpár' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3065157, 'lng' => 21.232147],
				],
				'Abaújkér' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3033478, 'lng' => 21.2013068],
				],
				'Abaújlak' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.4051818, 'lng' => 20.9548056],
				],
				'Abaújszántó' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.2792184, 'lng' => 21.1874523],
				],
				'Abaújszolnok' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3730791, 'lng' => 20.9749255],
				],
				'Abaújvár' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.5266538, 'lng' => 21.3150208],
				],
				'Abod' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3928646, 'lng' => 20.7923344],
				],
				'Aggtelek' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.4686657, 'lng' => 20.5040699],
				],
				'Alacska' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2157484, 'lng' => 20.6502945],
				],
				'Alsóberecki' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3437614, 'lng' => 21.6905164],
				],
				'Alsódobsza' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.1799523, 'lng' => 21.0026817],
				],
				'Alsógagy' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.4052855, 'lng' => 21.0255485],
				],
				'Alsóregmec' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4634336, 'lng' => 21.6181953],
				],
				'Alsószuha' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.3726027, 'lng' => 20.5044038],
				],
				'Alsótelekes' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.4105212, 'lng' => 20.6547156],
				],
				'Alsóvadász' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2401438, 'lng' => 20.9043765],
				],
				'Alsózsolca' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 1.'],
					'coordinates' => ['lat' => 48.0748263, 'lng' => 20.8850624],
				],
				'Arka' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3562385, 'lng' => 21.252529],
				],
				'Arló' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.1746548, 'lng' => 20.2560308],
				],
				'Arnót' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 1.'],
					'coordinates' => ['lat' => 48.1319962, 'lng' => 20.859401],
				],
				'Ároktő' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.7284812, 'lng' => 20.9423131],
				],
				'Aszaló' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.2177554, 'lng' => 20.9624804],
				],
				'Baktakék' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3675199, 'lng' => 21.0288911],
				],
				'Balajt' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3210349, 'lng' => 20.7866111],
				],
				'Bánhorváti' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2260139, 'lng' => 20.504815],
				],
				'Bánréve' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.2986902, 'lng' => 20.3560194],
				],
				'Baskó' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3326787, 'lng' => 21.336418],
				],
				'Becskeháza' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.5294979, 'lng' => 20.8354743],
				],
				'Bekecs' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.1534102, 'lng' => 21.1762263],
				],
				'Berente' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2385836, 'lng' => 20.6700776],
				],
				'Beret' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3458722, 'lng' => 21.0235103],
				],
				'Berzék' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 48.0240535, 'lng' => 20.9528886],
				],
				'Bőcs' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.0442332, 'lng' => 20.9683874],
				],
				'Bodroghalom' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3009977, 'lng' => 21.707044],
				],
				'Bodrogkeresztúr' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.1630176, 'lng' => 21.3595899],
				],
				'Bodrogkisfalud' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.1789303, 'lng' => 21.3617788],
				],
				'Bodrogolaszi' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.2867085, 'lng' => 21.5160527],
				],
				'Bódvalenke' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.5424028, 'lng' => 20.8041838],
				],
				'Bódvarákó' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.5111514, 'lng' => 20.7358047],
				],
				'Bódvaszilas' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.5377629, 'lng' => 20.7312757],
				],
				'Bogács' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.9030764, 'lng' => 20.5312356],
				],
				'Boldogkőújfalu' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3193629, 'lng' => 21.242022],
				],
				'Boldogkőváralja' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3380634, 'lng' => 21.2367554],
				],
				'Boldva' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.218091, 'lng' => 20.7886144],
				],
				'Borsodbóta' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.2121829, 'lng' => 20.3960602],
				],
				'Borsodgeszt' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.9559428, 'lng' => 20.6944004],
				],
				'Borsodivánka' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.701045, 'lng' => 20.6547148],
				],
				'Borsodnádasd' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.1191717, 'lng' => 20.2529566],
				],
				'Borsodszentgyörgy' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.1892068, 'lng' => 20.2073894],
				],
				'Borsodszirák' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2610318, 'lng' => 20.7676252],
				],
				'Bózsva' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4743356, 'lng' => 21.468268],
				],
				'Bükkábrány' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.8884157, 'lng' => 20.6810544],
				],
				'Bükkaranyos' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.9866329, 'lng' => 20.7794609],
				],
				'Bükkmogyorósd' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.1291531, 'lng' => 20.3563552],
				],
				'Bükkszentkereszt' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 48.0668164, 'lng' => 20.6324773],
				],
				'Bükkzsérc' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.9587559, 'lng' => 20.5025627],
				],
				'Büttös' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.4783127, 'lng' => 21.0110122],
				],
				'Cigánd' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.2558937, 'lng' => 21.8889241],
				],
				'Csenyéte' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.4345165, 'lng' => 21.0412334],
				],
				'Cserépfalu' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.9413093, 'lng' => 20.5347083],
				],
				'Cserépváralja' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.9325883, 'lng' => 20.5598918],
				],
				'Csernely' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.1438586, 'lng' => 20.3390005],
				],
				'Csincse' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.8883234, 'lng' => 20.768705],
				],
				'Csobád' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2796877, 'lng' => 21.0269782],
				],
				'Csobaj' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.0485163, 'lng' => 21.3382189],
				],
				'Csokvaomány' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.1666711, 'lng' => 20.3744746],
				],
				'Damak' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3168034, 'lng' => 20.8216124],
				],
				'Dámóc' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3748294, 'lng' => 22.0336128],
				],
				'Debréte' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.5000066, 'lng' => 20.8661035],
				],
				'Dédestapolcsány' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.1804582, 'lng' => 20.4850166],
				],
				'Detek' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3336841, 'lng' => 21.0176305],
				],
				'Domaháza' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.1836193, 'lng' => 20.1055583],
				],
				'Dövény' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.3469512, 'lng' => 20.5431344],
				],
				'Dubicsány' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.2837745, 'lng' => 20.4940325],
				],
				'Edelény' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2934391, 'lng' => 20.7385817],
				],
				'Egerlövő' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.7203221, 'lng' => 20.6175935],
				],
				'Égerszög' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.442896, 'lng' => 20.5875195],
				],
				'Emőd' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.9380038, 'lng' => 20.8154444],
				],
				'Encs' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3259442, 'lng' => 21.1133006],
				],
				'Erdőbénye' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.2662769, 'lng' => 21.3547995],
				],
				'Erdőhorváti' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3158739, 'lng' => 21.4272709],
				],
				'Fáj' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.4219028, 'lng' => 21.0747972],
				],
				'Fancsal' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3552347, 'lng' => 21.064671],
				],
				'Farkaslyuk' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.1876627, 'lng' => 20.3086509],
				],
				'Felsőberecki' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3595718, 'lng' => 21.6950761],
				],
				'Felsődobsza' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.2555859, 'lng' => 21.0764245],
				],
				'Felsőgagy' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.4289932, 'lng' => 21.0128468],
				],
				'Felsőkelecsény' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.3600051, 'lng' => 20.5939689],
				],
				'Felsőnyárád' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.3299583, 'lng' => 20.5995966],
				],
				'Felsőregmec' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4915243, 'lng' => 21.6056225],
				],
				'Felsőtelekes' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.4058831, 'lng' => 20.6352386],
				],
				'Felsővadász' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3709811, 'lng' => 20.9195765],
				],
				'Felsőzsolca' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 1.'],
					'coordinates' => ['lat' => 48.1041265, 'lng' => 20.8595396],
				],
				'Filkeháza' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4960919, 'lng' => 21.4888024],
				],
				'Fony' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3910341, 'lng' => 21.2865504],
				],
				'Forró' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3233535, 'lng' => 21.0880493],
				],
				'Fulókércs' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4308674, 'lng' => 21.1049891],
				],
				'Füzér' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.539654, 'lng' => 21.4547936],
				],
				'Füzérkajata' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.5182556, 'lng' => 21.5000318],
				],
				'Füzérkomlós' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.5126205, 'lng' => 21.4532344],
				],
				'Füzérradvány' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.483741, 'lng' => 21.530474],
				],
				'Gadna' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.4006289, 'lng' => 20.9296444],
				],
				'Gagyapáti' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.409096, 'lng' => 21.0017182],
				],
				'Gagybátor' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.433303, 'lng' => 20.94859],
				],
				'Gagyvendégi' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.4285166, 'lng' => 20.972405],
				],
				'Galvács' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.4190767, 'lng' => 20.7767621],
				],
				'Garadna' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4174625, 'lng' => 21.17463],
				],
				'Gelej' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.828655, 'lng' => 20.7755503],
				],
				'Gesztely' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.1026673, 'lng' => 20.9654647],
				],
				'Gibárt' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3153245, 'lng' => 21.1603909],
				],
				'Girincs' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 47.9691368, 'lng' => 20.9846965],
				],
				'Golop' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.2374312, 'lng' => 21.1893372],
				],
				'Gömörszőlős' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.3730427, 'lng' => 20.4276758],
				],
				'Gönc' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4727097, 'lng' => 21.2735417],
				],
				'Göncruszka' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4488786, 'lng' => 21.239774],
				],
				'Györgytarló' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.2053902, 'lng' => 21.6316333],
				],
				'Halmaj' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.2464584, 'lng' => 20.9983349],
				],
				'Hangács' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2896949, 'lng' => 20.8314625],
				],
				'Hangony' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.2290868, 'lng' => 20.198029],
				],
				'Háromhuta' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3780662, 'lng' => 21.4283347],
				],
				'Harsány' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.9679177, 'lng' => 20.7418041],
				],
				'Hegymeg' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3314259, 'lng' => 20.8614048],
				],
				'Hejce' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4234865, 'lng' => 21.2816978],
				],
				'Hejőbába' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.9059201, 'lng' => 20.9452436],
				],
				'Hejőkeresztúr' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.9610209, 'lng' => 20.8772681],
				],
				'Hejőkürt' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.8564708, 'lng' => 20.9930661],
				],
				'Hejőpapi' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.8972354, 'lng' => 20.9054713],
				],
				'Hejőszalonta' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.9388389, 'lng' => 20.8822344],
				],
				'Hercegkút' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3340476, 'lng' => 21.5301233],
				],
				'Hernádbűd' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.2966038, 'lng' => 21.137896],
				],
				'Hernádcéce' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3587807, 'lng' => 21.1976117],
				],
				'Hernádkak' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.0892117, 'lng' => 20.9635617],
				],
				'Hernádkércs' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.2420151, 'lng' => 21.0501362],
				],
				'Hernádnémeti' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.0716822, 'lng' => 20.9742345],
				],
				'Hernádpetri' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4815086, 'lng' => 21.1622472],
				],
				'Hernádszentandrás' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2890724, 'lng' => 21.0949074],
				],
				'Hernádszurdok' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.48169, 'lng' => 21.2071561],
				],
				'Hernádvécse' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4406714, 'lng' => 21.1687099],
				],
				'Hét' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.282992, 'lng' => 20.3875674],
				],
				'Hidasnémeti' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.5029778, 'lng' => 21.2293013],
				],
				'Hidvégardó' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.5598883, 'lng' => 20.8395348],
				],
				'Hollóháza' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.5393716, 'lng' => 21.4144474],
				],
				'Homrogd' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2834505, 'lng' => 20.9125329],
				],
				'Igrici' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.8673926, 'lng' => 20.8831705],
				],
				'Imola' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.4201572, 'lng' => 20.5516409],
				],
				'Ináncs' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2861362, 'lng' => 21.0681971],
				],
				'Irota' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3964482, 'lng' => 20.8752667],
				],
				'Izsófalva' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3087892, 'lng' => 20.6536072],
				],
				'Jákfalva' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.3316408, 'lng' => 20.569496],
				],
				'Járdánháza' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.1551033, 'lng' => 20.2477262],
				],
				'Jósvafő' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.4826254, 'lng' => 20.5504479],
				],
				'Kács' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.9574786, 'lng' => 20.6145847],
				],
				'Kánó' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.4276397, 'lng' => 20.5991681],
				],
				'Kány' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.5151651, 'lng' => 21.0143542],
				],
				'Karcsa' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3131571, 'lng' => 21.7953512],
				],
				'Karos' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3312141, 'lng' => 21.7406654],
				],
				'Kazincbarcika' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2489437, 'lng' => 20.6189771],
				],
				'Kázsmárk' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2728658, 'lng' => 20.9760294],
				],
				'Kéked' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.5447244, 'lng' => 21.3500526],
				],
				'Kelemér' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.3551802, 'lng' => 20.4296357],
				],
				'Kenézlő' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.2004193, 'lng' => 21.5311235],
				],
				'Keresztéte' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.4989547, 'lng' => 20.950696],
				],
				'Kesznyéten' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 47.9694339, 'lng' => 21.0413905],
				],
				'Királd' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.2393694, 'lng' => 20.3764361],
				],
				'Kiscsécs' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 47.9678112, 'lng' => 21.011133],
				],
				'Kisgyőr' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 48.0096251, 'lng' => 20.6874073],
				],
				'Kishuta' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4503449, 'lng' => 21.4814089],
				],
				'Kiskinizs' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2508135, 'lng' => 21.0345918],
				],
				'Kisrozvágy' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3491303, 'lng' => 21.9390758],
				],
				'Kissikátor' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.1946631, 'lng' => 20.1302306],
				],
				'Kistokaj' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 48.0397115, 'lng' => 20.8410079],
				],
				'Komjáti' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.5452009, 'lng' => 20.7618268],
				],
				'Komlóska' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3404486, 'lng' => 21.4622875],
				],
				'Kondó' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.1880491, 'lng' => 20.6438586],
				],
				'Korlát' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3779667, 'lng' => 21.2457327],
				],
				'Köröm' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.9842491, 'lng' => 20.9545886],
				],
				'Kovácsvágás' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.45352, 'lng' => 21.5283164],
				],
				'Krasznokvajda' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.4705256, 'lng' => 20.9714153],
				],
				'Kupa' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3316226, 'lng' => 20.9145594],
				],
				'Kurityán' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.310505, 'lng' => 20.62573],
				],
				'Lácacséke' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3664002, 'lng' => 21.9934562],
				],
				'Ládbesenyő' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3432268, 'lng' => 20.7859308],
				],
				'Lak' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3480907, 'lng' => 20.8662135],
				],
				'Legyesbénye' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.1564545, 'lng' => 21.1530692],
				],
				'Léh' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2906948, 'lng' => 20.9807054],
				],
				'Lénárddaróc' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.1486722, 'lng' => 20.3728301],
				],
				'Litka' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.4544802, 'lng' => 21.0584273],
				],
				'Mád' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.1922445, 'lng' => 21.2759773],
				],
				'Makkoshotyka' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3571928, 'lng' => 21.5164187],
				],
				'Mályi' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 48.0175678, 'lng' => 20.8292414],
				],
				'Mályinka' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.1545567, 'lng' => 20.4958901],
				],
				'Martonyi' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.4702379, 'lng' => 20.7660532],
				],
				'Megyaszó' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.1875185, 'lng' => 21.0547033],
				],
				'Méra' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3565901, 'lng' => 21.1469291],
				],
				'Meszes' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.438651, 'lng' => 20.7950688],
				],
				'Mezőcsát' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.8207081, 'lng' => 20.9051607],
				],
				'Mezőkeresztes' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.8262301, 'lng' => 20.6884043],
				],
				'Mezőkövesd' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.8074617, 'lng' => 20.5698525],
				],
				'Mezőnagymihály' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.8062776, 'lng' => 20.7308177],
				],
				'Mezőnyárád' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.8585625, 'lng' => 20.6764688],
				],
				'Mezőzombor' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.1501209, 'lng' => 21.2575954],
				],
				'Mikóháza' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4617944, 'lng' => 21.592572],
				],
				'Miskolc' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 1.', 'Borsod-Abaúj-Zemplén 2.'],
					'coordinates' => ['lat' => 48.1034775, 'lng' => 20.7784384],
				],
				'Mogyoróska' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3759799, 'lng' => 21.3296401],
				],
				'Monaj' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3061021, 'lng' => 20.9348205],
				],
				'Monok' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.2099439, 'lng' => 21.149252],
				],
				'Múcsony' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2758139, 'lng' => 20.6716209],
				],
				'Muhi' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.9778997, 'lng' => 20.9293321],
				],
				'Nagybarca' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2476865, 'lng' => 20.5280319],
				],
				'Nagycsécs' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.9601505, 'lng' => 20.9482798],
				],
				'Nagyhuta' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4290026, 'lng' => 21.492424],
				],
				'Nagykinizs' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.2344766, 'lng' => 21.0335706],
				],
				'Nagyrozvágy' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3404683, 'lng' => 21.9228458],
				],
				'Négyes' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.7013, 'lng' => 20.7040224],
				],
				'Nekézseny' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.1689694, 'lng' => 20.4291357],
				],
				'Nemesbikk' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.8876867, 'lng' => 20.9661155],
				],
				'Novajidrány' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.396674, 'lng' => 21.1688256],
				],
				'Nyékládháza' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.9933002, 'lng' => 20.8429935],
				],
				'Nyésta' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3702622, 'lng' => 20.9514276],
				],
				'Nyíri' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4986982, 'lng' => 21.440883],
				],
				'Nyomár' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.275559, 'lng' => 20.8198353],
				],
				'Olaszliszka' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.2419377, 'lng' => 21.4279754],
				],
				'Onga' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.1194769, 'lng' => 20.9065655],
				],
				'Ónod' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 48.0024425, 'lng' => 20.9146535],
				],
				'Ormosbánya' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.3322064, 'lng' => 20.6493181],
				],
				'Oszlár' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 47.8740321, 'lng' => 21.0332202],
				],
				'Ózd' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.2241439, 'lng' => 20.2888698],
				],
				'Pácin' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3306334, 'lng' => 21.8337743],
				],
				'Pálháza' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4717353, 'lng' => 21.507078],
				],
				'Pamlény' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.493024, 'lng' => 20.9282949],
				],
				'Pányok' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.5298401, 'lng' => 21.3478472],
				],
				'Parasznya' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 2.'],
					'coordinates' => ['lat' => 48.1688229, 'lng' => 20.6402064],
				],
				'Pere' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.2845544, 'lng' => 21.1211586],
				],
				'Perecse' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.5027869, 'lng' => 20.9845634],
				],
				'Perkupa' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.4712725, 'lng' => 20.6862819],
				],
				'Prügy' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.0824191, 'lng' => 21.2428751],
				],
				'Pusztafalu' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.5439277, 'lng' => 21.4860599],
				],
				'Pusztaradvány' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4679248, 'lng' => 21.1338715],
				],
				'Putnok' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.2939007, 'lng' => 20.4333508],
				],
				'Radostyán' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 2.'],
					'coordinates' => ['lat' => 48.1787774, 'lng' => 20.6532017],
				],
				'Ragály' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.4041753, 'lng' => 20.5211463],
				],
				'Rakaca' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.4617206, 'lng' => 20.8848555],
				],
				'Rakacaszend' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.4611034, 'lng' => 20.8378744],
				],
				'Rásonysápberencs' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.304802, 'lng' => 20.9934828],
				],
				'Rátka' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.2156932, 'lng' => 21.2267141],
				],
				'Regéc' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.392191, 'lng' => 21.3436481],
				],
				'Répáshuta' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 48.0507939, 'lng' => 20.5254934],
				],
				'Révleányvár' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3230427, 'lng' => 22.0416695],
				],
				'Ricse' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3251432, 'lng' => 21.9687588],
				],
				'Rudabánya' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.3747405, 'lng' => 20.6206118],
				],
				'Rudolftelep' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3092868, 'lng' => 20.6711602],
				],
				'Sajóbábony' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 2.'],
					'coordinates' => ['lat' => 48.1742691, 'lng' => 20.734572],
				],
				'Sajóecseg' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.190065, 'lng' => 20.772827],
				],
				'Sajógalgóc' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.2929878, 'lng' => 20.5323886],
				],
				'Sajóhídvég' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 48.0026817, 'lng' => 20.9495863],
				],
				'Sajóivánka' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2654174, 'lng' => 20.5799268],
				],
				'Sajókápolna' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.1952827, 'lng' => 20.6848853],
				],
				'Sajókaza' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.2864119, 'lng' => 20.5851277],
				],
				'Sajókeresztúr' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 1.'],
					'coordinates' => ['lat' => 48.1694996, 'lng' => 20.7768886],
				],
				'Sajólád' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 48.0402765, 'lng' => 20.9024513],
				],
				'Sajólászlófalva' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 2.'],
					'coordinates' => ['lat' => 48.1848765, 'lng' => 20.6736002],
				],
				'Sajómercse' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.2461305, 'lng' => 20.414773],
				],
				'Sajónémeti' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.270659, 'lng' => 20.3811845],
				],
				'Sajóörös' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 47.9515653, 'lng' => 21.0219599],
				],
				'Sajópálfala' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.163139, 'lng' => 20.8458093],
				],
				'Sajópetri' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 48.0351497, 'lng' => 20.8878767],
				],
				'Sajópüspöki' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.280186, 'lng' => 20.3400614],
				],
				'Sajósenye' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.1960682, 'lng' => 20.8185281],
				],
				'Sajószentpéter' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2188772, 'lng' => 20.7092248],
				],
				'Sajószöged' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 47.9458004, 'lng' => 20.9946112],
				],
				'Sajóvámos' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.1802021, 'lng' => 20.8298154],
				],
				'Sajóvelezd' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.2714818, 'lng' => 20.4593985],
				],
				'Sály' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.9527979, 'lng' => 20.6597197],
				],
				'Sárazsadány' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.2684871, 'lng' => 21.497789],
				],
				'Sárospatak' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3196929, 'lng' => 21.5687308],
				],
				'Sáta' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.1876567, 'lng' => 20.3914051],
				],
				'Sátoraljaújhely' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3960601, 'lng' => 21.6551122],
				],
				'Selyeb' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3381582, 'lng' => 20.9541317],
				],
				'Semjén' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3521396, 'lng' => 21.9671011],
				],
				'Serényfalva' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.3071589, 'lng' => 20.3852844],
				],
				'Sima' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.2996969, 'lng' => 21.3030527],
				],
				'Sóstófalva' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.156243, 'lng' => 20.9870638],
				],
				'Szakácsi' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3820531, 'lng' => 20.8614571],
				],
				'Szakáld' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.9431182, 'lng' => 20.908997],
				],
				'Szalaszend' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3859709, 'lng' => 21.1243501],
				],
				'Szalonna' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.4500484, 'lng' => 20.7394926],
				],
				'Szászfa' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.4704359, 'lng' => 20.9418168],
				],
				'Szegi' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.1953737, 'lng' => 21.3795562],
				],
				'Szegilong' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.2162488, 'lng' => 21.3965639],
				],
				'Szemere' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4661495, 'lng' => 21.099542],
				],
				'Szendrő' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.4046962, 'lng' => 20.7282046],
				],
				'Szendrőlád' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3433366, 'lng' => 20.7419436],
				],
				'Szentistván' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.7737632, 'lng' => 20.6579694],
				],
				'Szentistvánbaksa' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.2227558, 'lng' => 21.0276456],
				],
				'Szerencs' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.1590429, 'lng' => 21.2048872],
				],
				'Szikszó' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.1989312, 'lng' => 20.9298039],
				],
				'Szin' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.4972791, 'lng' => 20.6601922],
				],
				'Szinpetri' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.4847097, 'lng' => 20.625043],
				],
				'Szirmabesenyő' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 1.'],
					'coordinates' => ['lat' => 48.1509585, 'lng' => 20.7957903],
				],
				'Szögliget' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.5215045, 'lng' => 20.6770697],
				],
				'Szőlősardó' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.443484, 'lng' => 20.6278686],
				],
				'Szomolya' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.8919105, 'lng' => 20.4949334],
				],
				'Szuhafő' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.4082703, 'lng' => 20.4515974],
				],
				'Szuhakálló' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2835218, 'lng' => 20.6523991],
				],
				'Szuhogy' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.3842029, 'lng' => 20.6731282],
				],
				'Taktabáj' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.0621903, 'lng' => 21.3112131],
				],
				'Taktaharkány' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.0876121, 'lng' => 21.129918],
				],
				'Taktakenéz' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.0508677, 'lng' => 21.2167146],
				],
				'Taktaszada' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.1103437, 'lng' => 21.1735733],
				],
				'Tállya' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.2352295, 'lng' => 21.2260996],
				],
				'Tarcal' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.1311328, 'lng' => 21.3418021],
				],
				'Tard' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.8784711, 'lng' => 20.598937],
				],
				'Tardona' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.1699442, 'lng' => 20.531454],
				],
				'Telkibánya' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4854061, 'lng' => 21.3574907],
				],
				'Teresztenye' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.4463436, 'lng' => 20.6031689],
				],
				'Tibolddaróc' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.9206758, 'lng' => 20.6355357],
				],
				'Tiszabábolna' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.689752, 'lng' => 20.813906],
				],
				'Tiszacsermely' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.2336812, 'lng' => 21.7945686],
				],
				'Tiszadorogma' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.6839826, 'lng' => 20.8661184],
				],
				'Tiszakarád' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.2061184, 'lng' => 21.7213149],
				],
				'Tiszakeszi' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.7879554, 'lng' => 20.9904672],
				],
				'Tiszaladány' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.0621067, 'lng' => 21.4101619],
				],
				'Tiszalúc' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.0358262, 'lng' => 21.0648204],
				],
				'Tiszapalkonya' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 47.8849204, 'lng' => 21.0557818],
				],
				'Tiszatardos' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.0406385, 'lng' => 21.379655],
				],
				'Tiszatarján' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.8329217, 'lng' => 21.0014346],
				],
				'Tiszaújváros' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 47.9159846, 'lng' => 21.0427447],
				],
				'Tiszavalk' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.6888504, 'lng' => 20.751499],
				],
				'Tokaj' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.1172148, 'lng' => 21.4089015],
				],
				'Tolcsva' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.2841513, 'lng' => 21.4488452],
				],
				'Tomor' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.3258904, 'lng' => 20.8823733],
				],
				'Tornabarakony' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.4922432, 'lng' => 20.8192157],
				],
				'Tornakápolna' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.4616855, 'lng' => 20.617706],
				],
				'Tornanádaska' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.5611186, 'lng' => 20.7846392],
				],
				'Tornaszentandrás' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.5226438, 'lng' => 20.7790226],
				],
				'Tornaszentjakab' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.5244312, 'lng' => 20.8729813],
				],
				'Tornyosnémeti' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.5202757, 'lng' => 21.2506927],
				],
				'Trizs' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.4251253, 'lng' => 20.4958645],
				],
				'Újcsanálos' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 6.'],
					'coordinates' => ['lat' => 48.1380468, 'lng' => 21.0036907],
				],
				'Uppony' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.2155013, 'lng' => 20.434654],
				],
				'Vadna' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2733247, 'lng' => 20.5552218],
				],
				'Vágáshuta' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4264605, 'lng' => 21.545222],
				],
				'Vajdácska' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3196383, 'lng' => 21.6541401],
				],
				'Vámosújfalu' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.2575496, 'lng' => 21.4524394],
				],
				'Varbó' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 2.'],
					'coordinates' => ['lat' => 48.1631678, 'lng' => 20.6217693],
				],
				'Varbóc' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.4644075, 'lng' => 20.6450152],
				],
				'Vatta' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 7.'],
					'coordinates' => ['lat' => 47.9228447, 'lng' => 20.7389995],
				],
				'Vilmány' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4166062, 'lng' => 21.2302229],
				],
				'Vilyvitány' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4952223, 'lng' => 21.5589737],
				],
				'Viss' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.2176861, 'lng' => 21.5069652],
				],
				'Viszló' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.4939386, 'lng' => 20.8862569],
				],
				'Vizsoly' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.3845496, 'lng' => 21.2158416],
				],
				'Zádorfalva' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.3860789, 'lng' => 20.4852484],
				],
				'Zalkod' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.1857296, 'lng' => 21.4592752],
				],
				'Zemplénagárd' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.36024, 'lng' => 22.0709646],
				],
				'Ziliz' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 4.'],
					'coordinates' => ['lat' => 48.2511796, 'lng' => 20.7922106],
				],
				'Zsujta' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 5.'],
					'coordinates' => ['lat' => 48.4997896, 'lng' => 21.2789138],
				],
				'Zubogy' => [
					'constituencies' => ['Borsod-Abaúj-Zemplén 3.'],
					'coordinates' => ['lat' => 48.3792388, 'lng' => 20.5758141],
				],
			],
			'Budapest' => [
				'Budapest I. ker.' => [
					'constituencies' => ['Budapest 01.'],
					'coordinates' => ['lat' => 47.4968219, 'lng' => 19.037458],
				],
				'Budapest II. ker.' => [
					'constituencies' => ['Budapest 03.', 'Budapest 04.'],
					'coordinates' => ['lat' => 47.5393329, 'lng' => 18.986934],
				],
				'Budapest III. ker.' => [
					'constituencies' => ['Budapest 04.', 'Budapest 10.'],
					'coordinates' => ['lat' => 47.5671768, 'lng' => 19.0368517],
				],
				'Budapest IV. ker.' => [
					'constituencies' => ['Budapest 11.', 'Budapest 12.'],
					'coordinates' => ['lat' => 47.5648915, 'lng' => 19.0913149],
				],
				'Budapest V. ker.' => [
					'constituencies' => ['Budapest 01.'],
					'coordinates' => ['lat' => 47.5002319, 'lng' => 19.0520181],
				],
				'Budapest VI. ker.' => [
					'constituencies' => ['Budapest 05.'],
					'coordinates' => ['lat' => 47.509863, 'lng' => 19.0625813],
				],
				'Budapest VII. ker.' => [
					'constituencies' => ['Budapest 05.'],
					'coordinates' => ['lat' => 47.5027289, 'lng' => 19.073376],
				],
				'Budapest VIII. ker.' => [
					'constituencies' => ['Budapest 01.', 'Budapest 06.'],
					'coordinates' => ['lat' => 47.4894184, 'lng' => 19.070668],
				],
				'Budapest IX. ker.' => [
					'constituencies' => ['Budapest 01.', 'Budapest 06.'],
					'coordinates' => ['lat' => 47.4649279, 'lng' => 19.0916229],
				],
				'Budapest X. ker.' => [
					'constituencies' => ['Budapest 09.', 'Budapest 14.'],
					'coordinates' => ['lat' => 47.4820909, 'lng' => 19.1575028],
				],
				'Budapest XI. ker.' => [
					'constituencies' => ['Budapest 02.', 'Budapest 18.'],
					'coordinates' => ['lat' => 47.4593099, 'lng' => 19.0187389],
				],
				'Budapest XII. ker.' => [
					'constituencies' => ['Budapest 03.'],
					'coordinates' => ['lat' => 47.4991199, 'lng' => 18.990459],
				],
				'Budapest XIII. ker.' => [
					'constituencies' => ['Budapest 11.', 'Budapest 07.'],
					'coordinates' => ['lat' => 47.5355105, 'lng' => 19.0709266],
				],
				'Budapest XIV. ker.' => [
					'constituencies' => ['Budapest 08.', 'Budapest 13.'],
					'coordinates' => ['lat' => 47.5224569, 'lng' => 19.114709],
				],
				'Budapest XV. ker.' => [
					'constituencies' => ['Budapest 12.'],
					'coordinates' => ['lat' => 47.5589, 'lng' => 19.1193],
				],
				'Budapest XVI. ker.' => [
					'constituencies' => ['Budapest 13.'],
					'coordinates' => ['lat' => 47.5183029, 'lng' => 19.191941],
				],
				'Budapest XVII. ker.' => [
					'constituencies' => ['Budapest 14.'],
					'coordinates' => ['lat' => 47.4803, 'lng' => 19.2667001],
				],
				'Budapest XVIII. ker.' => [
					'constituencies' => ['Budapest 15.'],
					'coordinates' => ['lat' => 47.4281229, 'lng' => 19.2098429],
				],
				'Budapest XIX. ker.' => [
					'constituencies' => ['Budapest 09.', 'Budapest 16.'],
					'coordinates' => ['lat' => 47.4457289, 'lng' => 19.1430149],
				],
				'Budapest XX. ker.' => [
					'constituencies' => ['Budapest 16.'],
					'coordinates' => ['lat' => 47.4332879, 'lng' => 19.1193169],
				],
				'Budapest XXI. ker.' => [
					'constituencies' => ['Budapest 17.'],
					'coordinates' => ['lat' => 47.4243579, 'lng' => 19.066142],
				],
				'Budapest XXII. ker.' => [
					'constituencies' => ['Budapest 18.'],
					'coordinates' => ['lat' => 47.425, 'lng' => 19.031667],
				],
				'Budapest XXIII. ker.' => [
					'constituencies' => ['Budapest 17.'],
					'coordinates' => ['lat' => 47.3939599, 'lng' => 19.122523],
				],
			],
			'Csongrád-Csanád' => [
				'Algyő' => [
					'constituencies' => ['Csongrád-Csanád 4.'],
					'coordinates' => ['lat' => 46.3329625, 'lng' => 20.207889],
				],
				'Ambrózfalva' => [
					'constituencies' => ['Csongrád-Csanád 4.'],
					'coordinates' => ['lat' => 46.3501417, 'lng' => 20.7313995],
				],
				'Apátfalva' => [
					'constituencies' => ['Csongrád-Csanád 4.'],
					'coordinates' => ['lat' => 46.173317, 'lng' => 20.5800472],
				],
				'Árpádhalom' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.6158286, 'lng' => 20.547733],
				],
				'Ásotthalom' => [
					'constituencies' => ['Csongrád-Csanád 2.'],
					'coordinates' => ['lat' => 46.1995983, 'lng' => 19.7833756],
				],
				'Baks' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.5518708, 'lng' => 20.1064166],
				],
				'Balástya' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.4261828, 'lng' => 20.004933],
				],
				'Bordány' => [
					'constituencies' => ['Csongrád-Csanád 2.'],
					'coordinates' => ['lat' => 46.3194213, 'lng' => 19.9227063],
				],
				'Csanádalberti' => [
					'constituencies' => ['Csongrád-Csanád 4.'],
					'coordinates' => ['lat' => 46.3267872, 'lng' => 20.7068631],
				],
				'Csanádpalota' => [
					'constituencies' => ['Csongrád-Csanád 4.'],
					'coordinates' => ['lat' => 46.2407708, 'lng' => 20.7228873],
				],
				'Csanytelek' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.6014883, 'lng' => 20.1114379],
				],
				'Csengele' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.5411505, 'lng' => 19.8644533],
				],
				'Csongrád-Csanád' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.7084264, 'lng' => 20.1436061],
				],
				'Derekegyház' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.580238, 'lng' => 20.3549845],
				],
				'Deszk' => [
					'constituencies' => ['Csongrád-Csanád 1.'],
					'coordinates' => ['lat' => 46.2179603, 'lng' => 20.2404106],
				],
				'Dóc' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.437292, 'lng' => 20.1363129],
				],
				'Domaszék' => [
					'constituencies' => ['Csongrád-Csanád 2.'],
					'coordinates' => ['lat' => 46.2466283, 'lng' => 19.9990365],
				],
				'Eperjes' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.7076258, 'lng' => 20.5621489],
				],
				'Fábiánsebestyén' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.6748615, 'lng' => 20.455037],
				],
				'Felgyő' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.6616513, 'lng' => 20.1097394],
				],
				'Ferencszállás' => [
					'constituencies' => ['Csongrád-Csanád 1.'],
					'coordinates' => ['lat' => 46.2158295, 'lng' => 20.3553359],
				],
				'Földeák' => [
					'constituencies' => ['Csongrád-Csanád 4.'],
					'coordinates' => ['lat' => 46.3184223, 'lng' => 20.4929019],
				],
				'Forráskút' => [
					'constituencies' => ['Csongrád-Csanád 2.'],
					'coordinates' => ['lat' => 46.3655956, 'lng' => 19.9089055],
				],
				'Hódmezővásárhely' => [
					'constituencies' => ['Csongrád-Csanád 4.'],
					'coordinates' => ['lat' => 46.4181262, 'lng' => 20.3300315],
				],
				'Királyhegyes' => [
					'constituencies' => ['Csongrád-Csanád 4.'],
					'coordinates' => ['lat' => 46.2717114, 'lng' => 20.6126302],
				],
				'Kistelek' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.4694781, 'lng' => 19.9804365],
				],
				'Kiszombor' => [
					'constituencies' => ['Csongrád-Csanád 4.'],
					'coordinates' => ['lat' => 46.1856953, 'lng' => 20.4265486],
				],
				'Klárafalva' => [
					'constituencies' => ['Csongrád-Csanád 1.'],
					'coordinates' => ['lat' => 46.220953, 'lng' => 20.3255224],
				],
				'Kövegy' => [
					'constituencies' => ['Csongrád-Csanád 4.'],
					'coordinates' => ['lat' => 46.2246141, 'lng' => 20.6840764],
				],
				'Kübekháza' => [
					'constituencies' => ['Csongrád-Csanád 1.'],
					'coordinates' => ['lat' => 46.1500892, 'lng' => 20.276983],
				],
				'Magyarcsanád' => [
					'constituencies' => ['Csongrád-Csanád 4.'],
					'coordinates' => ['lat' => 46.1698824, 'lng' => 20.6132706],
				],
				'Makó' => [
					'constituencies' => ['Csongrád-Csanád 4.'],
					'coordinates' => ['lat' => 46.2219071, 'lng' => 20.4809265],
				],
				'Maroslele' => [
					'constituencies' => ['Csongrád-Csanád 4.'],
					'coordinates' => ['lat' => 46.2698362, 'lng' => 20.3418589],
				],
				'Mártély' => [
					'constituencies' => ['Csongrád-Csanád 4.'],
					'coordinates' => ['lat' => 46.4682451, 'lng' => 20.2416146],
				],
				'Mindszent' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.5227585, 'lng' => 20.1895798],
				],
				'Mórahalom' => [
					'constituencies' => ['Csongrád-Csanád 2.'],
					'coordinates' => ['lat' => 46.2179218, 'lng' => 19.88372],
				],
				'Nagyér' => [
					'constituencies' => ['Csongrád-Csanád 4.'],
					'coordinates' => ['lat' => 46.3703008, 'lng' => 20.729605],
				],
				'Nagylak' => [
					'constituencies' => ['Csongrád-Csanád 4.'],
					'coordinates' => ['lat' => 46.1737713, 'lng' => 20.7111982],
				],
				'Nagymágocs' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.5857132, 'lng' => 20.4833875],
				],
				'Nagytőke' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.7552639, 'lng' => 20.2860999],
				],
				'Óföldeák' => [
					'constituencies' => ['Csongrád-Csanád 4.'],
					'coordinates' => ['lat' => 46.2985957, 'lng' => 20.4369086],
				],
				'Ópusztaszer' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.4957061, 'lng' => 20.0665358],
				],
				'Öttömös' => [
					'constituencies' => ['Csongrád-Csanád 2.'],
					'coordinates' => ['lat' => 46.2808756, 'lng' => 19.6826038],
				],
				'Pitvaros' => [
					'constituencies' => ['Csongrád-Csanád 4.'],
					'coordinates' => ['lat' => 46.3194853, 'lng' => 20.7385996],
				],
				'Pusztamérges' => [
					'constituencies' => ['Csongrád-Csanád 2.'],
					'coordinates' => ['lat' => 46.3280134, 'lng' => 19.6849699],
				],
				'Pusztaszer' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.5515959, 'lng' => 19.9870098],
				],
				'Röszke' => [
					'constituencies' => ['Csongrád-Csanád 2.'],
					'coordinates' => ['lat' => 46.1873773, 'lng' => 20.037455],
				],
				'Ruzsa' => [
					'constituencies' => ['Csongrád-Csanád 2.'],
					'coordinates' => ['lat' => 46.2890678, 'lng' => 19.7481121],
				],
				'Sándorfalva' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.3635951, 'lng' => 20.1032227],
				],
				'Szatymaz' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.3426558, 'lng' => 20.0391941],
				],
				'Szeged' => [
					'constituencies' => ['Csongrád-Csanád 2.', 'Csongrád-Csanád 1.'],
					'coordinates' => ['lat' => 46.2530102, 'lng' => 20.1414253],
				],
				'Szegvár' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.5816447, 'lng' => 20.2266415],
				],
				'Székkutas' => [
					'constituencies' => ['Csongrád-Csanád 4.'],
					'coordinates' => ['lat' => 46.5063976, 'lng' => 20.537673],
				],
				'Szentes' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.654789, 'lng' => 20.2637492],
				],
				'Tiszasziget' => [
					'constituencies' => ['Csongrád-Csanád 1.'],
					'coordinates' => ['lat' => 46.1720458, 'lng' => 20.1618289],
				],
				'Tömörkény' => [
					'constituencies' => ['Csongrád-Csanád 3.'],
					'coordinates' => ['lat' => 46.6166243, 'lng' => 20.0436896],
				],
				'Újszentiván' => [
					'constituencies' => ['Csongrád-Csanád 1.'],
					'coordinates' => ['lat' => 46.1859286, 'lng' => 20.1835123],
				],
				'Üllés' => [
					'constituencies' => ['Csongrád-Csanád 2.'],
					'coordinates' => ['lat' => 46.3355015, 'lng' => 19.8489644],
				],
				'Zákányszék' => [
					'constituencies' => ['Csongrád-Csanád 2.'],
					'coordinates' => ['lat' => 46.2752726, 'lng' => 19.8883111],
				],
				'Zsombó' => [
					'constituencies' => ['Csongrád-Csanád 2.'],
					'coordinates' => ['lat' => 46.3284014, 'lng' => 19.9766186],
				],
			],
			'Fejér' => [
				'Aba' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 47.0328193, 'lng' => 18.522359],
				],
				'Adony' => [
					'constituencies' => ['Fejér 4.'],
					'coordinates' => ['lat' => 47.119831, 'lng' => 18.8612469],
				],
				'Alap' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.8075763, 'lng' => 18.684028],
				],
				'Alcsútdoboz' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.4277067, 'lng' => 18.6030325],
				],
				'Alsószentiván' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.7910573, 'lng' => 18.732161],
				],
				'Bakonycsernye' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.321719, 'lng' => 18.0907379],
				],
				'Bakonykúti' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.2458464, 'lng' => 18.195769],
				],
				'Balinka' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.3135736, 'lng' => 18.1907168],
				],
				'Baracs' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.9049033, 'lng' => 18.8752931],
				],
				'Baracska' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.2824737, 'lng' => 18.7598901],
				],
				'Beloiannisz' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.183143, 'lng' => 18.8245727],
				],
				'Besnyő' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.1892568, 'lng' => 18.7936832],
				],
				'Bicske' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.4911792, 'lng' => 18.6370142],
				],
				'Bodajk' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.3209663, 'lng' => 18.2339242],
				],
				'Bodmér' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.4489857, 'lng' => 18.5383832],
				],
				'Cece' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.7698199, 'lng' => 18.6336808],
				],
				'Csabdi' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.5229299, 'lng' => 18.6085371],
				],
				'Csákberény' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.3506861, 'lng' => 18.3265064],
				],
				'Csákvár' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.3941468, 'lng' => 18.4602445],
				],
				'Csókakő' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.3533961, 'lng' => 18.2693867],
				],
				'Csór' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.2049913, 'lng' => 18.2557813],
				],
				'Csősz' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 47.0382791, 'lng' => 18.414533],
				],
				'Daruszentmiklós' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.87194, 'lng' => 18.8568642],
				],
				'Dég' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.8707664, 'lng' => 18.4445717],
				],
				'Dunaújváros' => [
					'constituencies' => ['Fejér 4.'],
					'coordinates' => ['lat' => 46.9619059, 'lng' => 18.9355227],
				],
				'Előszállás' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.8276091, 'lng' => 18.8280627],
				],
				'Enying' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.9326943, 'lng' => 18.2414807],
				],
				'Ercsi' => [
					'constituencies' => ['Fejér 4.'],
					'coordinates' => ['lat' => 47.2482238, 'lng' => 18.8912626],
				],
				'Etyek' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.4467098, 'lng' => 18.751179],
				],
				'Fehérvárcsurgó' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.2904264, 'lng' => 18.2645262],
				],
				'Felcsút' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.4541851, 'lng' => 18.5865775],
				],
				'Füle' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.0535367, 'lng' => 18.2480871],
				],
				'Gánt' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.3902121, 'lng' => 18.387061],
				],
				'Gárdony' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.196537, 'lng' => 18.6115195],
				],
				'Gyúró' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.3700577, 'lng' => 18.7384824],
				],
				'Hantos' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.9943127, 'lng' => 18.6989263],
				],
				'Igar' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.7757642, 'lng' => 18.5137348],
				],
				'Iszkaszentgyörgy' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.2399338, 'lng' => 18.2987232],
				],
				'Isztimér' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.2787058, 'lng' => 18.1955966],
				],
				'Iváncsa' => [
					'constituencies' => ['Fejér 4.'],
					'coordinates' => ['lat' => 47.153376, 'lng' => 18.8270434],
				],
				'Jenő' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.1047531, 'lng' => 18.2453199],
				],
				'Kajászó' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.3234883, 'lng' => 18.7221054],
				],
				'Káloz' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.9568415, 'lng' => 18.4853961],
				],
				'Kápolnásnyék' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.2398554, 'lng' => 18.6764288],
				],
				'Kincsesbánya' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.2632477, 'lng' => 18.2764679],
				],
				'Kisapostag' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.8940766, 'lng' => 18.9323135],
				],
				'Kisláng' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.9598173, 'lng' => 18.3860884],
				],
				'Kőszárhegy' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.0926048, 'lng' => 18.341234],
				],
				'Kulcs' => [
					'constituencies' => ['Fejér 4.'],
					'coordinates' => ['lat' => 47.0541246, 'lng' => 18.9197178],
				],
				'Lajoskomárom' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.841585, 'lng' => 18.3355393],
				],
				'Lepsény' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.9918514, 'lng' => 18.2469618],
				],
				'Lovasberény' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.3109278, 'lng' => 18.5527924],
				],
				'Magyaralmás' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.2913027, 'lng' => 18.3245512],
				],
				'Mány' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.5321762, 'lng' => 18.6555811],
				],
				'Martonvásár' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.3164516, 'lng' => 18.7877558],
				],
				'Mátyásdomb' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.9228626, 'lng' => 18.3470929],
				],
				'Mezőfalva' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.9323938, 'lng' => 18.7771045],
				],
				'Mezőkomárom' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.8276482, 'lng' => 18.2934472],
				],
				'Mezőszentgyörgy' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.9920267, 'lng' => 18.2795568],
				],
				'Mezőszilas' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.8166957, 'lng' => 18.4754679],
				],
				'Moha' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.2437717, 'lng' => 18.3313907],
				],
				'Mór' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.374928, 'lng' => 18.2036035],
				],
				'Nadap' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.2585056, 'lng' => 18.6167437],
				],
				'Nádasdladány' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.1341786, 'lng' => 18.2394077],
				],
				'Nagykarácsony' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.8706425, 'lng' => 18.7725518],
				],
				'Nagylók' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.9764964, 'lng' => 18.64115],
				],
				'Nagyveleg' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.361797, 'lng' => 18.111061],
				],
				'Nagyvenyim' => [
					'constituencies' => ['Fejér 4.'],
					'coordinates' => ['lat' => 46.9571015, 'lng' => 18.8576229],
				],
				'Óbarok' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.4922397, 'lng' => 18.5681206],
				],
				'Pákozd' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.2172004, 'lng' => 18.5430768],
				],
				'Pátka' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.2752462, 'lng' => 18.4950339],
				],
				'Pázmánd' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.283645, 'lng' => 18.654854],
				],
				'Perkáta' => [
					'constituencies' => ['Fejér 4.'],
					'coordinates' => ['lat' => 47.0482285, 'lng' => 18.784294],
				],
				'Polgárdi' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.0601257, 'lng' => 18.2993645],
				],
				'Pusztaszabolcs' => [
					'constituencies' => ['Fejér 4.'],
					'coordinates' => ['lat' => 47.1408918, 'lng' => 18.7601638],
				],
				'Pusztavám' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.4297438, 'lng' => 18.2317401],
				],
				'Rácalmás' => [
					'constituencies' => ['Fejér 4.'],
					'coordinates' => ['lat' => 47.0243223, 'lng' => 18.9350709],
				],
				'Ráckeresztúr' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.2729155, 'lng' => 18.8330106],
				],
				'Sárbogárd' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.879104, 'lng' => 18.6213353],
				],
				'Sáregres' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.783236, 'lng' => 18.5935136],
				],
				'Sárkeresztes' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.2517488, 'lng' => 18.3541822],
				],
				'Sárkeresztúr' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 47.0025252, 'lng' => 18.5479461],
				],
				'Sárkeszi' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.1582764, 'lng' => 18.284968],
				],
				'Sárosd' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 47.0414738, 'lng' => 18.6488144],
				],
				'Sárszentágota' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.9706742, 'lng' => 18.5634969],
				],
				'Sárszentmihály' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.1537282, 'lng' => 18.3235014],
				],
				'Seregélyes' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 47.1100586, 'lng' => 18.5788431],
				],
				'Soponya' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 47.0120427, 'lng' => 18.4543505],
				],
				'Söréd' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.322683, 'lng' => 18.280508],
				],
				'Sukoró' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.2425436, 'lng' => 18.6022803],
				],
				'Szabadbattyán' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.1175572, 'lng' => 18.3681061],
				],
				'Szabadegyháza' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 47.0770131, 'lng' => 18.6912379],
				],
				'Szabadhídvég' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.8210159, 'lng' => 18.2798938],
				],
				'Szár' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.4791911, 'lng' => 18.5158147],
				],
				'Székesfehérvár' => [
					'constituencies' => ['Fejér 2.', 'Fejér 1.'],
					'coordinates' => ['lat' => 47.1860262, 'lng' => 18.4221358],
				],
				'Tabajd' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.4045316, 'lng' => 18.6302011],
				],
				'Tác' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 47.0794264, 'lng' => 18.403381],
				],
				'Tordas' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.3440943, 'lng' => 18.7483302],
				],
				'Újbarok' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.4791337, 'lng' => 18.5585574],
				],
				'Úrhida' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.1298384, 'lng' => 18.3321437],
				],
				'Vajta' => [
					'constituencies' => ['Fejér 5.'],
					'coordinates' => ['lat' => 46.7227758, 'lng' => 18.6618091],
				],
				'Vál' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.3624339, 'lng' => 18.6766737],
				],
				'Velence' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.2300924, 'lng' => 18.6506424],
				],
				'Vereb' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.318485, 'lng' => 18.6197301],
				],
				'Vértesacsa' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.3700218, 'lng' => 18.5792793],
				],
				'Vértesboglár' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.4291347, 'lng' => 18.5235823],
				],
				'Zámoly' => [
					'constituencies' => ['Fejér 2.'],
					'coordinates' => ['lat' => 47.3168103, 'lng' => 18.408371],
				],
				'Zichyújfalu' => [
					'constituencies' => ['Fejér 3.'],
					'coordinates' => ['lat' => 47.1291991, 'lng' => 18.6692222],
				],
			],
			'Győr-Moson-Sopron' => [
				'Abda' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.6962149, 'lng' => 17.5445786],
				],
				'Acsalag' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.676095, 'lng' => 17.1977771],
				],
				'Ágfalva' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.688862, 'lng' => 16.5110233],
				],
				'Agyagosszergény' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.608545, 'lng' => 16.9409912],
				],
				'Árpás' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5134127, 'lng' => 17.3931579],
				],
				'Ásványráró' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.8287695, 'lng' => 17.499195],
				],
				'Babót' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5752269, 'lng' => 17.0758604],
				],
				'Bágyogszovát' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5866036, 'lng' => 17.3617273],
				],
				'Bakonygyirót' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.4181388, 'lng' => 17.8055502],
				],
				'Bakonypéterd' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.4667076, 'lng' => 17.7967619],
				],
				'Bakonyszentlászló' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.3892006, 'lng' => 17.8032754],
				],
				'Barbacs' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.6455476, 'lng' => 17.297216],
				],
				'Beled' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.4662675, 'lng' => 17.0959263],
				],
				'Bezenye' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.9609867, 'lng' => 17.216211],
				],
				'Bezi' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.6737572, 'lng' => 17.3921093],
				],
				'Bodonhely' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5655752, 'lng' => 17.4072124],
				],
				'Bogyoszló' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5609657, 'lng' => 17.1850606],
				],
				'Bőny' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.6516279, 'lng' => 17.8703841],
				],
				'Börcs' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.6862052, 'lng' => 17.4988893],
				],
				'Bősárkány' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.6881947, 'lng' => 17.2507143],
				],
				'Cakóháza' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.6967121, 'lng' => 17.2863758],
				],
				'Cirák' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.4779219, 'lng' => 17.0282338],
				],
				'Csáfordjánosfa' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.4151998, 'lng' => 16.9510595],
				],
				'Csapod' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5162077, 'lng' => 16.9234546],
				],
				'Csér' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.4169765, 'lng' => 16.9330737],
				],
				'Csikvánd' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.4666335, 'lng' => 17.4546305],
				],
				'Csorna' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.6103234, 'lng' => 17.2462444],
				],
				'Darnózseli' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.8493957, 'lng' => 17.4273958],
				],
				'Dénesfa' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.4558445, 'lng' => 17.0335351],
				],
				'Dör' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5979168, 'lng' => 17.2991911],
				],
				'Dunakiliti' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.9659588, 'lng' => 17.2882641],
				],
				'Dunaremete' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.8761957, 'lng' => 17.4375005],
				],
				'Dunaszeg' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.7692554, 'lng' => 17.5407805],
				],
				'Dunaszentpál' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.7771623, 'lng' => 17.5043978],
				],
				'Dunasziget' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.9359671, 'lng' => 17.3617867],
				],
				'Ebergőc' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.5635832, 'lng' => 16.81167],
				],
				'Écs' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.5604415, 'lng' => 17.7072193],
				],
				'Edve' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.4551126, 'lng' => 17.135508],
				],
				'Egyed' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5192845, 'lng' => 17.3396861],
				],
				'Egyházasfalu' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.46243, 'lng' => 16.7679871],
				],
				'Enese' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.6461219, 'lng' => 17.4235267],
				],
				'Farád' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.6064483, 'lng' => 17.2003347],
				],
				'Fehértó' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.6759514, 'lng' => 17.3453497],
				],
				'Feketeerdő' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.9355702, 'lng' => 17.2783691],
				],
				'Felpéc' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.5225976, 'lng' => 17.5993517],
				],
				'Fenyőfő' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.3490387, 'lng' => 17.7656259],
				],
				'Fertőboz' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.633426, 'lng' => 16.6998899],
				],
				'Fertőd' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.61818, 'lng' => 16.8741418],
				],
				'Fertőendréd' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.6054618, 'lng' => 16.9085891],
				],
				'Fertőhomok' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.6196363, 'lng' => 16.7710445],
				],
				'Fertőrákos' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.7209654, 'lng' => 16.6488128],
				],
				'Fertőszentmiklós' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.5895578, 'lng' => 16.8730712],
				],
				'Fertőszéplak' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.6172442, 'lng' => 16.8405708],
				],
				'Gönyű' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.7334344, 'lng' => 17.8243403],
				],
				'Gyalóka' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.4427372, 'lng' => 16.696223],
				],
				'Gyarmat' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.4604024, 'lng' => 17.4964917],
				],
				'Gyömöre' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.4982876, 'lng' => 17.564804],
				],
				'Győr' => [
					'constituencies' => ['Győr-Moson-Sopron 2.', 'Győr-Moson-Sopron 1.'],
					'coordinates' => ['lat' => 47.6874569, 'lng' => 17.6503974],
				],
				'Győrasszonyfa' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.4950098, 'lng' => 17.8072327],
				],
				'Győrladamér' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.7545651, 'lng' => 17.5633004],
				],
				'Gyóró' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.4916519, 'lng' => 17.0236667],
				],
				'Győrság' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.5751529, 'lng' => 17.7515893],
				],
				'Győrsövényház' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.6909394, 'lng' => 17.3734235],
				],
				'Győrszemere' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.551813, 'lng' => 17.5635661],
				],
				'Győrújbarát' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.6076284, 'lng' => 17.6389745],
				],
				'Győrújfalu' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.722197, 'lng' => 17.6054524],
				],
				'Győrzámoly' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.7434268, 'lng' => 17.5770199],
				],
				'Halászi' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.8903231, 'lng' => 17.3256673],
				],
				'Harka' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.6339566, 'lng' => 16.5986264],
				],
				'Hédervár' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.831062, 'lng' => 17.4541026],
				],
				'Hegyeshalom' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.9117445, 'lng' => 17.156071],
				],
				'Hegykő' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.6188466, 'lng' => 16.7940292],
				],
				'Hidegség' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.6253847, 'lng' => 16.740935],
				],
				'Himod' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5200248, 'lng' => 17.0064434],
				],
				'Hövej' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5524954, 'lng' => 17.0166402],
				],
				'Ikrény' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.6539897, 'lng' => 17.5281764],
				],
				'Iván' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.445549, 'lng' => 16.9096056],
				],
				'Jánossomorja' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.7847917, 'lng' => 17.1298642],
				],
				'Jobaháza' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5799316, 'lng' => 17.1886952],
				],
				'Kajárpéc' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.4888221, 'lng' => 17.6350057],
				],
				'Kapuvár' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5912437, 'lng' => 17.0301952],
				],
				'Károlyháza' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.8032696, 'lng' => 17.3446363],
				],
				'Kimle' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.8172115, 'lng' => 17.3676625],
				],
				'Kisbabot' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5551791, 'lng' => 17.4149558],
				],
				'Kisbajcs' => [
					'constituencies' => ['Győr-Moson-Sopron 1.'],
					'coordinates' => ['lat' => 47.7450615, 'lng' => 17.6800942],
				],
				'Kisbodak' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.8963234, 'lng' => 17.4196192],
				],
				'Kisfalud' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.2041959, 'lng' => 18.494568],
				],
				'Kóny' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.6307264, 'lng' => 17.3596093],
				],
				'Kópháza' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.6385359, 'lng' => 16.6451629],
				],
				'Koroncó' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5999604, 'lng' => 17.5284792],
				],
				'Kunsziget' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.7385858, 'lng' => 17.5176565],
				],
				'Lázi' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.4661979, 'lng' => 17.8346909],
				],
				'Lébény' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.7360651, 'lng' => 17.3905652],
				],
				'Levél' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.8949275, 'lng' => 17.2001946],
				],
				'Lipót' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.8615868, 'lng' => 17.4603528],
				],
				'Lövő' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.5107966, 'lng' => 16.7898395],
				],
				'Maglóca' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.6625685, 'lng' => 17.2751221],
				],
				'Magyarkeresztúr' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5200063, 'lng' => 17.1660121],
				],
				'Máriakálnok' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.8596905, 'lng' => 17.3237666],
				],
				'Markotabödöge' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.6815136, 'lng' => 17.3116772],
				],
				'Mecsér' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.796671, 'lng' => 17.4744842],
				],
				'Mérges' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.6012809, 'lng' => 17.4438455],
				],
				'Mezőörs' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.568844, 'lng' => 17.8821253],
				],
				'Mihályi' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5142703, 'lng' => 17.0958265],
				],
				'Mórichida' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5127896, 'lng' => 17.4218174],
				],
				'Mosonmagyaróvár' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.8681469, 'lng' => 17.2689169],
				],
				'Mosonszentmiklós' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.7294576, 'lng' => 17.4242231],
				],
				'Mosonszolnok' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.8511108, 'lng' => 17.1735793],
				],
				'Mosonudvar' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.8435379, 'lng' => 17.224348],
				],
				'Nagybajcs' => [
					'constituencies' => ['Győr-Moson-Sopron 1.'],
					'coordinates' => ['lat' => 47.7639168, 'lng' => 17.686613],
				],
				'Nagycenk' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.6081549, 'lng' => 16.6979223],
				],
				'Nagylózs' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.5654858, 'lng' => 16.76965],
				],
				'Nagyszentjános' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.7100868, 'lng' => 17.8681808],
				],
				'Nemeskér' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.483855, 'lng' => 16.8050771],
				],
				'Nyalka' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.5443407, 'lng' => 17.8091081],
				],
				'Nyúl' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.5832389, 'lng' => 17.6862095],
				],
				'Osli' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.6385609, 'lng' => 17.0755158],
				],
				'Öttevény' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.7255506, 'lng' => 17.4899552],
				],
				'Páli' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.4774264, 'lng' => 17.1695082],
				],
				'Pannonhalma' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.549497, 'lng' => 17.7552412],
				],
				'Pásztori' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5553919, 'lng' => 17.2696728],
				],
				'Pázmándfalu' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.5710798, 'lng' => 17.7810865],
				],
				'Pér' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.6111604, 'lng' => 17.8049747],
				],
				'Pereszteg' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.594289, 'lng' => 16.7354028],
				],
				'Petőháza' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.5965785, 'lng' => 16.8954138],
				],
				'Pinnye' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.5855193, 'lng' => 16.7706082],
				],
				'Potyond' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.549377, 'lng' => 17.1821874],
				],
				'Püski' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.8846385, 'lng' => 17.4070152],
				],
				'Pusztacsalád' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.4853081, 'lng' => 16.9013644],
				],
				'Rábacsanak' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5256113, 'lng' => 17.2902872],
				],
				'Rábacsécsény' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5879598, 'lng' => 17.4227941],
				],
				'Rábakecöl' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.4324946, 'lng' => 17.1126349],
				],
				'Rábapatona' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.6314656, 'lng' => 17.4797584],
				],
				'Rábapordány' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5574649, 'lng' => 17.3262502],
				],
				'Rábasebes' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.4392738, 'lng' => 17.2423807],
				],
				'Rábaszentandrás' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.4596327, 'lng' => 17.3272097],
				],
				'Rábaszentmihály' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5775103, 'lng' => 17.4312379],
				],
				'Rábaszentmiklós' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5381909, 'lng' => 17.417513],
				],
				'Rábatamási' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5893387, 'lng' => 17.1699767],
				],
				'Rábcakapi' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.7079835, 'lng' => 17.2755839],
				],
				'Rajka' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.9977901, 'lng' => 17.1983996],
				],
				'Ravazd' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.5162349, 'lng' => 17.7512699],
				],
				'Répceszemere' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.4282026, 'lng' => 16.9738943],
				],
				'Répcevis' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.4427966, 'lng' => 16.6731972],
				],
				'Rétalap' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.6072246, 'lng' => 17.9071507],
				],
				'Röjtökmuzsaj' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.5543502, 'lng' => 16.8363467],
				],
				'Románd' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.4484049, 'lng' => 17.7909987],
				],
				'Sarród' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.6315873, 'lng' => 16.8613408],
				],
				'Sikátor' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.4370828, 'lng' => 17.8510581],
				],
				'Sobor' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.4768368, 'lng' => 17.3752902],
				],
				'Sokorópátka' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.4892381, 'lng' => 17.6953943],
				],
				'Sopron' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.6816619, 'lng' => 16.5844795],
				],
				'Sopronhorpács' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.4831854, 'lng' => 16.7359058],
				],
				'Sopronkövesd' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.5460504, 'lng' => 16.7432859],
				],
				'Sopronnémeti' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5364397, 'lng' => 17.2070182],
				],
				'Szakony' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.4262848, 'lng' => 16.7154462],
				],
				'Szany' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.4620733, 'lng' => 17.3027671],
				],
				'Szárföld' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5933239, 'lng' => 17.1221243],
				],
				'Szerecseny' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.4628425, 'lng' => 17.5536197],
				],
				'Szil' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.501622, 'lng' => 17.233297],
				],
				'Szilsárkány' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5396552, 'lng' => 17.2545808],
				],
				'Táp' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.5168299, 'lng' => 17.8292989],
				],
				'Tápszentmiklós' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.4930151, 'lng' => 17.8524913],
				],
				'Tarjánpuszta' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.5062161, 'lng' => 17.7869857],
				],
				'Tárnokréti' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.7217546, 'lng' => 17.3078226],
				],
				'Tényő' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.5407376, 'lng' => 17.6490009],
				],
				'Tét' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5198967, 'lng' => 17.5108553],
				],
				'Töltéstava' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.6273335, 'lng' => 17.7343778],
				],
				'Újkér' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.4573295, 'lng' => 16.8187647],
				],
				'Újrónafő' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.8101728, 'lng' => 17.2015241],
				],
				'Und' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.488856, 'lng' => 16.6961552],
				],
				'Vadosfa' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.4986805, 'lng' => 17.1287654],
				],
				'Vág' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.4469264, 'lng' => 17.2121765],
				],
				'Vámosszabadi' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.7571476, 'lng' => 17.6507532],
				],
				'Várbalog' => [
					'constituencies' => ['Győr-Moson-Sopron 5.'],
					'coordinates' => ['lat' => 47.8347267, 'lng' => 17.0720923],
				],
				'Vásárosfalu' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.4537986, 'lng' => 17.1158473],
				],
				'Vének' => [
					'constituencies' => ['Győr-Moson-Sopron 1.'],
					'coordinates' => ['lat' => 47.7392272, 'lng' => 17.7556608],
				],
				'Veszkény' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5969056, 'lng' => 17.0891913],
				],
				'Veszprémvarsány' => [
					'constituencies' => ['Győr-Moson-Sopron 2.'],
					'coordinates' => ['lat' => 47.4290248, 'lng' => 17.8287245],
				],
				'Vitnyéd' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.5863882, 'lng' => 16.9832151],
				],
				'Völcsej' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.496503, 'lng' => 16.7604595],
				],
				'Zsebeháza' => [
					'constituencies' => ['Győr-Moson-Sopron 3.'],
					'coordinates' => ['lat' => 47.511293, 'lng' => 17.191017],
				],
				'Zsira' => [
					'constituencies' => ['Győr-Moson-Sopron 4.'],
					'coordinates' => ['lat' => 47.4580482, 'lng' => 16.6766466],
				],
			],
			'Hajdú-Bihar' => [
				'Álmosd' => [
					'constituencies' => ['Hajdú-Bihar 3.'],
					'coordinates' => ['lat' => 47.4167788, 'lng' => 21.9806107],
				],
				'Ártánd' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.1241958, 'lng' => 21.7568167],
				],
				'Bagamér' => [
					'constituencies' => ['Hajdú-Bihar 3.'],
					'coordinates' => ['lat' => 47.4498231, 'lng' => 21.9942012],
				],
				'Bakonszeg' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.1900613, 'lng' => 21.4442102],
				],
				'Balmazújváros' => [
					'constituencies' => ['Hajdú-Bihar 6.'],
					'coordinates' => ['lat' => 47.6145296, 'lng' => 21.3417333],
				],
				'Báránd' => [
					'constituencies' => ['Hajdú-Bihar 5.'],
					'coordinates' => ['lat' => 47.2936964, 'lng' => 21.2288584],
				],
				'Bedő' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.1634194, 'lng' => 21.7502785],
				],
				'Berekböszörmény' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.0615952, 'lng' => 21.6782301],
				],
				'Berettyóújfalu' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.2196438, 'lng' => 21.5362812],
				],
				'Bihardancsháza' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.2291246, 'lng' => 21.3159659],
				],
				'Biharkeresztes' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.1301236, 'lng' => 21.7219423],
				],
				'Biharnagybajom' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.2108104, 'lng' => 21.2302309],
				],
				'Bihartorda' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.215994, 'lng' => 21.3526252],
				],
				'Bocskaikert' => [
					'constituencies' => ['Hajdú-Bihar 3.'],
					'coordinates' => ['lat' => 47.6435949, 'lng' => 21.659878],
				],
				'Bojt' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.1927968, 'lng' => 21.7327485],
				],
				'Csökmő' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.0315111, 'lng' => 21.2892817],
				],
				'Darvas' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.1017037, 'lng' => 21.3374554],
				],
				'Debrecen' => [
					'constituencies' => ['Hajdú-Bihar 3.', 'Hajdú-Bihar 1.', 'Hajdú-Bihar 2.'],
					'coordinates' => ['lat' => 47.5316049, 'lng' => 21.6273124],
				],
				'Derecske' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.3533886, 'lng' => 21.5658524],
				],
				'Ebes' => [
					'constituencies' => ['Hajdú-Bihar 5.'],
					'coordinates' => ['lat' => 47.4709086, 'lng' => 21.490457],
				],
				'Egyek' => [
					'constituencies' => ['Hajdú-Bihar 5.'],
					'coordinates' => ['lat' => 47.6258313, 'lng' => 20.8907463],
				],
				'Esztár' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.2837051, 'lng' => 21.7744117],
				],
				'Földes' => [
					'constituencies' => ['Hajdú-Bihar 5.'],
					'coordinates' => ['lat' => 47.2896801, 'lng' => 21.3633025],
				],
				'Folyás' => [
					'constituencies' => ['Hajdú-Bihar 6.'],
					'coordinates' => ['lat' => 47.8086696, 'lng' => 21.1371809],
				],
				'Fülöp' => [
					'constituencies' => ['Hajdú-Bihar 3.'],
					'coordinates' => ['lat' => 47.5981409, 'lng' => 22.0546557],
				],
				'Furta' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.1300357, 'lng' => 21.460144],
				],
				'Gáborján' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.2360716, 'lng' => 21.6622765],
				],
				'Görbeháza' => [
					'constituencies' => ['Hajdú-Bihar 6.'],
					'coordinates' => ['lat' => 47.8200025, 'lng' => 21.2359976],
				],
				'Hajdúbagos' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.3947066, 'lng' => 21.6643329],
				],
				'Hajdúböszörmény' => [
					'constituencies' => ['Hajdú-Bihar 6.'],
					'coordinates' => ['lat' => 47.6718908, 'lng' => 21.5126637],
				],
				'Hajdúdorog' => [
					'constituencies' => ['Hajdú-Bihar 6.'],
					'coordinates' => ['lat' => 47.8166047, 'lng' => 21.4980694],
				],
				'Hajdúhadház' => [
					'constituencies' => ['Hajdú-Bihar 3.'],
					'coordinates' => ['lat' => 47.6802292, 'lng' => 21.6675179],
				],
				'Hajdúnánás' => [
					'constituencies' => ['Hajdú-Bihar 6.'],
					'coordinates' => ['lat' => 47.843004, 'lng' => 21.4242691],
				],
				'Hajdúsámson' => [
					'constituencies' => ['Hajdú-Bihar 3.'],
					'coordinates' => ['lat' => 47.6049148, 'lng' => 21.7597325],
				],
				'Hajdúszoboszló' => [
					'constituencies' => ['Hajdú-Bihar 5.'],
					'coordinates' => ['lat' => 47.4435369, 'lng' => 21.3965516],
				],
				'Hajdúszovát' => [
					'constituencies' => ['Hajdú-Bihar 5.'],
					'coordinates' => ['lat' => 47.3903463, 'lng' => 21.4764161],
				],
				'Hencida' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.2507004, 'lng' => 21.6989732],
				],
				'Hortobágy' => [
					'constituencies' => ['Hajdú-Bihar 5.'],
					'coordinates' => ['lat' => 47.5868751, 'lng' => 21.1560332],
				],
				'Hosszúpályi' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.3947673, 'lng' => 21.7346539],
				],
				'Kaba' => [
					'constituencies' => ['Hajdú-Bihar 5.'],
					'coordinates' => ['lat' => 47.3565391, 'lng' => 21.2726765],
				],
				'Kismarja' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.2463277, 'lng' => 21.8214627],
				],
				'Kokad' => [
					'constituencies' => ['Hajdú-Bihar 3.'],
					'coordinates' => ['lat' => 47.4054409, 'lng' => 21.9336174],
				],
				'Komádi' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.0055271, 'lng' => 21.4944772],
				],
				'Konyár' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.3213954, 'lng' => 21.6691634],
				],
				'Körösszakál' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.0178012, 'lng' => 21.5932398],
				],
				'Körösszegapáti' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.0396539, 'lng' => 21.6317831],
				],
				'Létavértes' => [
					'constituencies' => ['Hajdú-Bihar 3.'],
					'coordinates' => ['lat' => 47.3835171, 'lng' => 21.8798767],
				],
				'Magyarhomorog' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.0222187, 'lng' => 21.5480518],
				],
				'Mezőpeterd' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.165025, 'lng' => 21.6200633],
				],
				'Mezősas' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.1104156, 'lng' => 21.5671344],
				],
				'Mikepércs' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.4406335, 'lng' => 21.6366773],
				],
				'Monostorpályi' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.3984198, 'lng' => 21.7764527],
				],
				'Nádudvar' => [
					'constituencies' => ['Hajdú-Bihar 5.'],
					'coordinates' => ['lat' => 47.4259381, 'lng' => 21.1616779],
				],
				'Nagyhegyes' => [
					'constituencies' => ['Hajdú-Bihar 5.'],
					'coordinates' => ['lat' => 47.539228, 'lng' => 21.345552],
				],
				'Nagykereki' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.1863168, 'lng' => 21.7922805],
				],
				'Nagyrábé' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.2043078, 'lng' => 21.3306582],
				],
				'Nyírábrány' => [
					'constituencies' => ['Hajdú-Bihar 3.'],
					'coordinates' => ['lat' => 47.541423, 'lng' => 22.0128317],
				],
				'Nyíracsád' => [
					'constituencies' => ['Hajdú-Bihar 3.'],
					'coordinates' => ['lat' => 47.6039774, 'lng' => 21.9715154],
				],
				'Nyíradony' => [
					'constituencies' => ['Hajdú-Bihar 3.'],
					'coordinates' => ['lat' => 47.6899404, 'lng' => 21.9085991],
				],
				'Nyírmártonfalva' => [
					'constituencies' => ['Hajdú-Bihar 3.'],
					'coordinates' => ['lat' => 47.5862503, 'lng' => 21.8964914],
				],
				'Pocsaj' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.2851817, 'lng' => 21.8122198],
				],
				'Polgár' => [
					'constituencies' => ['Hajdú-Bihar 6.'],
					'coordinates' => ['lat' => 47.8679381, 'lng' => 21.1141038],
				],
				'Püspökladány' => [
					'constituencies' => ['Hajdú-Bihar 5.'],
					'coordinates' => ['lat' => 47.3216529, 'lng' => 21.1185953],
				],
				'Sáp' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.2549739, 'lng' => 21.3555868],
				],
				'Sáránd' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.4062312, 'lng' => 21.6290631],
				],
				'Sárrétudvari' => [
					'constituencies' => ['Hajdú-Bihar 5.'],
					'coordinates' => ['lat' => 47.2406806, 'lng' => 21.1866058],
				],
				'Szentpéterszeg' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.2386719, 'lng' => 21.6178971],
				],
				'Szerep' => [
					'constituencies' => ['Hajdú-Bihar 5.'],
					'coordinates' => ['lat' => 47.2278774, 'lng' => 21.1407795],
				],
				'Téglás' => [
					'constituencies' => ['Hajdú-Bihar 3.'],
					'coordinates' => ['lat' => 47.7109686, 'lng' => 21.6727776],
				],
				'Tépe' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.32046, 'lng' => 21.5714076],
				],
				'Tetétlen' => [
					'constituencies' => ['Hajdú-Bihar 5.'],
					'coordinates' => ['lat' => 47.3148595, 'lng' => 21.3069162],
				],
				'Tiszacsege' => [
					'constituencies' => ['Hajdú-Bihar 5.'],
					'coordinates' => ['lat' => 47.6997085, 'lng' => 20.9917041],
				],
				'Tiszagyulaháza' => [
					'constituencies' => ['Hajdú-Bihar 6.'],
					'coordinates' => ['lat' => 47.942524, 'lng' => 21.1428152],
				],
				'Told' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.1180165, 'lng' => 21.6413048],
				],
				'Újiráz' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 46.9870862, 'lng' => 21.3556353],
				],
				'Újléta' => [
					'constituencies' => ['Hajdú-Bihar 3.'],
					'coordinates' => ['lat' => 47.4650261, 'lng' => 21.8733489],
				],
				'Újszentmargita' => [
					'constituencies' => ['Hajdú-Bihar 6.'],
					'coordinates' => ['lat' => 47.7266767, 'lng' => 21.1047788],
				],
				'Újtikos' => [
					'constituencies' => ['Hajdú-Bihar 6.'],
					'coordinates' => ['lat' => 47.9176202, 'lng' => 21.171571],
				],
				'Vámospércs' => [
					'constituencies' => ['Hajdú-Bihar 3.'],
					'coordinates' => ['lat' => 47.525345, 'lng' => 21.8992474],
				],
				'Váncsod' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.2011182, 'lng' => 21.6400459],
				],
				'Vekerd' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.0959975, 'lng' => 21.4017741],
				],
				'Zsáka' => [
					'constituencies' => ['Hajdú-Bihar 4.'],
					'coordinates' => ['lat' => 47.1340418, 'lng' => 21.4307824],
				],
			],
			'Heves' => [
				'Abasár' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 47.7989023, 'lng' => 20.0036779],
				],
				'Adács' => [
					'constituencies' => ['Heves 3.'],
					'coordinates' => ['lat' => 47.6922284, 'lng' => 19.9779484],
				],
				'Aldebrő' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 47.7891428, 'lng' => 20.2302555],
				],
				'Andornaktálya' => [
					'constituencies' => ['Heves 1.'],
					'coordinates' => ['lat' => 47.8499325, 'lng' => 20.4105243],
				],
				'Apc' => [
					'constituencies' => ['Heves 3.'],
					'coordinates' => ['lat' => 47.7933298, 'lng' => 19.6955737],
				],
				'Átány' => [
					'constituencies' => ['Heves 1.'],
					'coordinates' => ['lat' => 47.6156875, 'lng' => 20.3620368],
				],
				'Atkár' => [
					'constituencies' => ['Heves 3.'],
					'coordinates' => ['lat' => 47.7209651, 'lng' => 19.8912361],
				],
				'Balaton' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 46.8302679, 'lng' => 17.7340438],
				],
				'Bátor' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 47.99076, 'lng' => 20.2627351],
				],
				'Bekölce' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 48.0804457, 'lng' => 20.268156],
				],
				'Bélapátfalva' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 48.0578657, 'lng' => 20.3500536],
				],
				'Besenyőtelek' => [
					'constituencies' => ['Heves 1.'],
					'coordinates' => ['lat' => 47.6994693, 'lng' => 20.4300342],
				],
				'Boconád' => [
					'constituencies' => ['Heves 3.'],
					'coordinates' => ['lat' => 47.6414895, 'lng' => 20.1877312],
				],
				'Bodony' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 47.9420912, 'lng' => 20.0199927],
				],
				'Boldog' => [
					'constituencies' => ['Heves 3.'],
					'coordinates' => ['lat' => 47.6031287, 'lng' => 19.687521],
				],
				'Bükkszék' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 47.9915393, 'lng' => 20.1765126],
				],
				'Bükkszenterzsébet' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 48.0532811, 'lng' => 20.1622924],
				],
				'Bükkszentmárton' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 48.0715382, 'lng' => 20.3310312],
				],
				'Csány' => [
					'constituencies' => ['Heves 3.'],
					'coordinates' => ['lat' => 47.6474142, 'lng' => 19.8259607],
				],
				'Demjén' => [
					'constituencies' => ['Heves 1.'],
					'coordinates' => ['lat' => 47.8317294, 'lng' => 20.3313872],
				],
				'Detk' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 47.7489442, 'lng' => 20.0983332],
				],
				'Domoszló' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 47.8288666, 'lng' => 20.1172988],
				],
				'Dormánd' => [
					'constituencies' => ['Heves 1.'],
					'coordinates' => ['lat' => 47.7203119, 'lng' => 20.4174779],
				],
				'Ecséd' => [
					'constituencies' => ['Heves 3.'],
					'coordinates' => ['lat' => 47.7307237, 'lng' => 19.7684767],
				],
				'Eger' => [
					'constituencies' => ['Heves 1.'],
					'coordinates' => ['lat' => 47.9025348, 'lng' => 20.3772284],
				],
				'Egerbakta' => [
					'constituencies' => ['Heves 1.'],
					'coordinates' => ['lat' => 47.9341404, 'lng' => 20.2918134],
				],
				'Egerbocs' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 48.0263467, 'lng' => 20.2598999],
				],
				'Egercsehi' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 48.0545478, 'lng' => 20.261522],
				],
				'Egerfarmos' => [
					'constituencies' => ['Heves 1.'],
					'coordinates' => ['lat' => 47.7177802, 'lng' => 20.5358914],
				],
				'Egerszalók' => [
					'constituencies' => ['Heves 1.'],
					'coordinates' => ['lat' => 47.8702275, 'lng' => 20.3241673],
				],
				'Egerszólát' => [
					'constituencies' => ['Heves 1.'],
					'coordinates' => ['lat' => 47.8902473, 'lng' => 20.2669774],
				],
				'Erdőkövesd' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 48.0391241, 'lng' => 20.1013656],
				],
				'Erdőtelek' => [
					'constituencies' => ['Heves 3.'],
					'coordinates' => ['lat' => 47.6852656, 'lng' => 20.3115369],
				],
				'Erk' => [
					'constituencies' => ['Heves 3.'],
					'coordinates' => ['lat' => 47.6101796, 'lng' => 20.076668],
				],
				'Fedémes' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 48.0320282, 'lng' => 20.1878653],
				],
				'Feldebrő' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 47.8128253, 'lng' => 20.2363322],
				],
				'Felsőtárkány' => [
					'constituencies' => ['Heves 1.'],
					'coordinates' => ['lat' => 47.9734513, 'lng' => 20.41906],
				],
				'Füzesabony' => [
					'constituencies' => ['Heves 1.'],
					'coordinates' => ['lat' => 47.7495339, 'lng' => 20.4150668],
				],
				'Gyöngyös' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 47.7772651, 'lng' => 19.9294927],
				],
				'Gyöngyöshalász' => [
					'constituencies' => ['Heves 3.'],
					'coordinates' => ['lat' => 47.7413068, 'lng' => 19.9227242],
				],
				'Gyöngyösoroszi' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 47.8263987, 'lng' => 19.8928817],
				],
				'Gyöngyöspata' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 47.8140904, 'lng' => 19.7923335],
				],
				'Gyöngyössolymos' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 47.8160489, 'lng' => 19.9338831],
				],
				'Gyöngyöstarján' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 47.8132903, 'lng' => 19.8664265],
				],
				'Halmajugra' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 47.7634173, 'lng' => 20.0523104],
				],
				'Hatvan' => [
					'constituencies' => ['Heves 3.'],
					'coordinates' => ['lat' => 47.6656965, 'lng' => 19.676666],
				],
				'Heréd' => [
					'constituencies' => ['Heves 3.'],
					'coordinates' => ['lat' => 47.7081485, 'lng' => 19.6327042],
				],
				'Heves' => [
					'constituencies' => ['Heves 3.'],
					'coordinates' => ['lat' => 47.5971694, 'lng' => 20.280156],
				],
				'Hevesaranyos' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 48.0109153, 'lng' => 20.2342809],
				],
				'Hevesvezekény' => [
					'constituencies' => ['Heves 3.'],
					'coordinates' => ['lat' => 47.5570546, 'lng' => 20.3580453],
				],
				'Hort' => [
					'constituencies' => ['Heves 3.'],
					'coordinates' => ['lat' => 47.6890439, 'lng' => 19.7842632],
				],
				'Istenmezeje' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 48.0845673, 'lng' => 20.0515347],
				],
				'Ivád' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 48.0203013, 'lng' => 20.0612654],
				],
				'Kál' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 47.7318239, 'lng' => 20.2608866],
				],
				'Kápolna' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 47.7584202, 'lng' => 20.2459749],
				],
				'Karácsond' => [
					'constituencies' => ['Heves 3.'],
					'coordinates' => ['lat' => 47.7282318, 'lng' => 20.0282488],
				],
				'Kerecsend' => [
					'constituencies' => ['Heves 1.'],
					'coordinates' => ['lat' => 47.7947277, 'lng' => 20.3444695],
				],
				'Kerekharaszt' => [
					'constituencies' => ['Heves 3.'],
					'coordinates' => ['lat' => 47.6623104, 'lng' => 19.6253721],
				],
				'Kisfüzes' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 47.9881653, 'lng' => 20.1267373],
				],
				'Kisköre' => [
					'constituencies' => ['Heves 3.'],
					'coordinates' => ['lat' => 47.4984608, 'lng' => 20.4973609],
				],
				'Kisnána' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 47.8506469, 'lng' => 20.1457821],
				],
				'Kömlő' => [
					'constituencies' => ['Heves 1.'],
					'coordinates' => ['lat' => 46.1929788, 'lng' => 18.2512139],
				],
				'Kompolt' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 47.7415463, 'lng' => 20.2406377],
				],
				'Lőrinci' => [
					'constituencies' => ['Heves 3.'],
					'coordinates' => ['lat' => 47.7390261, 'lng' => 19.6756557],
				],
				'Ludas' => [
					'constituencies' => ['Heves 3.'],
					'coordinates' => ['lat' => 47.7300788, 'lng' => 20.0910629],
				],
				'Maklár' => [
					'constituencies' => ['Heves 1.'],
					'coordinates' => ['lat' => 47.8054074, 'lng' => 20.410901],
				],
				'Markaz' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 47.8222206, 'lng' => 20.0582311],
				],
				'Mátraballa' => [
					'constituencies' => ['Heves 2.'],
					'coordinates' => ['lat' => 47.9843833, 'lng' => 20.0225017],
				],

			],
		];
	}
}
