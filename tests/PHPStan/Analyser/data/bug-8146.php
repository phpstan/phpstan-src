<?php declare(strict_types=1);

namespace Bug8146;

class LocationFixtures
{
    /** @return array<string, array<string, array{constituencies: non-empty-list<string>, coordinates: array{lat: float, lng: float}}>> */
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
                'Mátraderecske' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 47.9479947, 'lng' => 20.0822028],
                ],
                'Mátraszentimre' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 47.9094814, 'lng' => 19.8759248],
                ],
                'Mezőszemere' => [
                    'constituencies' => ['Heves 1.'],
                    'coordinates' => ['lat' => 47.7463805, 'lng' => 20.518569],
                ],
                'Mezőtárkány' => [
                    'constituencies' => ['Heves 1.'],
                    'coordinates' => ['lat' => 47.7198231, 'lng' => 20.4763891],
                ],
                'Mikófalva' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 48.0559583, 'lng' => 20.3187324],
                ],
                'Mónosbél' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 48.0341739, 'lng' => 20.3327531],
                ],
                'Nagyfüged' => [
                    'constituencies' => ['Heves 3.'],
                    'coordinates' => ['lat' => 47.6828641, 'lng' => 20.1047939],
                ],
                'Nagykökényes' => [
                    'constituencies' => ['Heves 3.'],
                    'coordinates' => ['lat' => 47.7338833, 'lng' => 19.5985529],
                ],
                'Nagyréde' => [
                    'constituencies' => ['Heves 3.'],
                    'coordinates' => ['lat' => 47.7649455, 'lng' => 19.8513035],
                ],
                'Nagytálya' => [
                    'constituencies' => ['Heves 1.'],
                    'coordinates' => ['lat' => 47.816124, 'lng' => 20.4083683],
                ],
                'Nagyút' => [
                    'constituencies' => ['Heves 3.'],
                    'coordinates' => ['lat' => 47.7209548, 'lng' => 20.1722744],
                ],
                'Nagyvisnyó' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 48.1392605, 'lng' => 20.4260409],
                ],
                'Noszvaj' => [
                    'constituencies' => ['Heves 1.'],
                    'coordinates' => ['lat' => 47.9386893, 'lng' => 20.4729451],
                ],
                'Novaj' => [
                    'constituencies' => ['Heves 1.'],
                    'coordinates' => ['lat' => 47.8579509, 'lng' => 20.4792211],
                ],
                'Ostoros' => [
                    'constituencies' => ['Heves 1.'],
                    'coordinates' => ['lat' => 47.8685553, 'lng' => 20.4279983],
                ],
                'Pálosvörösmart' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 47.8153358, 'lng' => 19.9969266],
                ],
                'Parád' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 47.9238299, 'lng' => 20.0419493],
                ],
                'Parádsasvár' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 47.9122733, 'lng' => 19.9764865],
                ],
                'Pély' => [
                    'constituencies' => ['Heves 3.'],
                    'coordinates' => ['lat' => 47.4922155, 'lng' => 20.3426049],
                ],
                'Pétervására' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 48.0205346, 'lng' => 20.0984181],
                ],
                'Petőfibánya' => [
                    'constituencies' => ['Heves 3.'],
                    'coordinates' => ['lat' => 47.7638141, 'lng' => 19.7019699],
                ],
                'Poroszló' => [
                    'constituencies' => ['Heves 1.'],
                    'coordinates' => ['lat' => 47.647173, 'lng' => 20.650024],
                ],
                'Recsk' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 47.9336741, 'lng' => 20.1084878],
                ],
                'Rózsaszentmárton' => [
                    'constituencies' => ['Heves 3.'],
                    'coordinates' => ['lat' => 47.7850238, 'lng' => 19.7406507],
                ],
                'Sarud' => [
                    'constituencies' => ['Heves 1.'],
                    'coordinates' => ['lat' => 47.5906191, 'lng' => 20.5920014],
                ],
                'Sirok' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 47.9319682, 'lng' => 20.1944831],
                ],
                'Szajla' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 47.9617881, 'lng' => 20.1410079],
                ],
                'Szarvaskő' => [
                    'constituencies' => ['Heves 1.'],
                    'coordinates' => ['lat' => 47.9869926, 'lng' => 20.3329002],
                ],
                'Szentdomonkos' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 48.0684913, 'lng' => 20.1959353],
                ],
                'Szihalom' => [
                    'constituencies' => ['Heves 1.'],
                    'coordinates' => ['lat' => 47.773252, 'lng' => 20.4845704],
                ],
                'Szilvásvárad' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 48.1050501, 'lng' => 20.3881651],
                ],
                'Szúcs' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 48.046838, 'lng' => 20.2481659],
                ],
                'Szűcsi' => [
                    'constituencies' => ['Heves 3.'],
                    'coordinates' => ['lat' => 47.8029732, 'lng' => 19.7617685],
                ],
                'Tarnabod' => [
                    'constituencies' => ['Heves 3.'],
                    'coordinates' => ['lat' => 47.6840506, 'lng' => 20.2240375],
                ],
                'Tarnalelesz' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 48.0607839, 'lng' => 20.1817352],
                ],
                'Tarnaméra' => [
                    'constituencies' => ['Heves 3.'],
                    'coordinates' => ['lat' => 47.6533357, 'lng' => 20.1558033],
                ],
                'Tarnaörs' => [
                    'constituencies' => ['Heves 3.'],
                    'coordinates' => ['lat' => 47.5970549, 'lng' => 20.0558138],
                ],
                'Tarnaszentmária' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 47.8786816, 'lng' => 20.2034202],
                ],
                'Tarnaszentmiklós' => [
                    'constituencies' => ['Heves 3.'],
                    'coordinates' => ['lat' => 47.5268195, 'lng' => 20.3816054],
                ],
                'Tarnazsadány' => [
                    'constituencies' => ['Heves 3.'],
                    'coordinates' => ['lat' => 47.6743233, 'lng' => 20.1559835],
                ],
                'Tenk' => [
                    'constituencies' => ['Heves 3.'],
                    'coordinates' => ['lat' => 47.6553174, 'lng' => 20.3391024],
                ],
                'Terpes' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 47.9741006, 'lng' => 20.1504922],
                ],
                'Tiszanána' => [
                    'constituencies' => ['Heves 1.'],
                    'coordinates' => ['lat' => 47.562039, 'lng' => 20.522157],
                ],
                'Tófalu' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 47.7750413, 'lng' => 20.2375973],
                ],
                'Újlőrincfalva' => [
                    'constituencies' => ['Heves 1.'],
                    'coordinates' => ['lat' => 47.6267915, 'lng' => 20.597986],
                ],
                'Vámosgyörk' => [
                    'constituencies' => ['Heves 3.'],
                    'coordinates' => ['lat' => 47.685008, 'lng' => 19.9248214],
                ],
                'Váraszó' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 48.0598878, 'lng' => 20.1102459],
                ],
                'Vécs' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 47.8069407, 'lng' => 20.1688731],
                ],
                'Verpelét' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 47.8484938, 'lng' => 20.2282029],
                ],
                'Visonta' => [
                    'constituencies' => ['Heves 2.'],
                    'coordinates' => ['lat' => 47.7778641, 'lng' => 20.0287848],
                ],
                'Visznek' => [
                    'constituencies' => ['Heves 3.'],
                    'coordinates' => ['lat' => 47.6420585, 'lng' => 20.02672],
                ],
                'Zagyvaszántó' => [
                    'constituencies' => ['Heves 3.'],
                    'coordinates' => ['lat' => 47.7753848, 'lng' => 19.6701261],
                ],
                'Zaránk' => [
                    'constituencies' => ['Heves 3.'],
                    'coordinates' => ['lat' => 47.6405613, 'lng' => 20.1036532],
                ],
            ],
            'Jász-Nagykun-Szolnok' => [
                'Abádszalók' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 3.'],
                    'coordinates' => ['lat' => 47.474851, 'lng' => 20.591367],
                ],
                'Alattyán' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 2.'],
                    'coordinates' => ['lat' => 47.425691, 'lng' => 20.0423963],
                ],
                'Berekfürdő' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 3.'],
                    'coordinates' => ['lat' => 47.3871464, 'lng' => 20.8448654],
                ],
                'Besenyszög' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 1.'],
                    'coordinates' => ['lat' => 47.2955443, 'lng' => 20.2590077],
                ],
                'Cibakháza' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 46.9549324, 'lng' => 20.2016117],
                ],
                'Csataszög' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 1.'],
                    'coordinates' => ['lat' => 47.283495, 'lng' => 20.3842512],
                ],
                'Csépa' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 46.8073219, 'lng' => 20.1343316],
                ],
                'Cserkeszőlő' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 46.8672608, 'lng' => 20.204938],
                ],
                'Fegyvernek' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 3.'],
                    'coordinates' => ['lat' => 47.2492059, 'lng' => 20.5266647],
                ],
                'Hunyadfalva' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 1.'],
                    'coordinates' => ['lat' => 47.3118457, 'lng' => 20.3707903],
                ],
                'Jánoshida' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 2.'],
                    'coordinates' => ['lat' => 47.3815282, 'lng' => 20.0592719],
                ],
                'Jászágó' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 2.'],
                    'coordinates' => ['lat' => 47.5970758, 'lng' => 19.8467579],
                ],
                'Jászalsószentgyörgy' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 2.'],
                    'coordinates' => ['lat' => 47.3708192, 'lng' => 20.0950715],
                ],
                'Jászapáti' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 2.'],
                    'coordinates' => ['lat' => 47.512244, 'lng' => 20.142285],
                ],
                'Jászárokszállás' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 2.'],
                    'coordinates' => ['lat' => 47.6417789, 'lng' => 19.9786202],
                ],
                'Jászberény' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 2.'],
                    'coordinates' => ['lat' => 47.5001845, 'lng' => 19.9062865],
                ],
                'Jászboldogháza' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 2.'],
                    'coordinates' => ['lat' => 47.3690253, 'lng' => 19.998968],
                ],
                'Jászdózsa' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 2.'],
                    'coordinates' => ['lat' => 47.566094, 'lng' => 20.0128846],
                ],
                'Jászfelsőszentgyörgy' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 2.'],
                    'coordinates' => ['lat' => 47.5068047, 'lng' => 19.788709],
                ],
                'Jászfényszaru' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 2.'],
                    'coordinates' => ['lat' => 47.5715564, 'lng' => 19.7234877],
                ],
                'Jászivány' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 2.'],
                    'coordinates' => ['lat' => 47.5206579, 'lng' => 20.2457464],
                ],
                'Jászjákóhalma' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 2.'],
                    'coordinates' => ['lat' => 47.5191853, 'lng' => 19.9931675],
                ],
                'Jászkisér' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 2.'],
                    'coordinates' => ['lat' => 47.4559496, 'lng' => 20.2150999],
                ],
                'Jászladány' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 2.'],
                    'coordinates' => ['lat' => 47.3661708, 'lng' => 20.1644968],
                ],
                'Jászszentandrás' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 2.'],
                    'coordinates' => ['lat' => 47.5852301, 'lng' => 20.1718432],
                ],
                'Jásztelek' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 2.'],
                    'coordinates' => ['lat' => 47.4819707, 'lng' => 20.0041831],
                ],
                'Karcag' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 3.'],
                    'coordinates' => ['lat' => 47.3148629, 'lng' => 20.9196518],
                ],
                'Kenderes' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 3.'],
                    'coordinates' => ['lat' => 47.2487544, 'lng' => 20.6726977],
                ],
                'Kengyel' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 47.0933742, 'lng' => 20.3369989],
                ],
                'Kétpó' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 47.0753979, 'lng' => 20.4792963],
                ],
                'Kisújszállás' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 3.'],
                    'coordinates' => ['lat' => 47.215504, 'lng' => 20.7571333],
                ],
                'Kőtelek' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 1.'],
                    'coordinates' => ['lat' => 47.3354981, 'lng' => 20.4355378],
                ],
                'Kuncsorba' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 47.1289308, 'lng' => 20.5555221],
                ],
                'Kunhegyes' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 3.'],
                    'coordinates' => ['lat' => 47.3666884, 'lng' => 20.6346524],
                ],
                'Kunmadaras' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 3.'],
                    'coordinates' => ['lat' => 47.4261568, 'lng' => 20.7949978],
                ],
                'Kunszentmárton' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 46.8363738, 'lng' => 20.2878163],
                ],
                'Martfű' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 47.0172989, 'lng' => 20.2810618],
                ],
                'Mesterszállás' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 46.9568001, 'lng' => 20.4295257],
                ],
                'Mezőhék' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 46.9965965, 'lng' => 20.3865423],
                ],
                'Mezőtúr' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 47.0041296, 'lng' => 20.6161809],
                ],
                'Nagyiván' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 3.'],
                    'coordinates' => ['lat' => 47.4851729, 'lng' => 20.9271536],
                ],
                'Nagykörű' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 1.'],
                    'coordinates' => ['lat' => 47.2748758, 'lng' => 20.4468543],
                ],
                'Nagyrév' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 46.9436635, 'lng' => 20.1481311],
                ],
                'Öcsöd' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 46.9020053, 'lng' => 20.3890054],
                ],
                'Örményes' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 47.1924671, 'lng' => 20.5669518],
                ],
                'Pusztamonostor' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 2.'],
                    'coordinates' => ['lat' => 47.5569744, 'lng' => 19.7956793],
                ],
                'Rákóczifalva' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 1.'],
                    'coordinates' => ['lat' => 47.0916199, 'lng' => 20.2279391],
                ],
                'Rákócziújfalu' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 1.'],
                    'coordinates' => ['lat' => 47.0590598, 'lng' => 20.2664229],
                ],
                'Szajol' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 47.1744696, 'lng' => 20.3011621],
                ],
                'Szászberek' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 1.'],
                    'coordinates' => ['lat' => 47.3121174, 'lng' => 20.0991121],
                ],
                'Szelevény' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 46.8021192, 'lng' => 20.1981562],
                ],
                'Szolnok' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 1.'],
                    'coordinates' => ['lat' => 47.1621355, 'lng' => 20.1824712],
                ],
                'Tiszabő' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 3.'],
                    'coordinates' => ['lat' => 47.3076428, 'lng' => 20.4863751],
                ],
                'Tiszabura' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 3.'],
                    'coordinates' => ['lat' => 47.4475317, 'lng' => 20.4589276],
                ],
                'Tiszaderzs' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 3.'],
                    'coordinates' => ['lat' => 47.5132714, 'lng' => 20.6423166],
                ],
                'Tiszaföldvár' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 46.9652522, 'lng' => 20.2525018],
                ],
                'Tiszafüred' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 3.'],
                    'coordinates' => ['lat' => 47.6146841, 'lng' => 20.7504474],
                ],
                'Tiszagyenda' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 3.'],
                    'coordinates' => ['lat' => 47.383987, 'lng' => 20.5081817],
                ],
                'Tiszaigar' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 3.'],
                    'coordinates' => ['lat' => 47.5347294, 'lng' => 20.7963199],
                ],
                'Tiszainoka' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 46.9038788, 'lng' => 20.1548183],
                ],
                'Tiszajenő' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 1.'],
                    'coordinates' => ['lat' => 47.0248894, 'lng' => 20.1424797],
                ],
                'Tiszakürt' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 46.8865601, 'lng' => 20.1250583],
                ],
                'Tiszaörs' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 3.'],
                    'coordinates' => ['lat' => 47.5087725, 'lng' => 20.8228733],
                ],
                'Tiszapüspöki' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 47.2133835, 'lng' => 20.3180613],
                ],
                'Tiszaroff' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 3.'],
                    'coordinates' => ['lat' => 47.397983, 'lng' => 20.4374311],
                ],
                'Tiszasas' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 46.8225925, 'lng' => 20.0802012],
                ],
                'Tiszasüly' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 2.'],
                    'coordinates' => ['lat' => 47.3879537, 'lng' => 20.3873882],
                ],
                'Tiszaszentimre' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 3.'],
                    'coordinates' => ['lat' => 47.4896832, 'lng' => 20.7251336],
                ],
                'Tiszaszőlős' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 3.'],
                    'coordinates' => ['lat' => 47.5563403, 'lng' => 20.7214501],
                ],
                'Tiszatenyő' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 47.1382108, 'lng' => 20.3770865],
                ],
                'Tiszavárkony' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 1.'],
                    'coordinates' => ['lat' => 47.0639799, 'lng' => 20.1814024],
                ],
                'Tomajmonostora' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 3.'],
                    'coordinates' => ['lat' => 47.4343713, 'lng' => 20.7031231],
                ],
                'Törökszentmiklós' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 4.'],
                    'coordinates' => ['lat' => 47.1759479, 'lng' => 20.4132766],
                ],
                'Tószeg' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 1.'],
                    'coordinates' => ['lat' => 47.09755, 'lng' => 20.1398595],
                ],
                'Túrkeve' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 3.'],
                    'coordinates' => ['lat' => 47.1043405, 'lng' => 20.7417284],
                ],
                'Újszász' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 2.'],
                    'coordinates' => ['lat' => 47.2900477, 'lng' => 20.071179],
                ],
                'Vezseny' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 1.'],
                    'coordinates' => ['lat' => 47.0298525, 'lng' => 20.2179134],
                ],
                'Zagyvarékas' => [
                    'constituencies' => ['Jász-Nagykun-Szolnok 1.'],
                    'coordinates' => ['lat' => 47.2662053, 'lng' => 20.1240392],
                ],
            ],
            'Komárom-Esztergom' => [
                'Ács' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.7029909, 'lng' => 18.0074994],
                ],
                'Ácsteszér' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.4082757, 'lng' => 18.0057133],
                ],
                'Aka' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.4013811, 'lng' => 18.0696451],
                ],
                'Almásfüzitő' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.7299107, 'lng' => 18.2566324],
                ],
                'Annavölgy' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.6953478, 'lng' => 18.6661781],
                ],
                'Ászár' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.5103908, 'lng' => 18.0041642],
                ],
                'Bábolna' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.6412136, 'lng' => 17.9787811],
                ],
                'Baj' => [
                    'constituencies' => ['Komárom-Esztergom 1.'],
                    'coordinates' => ['lat' => 47.6482703, 'lng' => 18.362793],
                ],
                'Bajna' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.6526729, 'lng' => 18.599924],
                ],
                'Bajót' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.728367, 'lng' => 18.5603746],
                ],
                'Bakonybánk' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.4662171, 'lng' => 17.9023814],
                ],
                'Bakonysárkány' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.4476908, 'lng' => 18.1010326],
                ],
                'Bakonyszombathely' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.4725672, 'lng' => 17.959953],
                ],
                'Bana' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.6493783, 'lng' => 17.9213039],
                ],
                'Bársonyos' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.5095062, 'lng' => 17.9212269],
                ],
                'Bokod' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.4919931, 'lng' => 18.2496494],
                ],
                'Császár' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.5019992, 'lng' => 18.1420261],
                ],
                'Csatka' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.3792159, 'lng' => 17.9764505],
                ],
                'Csém' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.6820263, 'lng' => 18.0886975],
                ],
                'Csép' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.5773354, 'lng' => 18.0634668],
                ],
                'Csolnok' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.6939954, 'lng' => 18.7137777],
                ],
                'Dad' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.518804, 'lng' => 18.2258724],
                ],
                'Dág' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.6647525, 'lng' => 18.7187042],
                ],
                'Dömös' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.7644099, 'lng' => 18.9104042],
                ],
                'Dorog' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.7236369, 'lng' => 18.729744],
                ],
                'Dunaalmás' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.7269343, 'lng' => 18.325511],
                ],
                'Dunaszentmiklós' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.7043134, 'lng' => 18.3815953],
                ],
                'Epöl' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.6463888, 'lng' => 18.6370513],
                ],
                'Esztergom' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.7883949, 'lng' => 18.7434451],
                ],
                'Ete' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.5333778, 'lng' => 18.0734882],
                ],
                'Gyermely' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.5917949, 'lng' => 18.6417637],
                ],
                'Héreg' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.6479775, 'lng' => 18.5120982],
                ],
                'Kecskéd' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.5218241, 'lng' => 18.3085385],
                ],
                'Kerékteleki' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.5151856, 'lng' => 17.9390277],
                ],
                'Kesztölc' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.7132605, 'lng' => 18.7885429],
                ],
                'Kisbér' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.4975746, 'lng' => 18.028651],
                ],
                'Kisigmánd' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.6538051, 'lng' => 18.0982485],
                ],
                'Kocs' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.6066644, 'lng' => 18.2148552],
                ],
                'Komárom' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.7390852, 'lng' => 18.1267006],
                ],
                'Kömlőd' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.5442861, 'lng' => 18.2588383],
                ],
                'Környe' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.5433411, 'lng' => 18.3334659],
                ],
                'Lábatlan' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.7473238, 'lng' => 18.4910422],
                ],
                'Leányvár' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.6811915, 'lng' => 18.772131],
                ],
                'Máriahalom' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.6272993, 'lng' => 18.7078969],
                ],
                'Mocsa' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.6691326, 'lng' => 18.1824407],
                ],
                'Mogyorósbánya' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.7287112, 'lng' => 18.6035254],
                ],
                'Nagyigmánd' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.6364291, 'lng' => 18.0710406],
                ],
                'Nagysáp' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.6848748, 'lng' => 18.6014087],
                ],
                'Naszály' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.6997006, 'lng' => 18.2581902],
                ],
                'Neszmély' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.734063, 'lng' => 18.3542633],
                ],
                'Nyergesújfalu' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.7570052, 'lng' => 18.5502089],
                ],
                'Oroszlány' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.4858755, 'lng' => 18.315496],
                ],
                'Piliscsév' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.6758113, 'lng' => 18.8156151],
                ],
                'Pilismarót' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.7838071, 'lng' => 18.8757708],
                ],
                'Réde' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.429874, 'lng' => 17.9161858],
                ],
                'Sárisáp' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.6755615, 'lng' => 18.6785675],
                ],
                'Súr' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.3695631, 'lng' => 18.0308026],
                ],
                'Süttő' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.7572442, 'lng' => 18.4475856],
                ],
                'Szákszend' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.5451391, 'lng' => 18.1726464],
                ],
                'Szárliget' => [
                    'constituencies' => ['Komárom-Esztergom 1.'],
                    'coordinates' => ['lat' => 47.5168922, 'lng' => 18.4950011],
                ],
                'Szomód' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.6818612, 'lng' => 18.3408412],
                ],
                'Szomor' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.5919164, 'lng' => 18.6652671],
                ],
                'Tardos' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.6621475, 'lng' => 18.4514534],
                ],
                'Tarján' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.6085662, 'lng' => 18.5096391],
                ],
                'Tárkány' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.592676, 'lng' => 18.0012442],
                ],
                'Tát' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.7409833, 'lng' => 18.648829],
                ],
                'Tata' => [
                    'constituencies' => ['Komárom-Esztergom 1.'],
                    'coordinates' => ['lat' => 47.6458172, 'lng' => 18.3303022],
                ],
                'Tatabánya' => [
                    'constituencies' => ['Komárom-Esztergom 1.'],
                    'coordinates' => ['lat' => 47.569246, 'lng' => 18.404818],
                ],
                'Tokod' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.7280727, 'lng' => 18.6653779],
                ],
                'Tokodaltáró' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.7318931, 'lng' => 18.6894382],
                ],
                'Úny' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.6417844, 'lng' => 18.7368192],
                ],
                'Várgesztes' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.4747062, 'lng' => 18.3988089],
                ],
                'Vérteskethely' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.4859015, 'lng' => 18.0834128],
                ],
                'Vértessomló' => [
                    'constituencies' => ['Komárom-Esztergom 3.'],
                    'coordinates' => ['lat' => 47.5111256, 'lng' => 18.3665487],
                ],
                'Vértesszőlős' => [
                    'constituencies' => ['Komárom-Esztergom 1.'],
                    'coordinates' => ['lat' => 47.6214892, 'lng' => 18.3804104],
                ],
                'Vértestolna' => [
                    'constituencies' => ['Komárom-Esztergom 2.'],
                    'coordinates' => ['lat' => 47.6273588, 'lng' => 18.455897],
                ],
            ],
            'Nógrád' => [
                'Alsópetény' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.8750415, 'lng' => 19.2436605],
                ],
                'Alsótold' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.9517606, 'lng' => 19.5974927],
                ],
                'Balassagyarmat' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0711866, 'lng' => 19.2937136],
                ],
                'Bánk' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9237734, 'lng' => 19.1732058],
                ],
                'Bárna' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 48.1005037, 'lng' => 19.933392],
                ],
                'Bátonyterenye' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.992873, 'lng' => 19.8270694],
                ],
                'Becske' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9105827, 'lng' => 19.376094],
                ],
                'Bér' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.8632299, 'lng' => 19.5042102],
                ],
                'Bercel' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.8731938, 'lng' => 19.4036543],
                ],
                'Berkenye' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.8869612, 'lng' => 19.070502],
                ],
                'Bokor' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.9298107, 'lng' => 19.5417931],
                ],
                'Borsosberény' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9750317, 'lng' => 19.1096484],
                ],
                'Buják' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.8819043, 'lng' => 19.5447415],
                ],
                'Cered' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 48.1461683, 'lng' => 19.9640407],
                ],
                'Csécse' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.8675819, 'lng' => 19.6216114],
                ],
                'Cserháthaláp' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9796106, 'lng' => 19.378028],
                ],
                'Cserhátsurány' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9782939, 'lng' => 19.4251222],
                ],
                'Cserhátszentiván' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.9393473, 'lng' => 19.5803563],
                ],
                'Csesztve' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0146911, 'lng' => 19.2779957],
                ],
                'Csitár' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0545783, 'lng' => 19.427874],
                ],
                'Debercsény' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9616154, 'lng' => 19.3151101],
                ],
                'Dejtár' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0382401, 'lng' => 19.1673776],
                ],
                'Diósjenő' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.941713, 'lng' => 19.0389773],
                ],
                'Dorogháza' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.9867221, 'lng' => 19.90185],
                ],
                'Drégelypalánk' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0493831, 'lng' => 19.0477349],
                ],
                'Ecseg' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.8978427, 'lng' => 19.6037939],
                ],
                'Egyházasdengeleg' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.7958201, 'lng' => 19.5575048],
                ],
                'Egyházasgerge' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.1798893, 'lng' => 19.6453014],
                ],
                'Endrefalva' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.1272592, 'lng' => 19.5792852],
                ],
                'Erdőkürt' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.7733766, 'lng' => 19.4582374],
                ],
                'Erdőtarcsa' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.7638239, 'lng' => 19.5391851],
                ],
                'Érsekvadkert' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9971184, 'lng' => 19.197031],
                ],
                'Etes' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.1099097, 'lng' => 19.7219506],
                ],
                'Felsőpetény' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.8889613, 'lng' => 19.1975606],
                ],
                'Felsőtold' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.9691969, 'lng' => 19.6106401],
                ],
                'Galgaguta' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.84928, 'lng' => 19.3877658],
                ],
                'Garáb' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.9780761, 'lng' => 19.6399711],
                ],
                'Héhalom' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.7787299, 'lng' => 19.5881532],
                ],
                'Herencsény' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9749325, 'lng' => 19.4716887],
                ],
                'Hollókő' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.9975197, 'lng' => 19.5919709],
                ],
                'Hont' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0504378, 'lng' => 18.9939433],
                ],
                'Horpács' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9976073, 'lng' => 19.1302099],
                ],
                'Hugyag' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0874971, 'lng' => 19.4315758],
                ],
                'Iliny' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0331409, 'lng' => 19.4258421],
                ],
                'Ipolyszög' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0524595, 'lng' => 19.2320718],
                ],
                'Ipolytarnóc' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.2383614, 'lng' => 19.6261792],
                ],
                'Ipolyvece' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0604805, 'lng' => 19.1069166],
                ],
                'Jobbágyi' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.8307049, 'lng' => 19.6751723],
                ],
                'Kálló' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.7490626, 'lng' => 19.4898587],
                ],
                'Karancsalja' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.1323722, 'lng' => 19.7549909],
                ],
                'Karancsberény' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.1845828, 'lng' => 19.7450703],
                ],
                'Karancskeszi' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.1631262, 'lng' => 19.6990502],
                ],
                'Karancslapujtő' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.1524053, 'lng' => 19.7387194],
                ],
                'Karancsság' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.1167447, 'lng' => 19.6590169],
                ],
                'Kazár' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 48.050031, 'lng' => 19.8571824],
                ],
                'Keszeg' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.8366001, 'lng' => 19.2388746],
                ],
                'Kétbodony' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9349396, 'lng' => 19.2817966],
                ],
                'Kisbágyon' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.8251751, 'lng' => 19.5846052],
                ],
                'Kisbárkány' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 48.0165211, 'lng' => 19.6852055],
                ],
                'Kisecset' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9369898, 'lng' => 19.3137043],
                ],
                'Kishartyán' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 48.0840009, 'lng' => 19.7033542],
                ],
                'Kozárd' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.9145586, 'lng' => 19.6184443],
                ],
                'Kutasó' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.947864, 'lng' => 19.5402922],
                ],
                'Legénd' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.8781938, 'lng' => 19.3109033],
                ],
                'Litke' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.2088599, 'lng' => 19.599671],
                ],
                'Lucfalva' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 48.0305692, 'lng' => 19.691604],
                ],
                'Ludányhalászi' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.1358396, 'lng' => 19.524206],
                ],
                'Magyargéc' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0842649, 'lng' => 19.6028346],
                ],
                'Magyarnándor' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9681191, 'lng' => 19.3491916],
                ],
                'Márkháza' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 48.0144811, 'lng' => 19.7195486],
                ],
                'Mátramindszent' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.9820467, 'lng' => 19.9341332],
                ],
                'Mátranovák' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 48.0367889, 'lng' => 19.9764364],
                ],
                'Mátraszele' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 48.053499, 'lng' => 19.8953922],
                ],
                'Mátraszőlős' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.9545683, 'lng' => 19.6898279],
                ],
                'Mátraterenye' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 48.0261631, 'lng' => 19.9563216],
                ],
                'Mátraverebély' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.9731543, 'lng' => 19.7764794],
                ],
                'Mihálygerge' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.1961148, 'lng' => 19.6356407],
                ],
                'Mohora' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9938388, 'lng' => 19.3406287],
                ],
                'Nagybárkány' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.9987358, 'lng' => 19.7019131],
                ],
                'Nagykeresztúr' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 48.0389162, 'lng' => 19.7254939],
                ],
                'Nagylóc' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0349294, 'lng' => 19.5755888],
                ],
                'Nagyoroszi' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0021518, 'lng' => 19.0939001],
                ],
                'Nemti' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 48.0062166, 'lng' => 19.9027941],
                ],
                'Nézsa' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.8451117, 'lng' => 19.2963705],
                ],
                'Nógrád' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9041031, 'lng' => 19.0498504],
                ],
                'Nógrádkövesd' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.8787105, 'lng' => 19.3704996],
                ],
                'Nógrádmarcal' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0276924, 'lng' => 19.3842043],
                ],
                'Nógrádmegyer' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.068927, 'lng' => 19.6245484],
                ],
                'Nógrádsáp' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.8386954, 'lng' => 19.3541238],
                ],
                'Nógrádsipek' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0078615, 'lng' => 19.504134],
                ],
                'Nógrádszakál' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.181305, 'lng' => 19.5265547],
                ],
                'Nőtincs' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.8840243, 'lng' => 19.1372103],
                ],
                'Őrhalom' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0761016, 'lng' => 19.4046301],
                ],
                'Ősagárd' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.8582182, 'lng' => 19.1920121],
                ],
                'Palotás' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.7950667, 'lng' => 19.5967106],
                ],
                'Pásztó' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.9241404, 'lng' => 19.7059592],
                ],
                'Patak' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0200434, 'lng' => 19.1459782],
                ],
                'Patvarc' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0657946, 'lng' => 19.346196],
                ],
                'Piliny' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.137451, 'lng' => 19.5997838],
                ],
                'Pusztaberki' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9749351, 'lng' => 19.1639067],
                ],
                'Rákóczibánya' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 48.0295219, 'lng' => 19.8728674],
                ],
                'Rétság' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9294927, 'lng' => 19.1351898],
                ],
                'Rimóc' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0381914, 'lng' => 19.5278775],
                ],
                'Romhány' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.92317, 'lng' => 19.2580206],
                ],
                'Ságújfalu' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.1049581, 'lng' => 19.681943],
                ],
                'Salgótarján' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 48.0935237, 'lng' => 19.7999813],
                ],
                'Sámsonháza' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.986915, 'lng' => 19.7213946],
                ],
                'Somoskőújfalu' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 48.1594881, 'lng' => 19.8226063],
                ],
                'Sóshartyán' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 48.0713121, 'lng' => 19.6812221],
                ],
                'Szalmatercs' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.122849, 'lng' => 19.635785],
                ],
                'Szanda' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9270138, 'lng' => 19.4391933],
                ],
                'Szarvasgede' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.8211781, 'lng' => 19.6387209],
                ],
                'Szátok' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9545885, 'lng' => 19.2345013],
                ],
                'Szécsénke' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9044501, 'lng' => 19.33425],
                ],
                'Szécsény' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0801811, 'lng' => 19.5199646],
                ],
                'Szécsényfelfalu' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.1451432, 'lng' => 19.5673177],
                ],
                'Szendehely' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.8562253, 'lng' => 19.1031731],
                ],
                'Szente' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9675395, 'lng' => 19.2832332],
                ],
                'Szilaspogony' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 48.1171656, 'lng' => 20.0203005],
                ],
                'Szirák' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.830127, 'lng' => 19.53333],
                ],
                'Szügy' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0353776, 'lng' => 19.3259223],
                ],
                'Szuha' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.9729422, 'lng' => 19.9170076],
                ],
                'Szurdokpüspöki' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.858319, 'lng' => 19.697088],
                ],
                'Tar' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 47.9508012, 'lng' => 19.7420286],
                ],
                'Terény' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9469931, 'lng' => 19.4400328],
                ],
                'Tereske' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9497613, 'lng' => 19.1921131],
                ],
                'Tolmács' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.9306976, 'lng' => 19.1083919],
                ],
                'Vanyarc' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 47.8235477, 'lng' => 19.4532779],
                ],
                'Varsány' => [
                    'constituencies' => ['Nógrád 2.'],
                    'coordinates' => ['lat' => 48.0420183, 'lng' => 19.4897063],
                ],
                'Vizslás' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 48.0500449, 'lng' => 19.8191861],
                ],
                'Zabar' => [
                    'constituencies' => ['Nógrád 1.'],
                    'coordinates' => ['lat' => 48.1451049, 'lng' => 20.0493344],
                ],
            ],
            'Pest' => [
                'Abony' => [
                    'constituencies' => ['Pest 12.'],
                    'coordinates' => ['lat' => 47.18854, 'lng' => 20.0095688],
                ],
                'Acsa' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.7946936, 'lng' => 19.3864356],
                ],
                'Albertirsa' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.2418477, 'lng' => 19.6098344],
                ],
                'Alsónémedi' => [
                    'constituencies' => ['Pest 11.'],
                    'coordinates' => ['lat' => 47.3166722, 'lng' => 19.1586308],
                ],
                'Apaj' => [
                    'constituencies' => ['Pest 11.'],
                    'coordinates' => ['lat' => 47.1149909, 'lng' => 19.0892407],
                ],
                'Áporka' => [
                    'constituencies' => ['Pest 11.'],
                    'coordinates' => ['lat' => 47.2293561, 'lng' => 19.0121294],
                ],
                'Aszód' => [
                    'constituencies' => ['Pest 06.'],
                    'coordinates' => ['lat' => 47.6533802, 'lng' => 19.4843422],
                ],
                'Bag' => [
                    'constituencies' => ['Pest 06.'],
                    'coordinates' => ['lat' => 47.6355254, 'lng' => 19.4829006],
                ],
                'Bénye' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.3545366, 'lng' => 19.5406237],
                ],
                'Bernecebaráti' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 48.0379555, 'lng' => 18.9114537],
                ],
                'Biatorbágy' => [
                    'constituencies' => ['Pest 02.'],
                    'coordinates' => ['lat' => 47.4706818, 'lng' => 18.8205591],
                ],
                'Budajenő' => [
                    'constituencies' => ['Pest 02.'],
                    'coordinates' => ['lat' => 47.5561584, 'lng' => 18.8024165],
                ],
                'Budakalász' => [
                    'constituencies' => ['Pest 03.'],
                    'coordinates' => ['lat' => 47.621282, 'lng' => 19.050868],
                ],
                'Budakeszi' => [
                    'constituencies' => ['Pest 02.'],
                    'coordinates' => ['lat' => 47.5136249, 'lng' => 18.9278382],
                ],
                'Budaörs' => [
                    'constituencies' => ['Pest 02.'],
                    'coordinates' => ['lat' => 47.4621396, 'lng' => 18.9529586],
                ],
                'Bugyi' => [
                    'constituencies' => ['Pest 11.'],
                    'coordinates' => ['lat' => 47.2259604, 'lng' => 19.15156],
                ],
                'Cegléd' => [
                    'constituencies' => ['Pest 12.'],
                    'coordinates' => ['lat' => 47.1737909, 'lng' => 19.7966325],
                ],
                'Ceglédbercel' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.2189987, 'lng' => 19.6749687],
                ],
                'Csemő' => [
                    'constituencies' => ['Pest 12.'],
                    'coordinates' => ['lat' => 47.1177661, 'lng' => 19.6907056],
                ],
                'Csévharaszt' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.2949541, 'lng' => 19.4246557],
                ],
                'Csobánka' => [
                    'constituencies' => ['Pest 03.'],
                    'coordinates' => ['lat' => 47.6444621, 'lng' => 18.9644984],
                ],
                'Csomád' => [
                    'constituencies' => ['Pest 05.'],
                    'coordinates' => ['lat' => 47.6588363, 'lng' => 19.2318138],
                ],
                'Csömör' => [
                    'constituencies' => ['Pest 05.'],
                    'coordinates' => ['lat' => 47.54666, 'lng' => 19.2242899],
                ],
                'Csörög' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.7336344, 'lng' => 19.1931529],
                ],
                'Csővár' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.8127209, 'lng' => 19.3234007],
                ],
                'Dabas' => [
                    'constituencies' => ['Pest 11.'],
                    'coordinates' => ['lat' => 47.1876108, 'lng' => 19.3118999],
                ],
                'Dánszentmiklós' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.2108957, 'lng' => 19.5481094],
                ],
                'Dány' => [
                    'constituencies' => ['Pest 09.'],
                    'coordinates' => ['lat' => 47.5183825, 'lng' => 19.5432106],
                ],
                'Délegyháza' => [
                    'constituencies' => ['Pest 11.'],
                    'coordinates' => ['lat' => 47.255922, 'lng' => 19.0808625],
                ],
                'Diósd' => [
                    'constituencies' => ['Pest 01.'],
                    'coordinates' => ['lat' => 47.4082916, 'lng' => 18.9434797],
                ],
                'Domony' => [
                    'constituencies' => ['Pest 06.'],
                    'coordinates' => ['lat' => 47.6544521, 'lng' => 19.4349645],
                ],
                'Dömsöd' => [
                    'constituencies' => ['Pest 11.'],
                    'coordinates' => ['lat' => 47.0903328, 'lng' => 19.0107327],
                ],
                'Dunabogdány' => [
                    'constituencies' => ['Pest 03.'],
                    'coordinates' => ['lat' => 47.7938701, 'lng' => 19.0290871],
                ],
                'Dunaharaszti' => [
                    'constituencies' => ['Pest 11.'],
                    'coordinates' => ['lat' => 47.3536817, 'lng' => 19.0970722],
                ],
                'Dunakeszi' => [
                    'constituencies' => ['Pest 05.'],
                    'coordinates' => ['lat' => 47.6343844, 'lng' => 19.1397121],
                ],
                'Dunavarsány' => [
                    'constituencies' => ['Pest 08.'],
                    'coordinates' => ['lat' => 47.2795704, 'lng' => 19.0679148],
                ],
                'Ecser' => [
                    'constituencies' => ['Pest 07.'],
                    'coordinates' => ['lat' => 47.4443176, 'lng' => 19.3168337],
                ],
                'Érd' => [
                    'constituencies' => ['Pest 01.'],
                    'coordinates' => ['lat' => 47.3919718, 'lng' => 18.904544],
                ],
                'Erdőkertes' => [
                    'constituencies' => ['Pest 05.'],
                    'coordinates' => ['lat' => 47.6706442, 'lng' => 19.3139168],
                ],
                'Farmos' => [
                    'constituencies' => ['Pest 09.'],
                    'coordinates' => ['lat' => 47.361515, 'lng' => 19.8520869],
                ],
                'Felsőpakony' => [
                    'constituencies' => ['Pest 07.'],
                    'coordinates' => ['lat' => 47.3438657, 'lng' => 19.2379608],
                ],
                'Fót' => [
                    'constituencies' => ['Pest 05.'],
                    'coordinates' => ['lat' => 47.6172524, 'lng' => 19.1891657],
                ],
                'Galgagyörk' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.7409757, 'lng' => 19.3768622],
                ],
                'Galgahévíz' => [
                    'constituencies' => ['Pest 09.'],
                    'coordinates' => ['lat' => 47.6185843, 'lng' => 19.5596312],
                ],
                'Galgamácsa' => [
                    'constituencies' => ['Pest 06.'],
                    'coordinates' => ['lat' => 47.69623, 'lng' => 19.3901192],
                ],
                'Göd' => [
                    'constituencies' => ['Pest 05.'],
                    'coordinates' => ['lat' => 47.6942316, 'lng' => 19.140119],
                ],
                'Gödöllő' => [
                    'constituencies' => ['Pest 06.'],
                    'coordinates' => ['lat' => 47.6007732, 'lng' => 19.3605431],
                ],
                'Gomba' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.3702652, 'lng' => 19.5287008],
                ],
                'Gyál' => [
                    'constituencies' => ['Pest 07.'],
                    'coordinates' => ['lat' => 47.3837273, 'lng' => 19.2171074],
                ],
                'Gyömrő' => [
                    'constituencies' => ['Pest 07.'],
                    'coordinates' => ['lat' => 47.4245545, 'lng' => 19.3976705],
                ],
                'Halásztelek' => [
                    'constituencies' => ['Pest 08.'],
                    'coordinates' => ['lat' => 47.3630908, 'lng' => 18.9817643],
                ],
                'Herceghalom' => [
                    'constituencies' => ['Pest 02.'],
                    'coordinates' => ['lat' => 47.4979399, 'lng' => 18.743256],
                ],
                'Hernád' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.1642848, 'lng' => 19.4108893],
                ],
                'Hévízgyörk' => [
                    'constituencies' => ['Pest 06.'],
                    'coordinates' => ['lat' => 47.6287048, 'lng' => 19.5196749],
                ],
                'Iklad' => [
                    'constituencies' => ['Pest 06.'],
                    'coordinates' => ['lat' => 47.6628224, 'lng' => 19.4421871],
                ],
                'Inárcs' => [
                    'constituencies' => ['Pest 11.'],
                    'coordinates' => ['lat' => 47.2605463, 'lng' => 19.3267784],
                ],
                'Ipolydamásd' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.8410047, 'lng' => 18.8288573],
                ],
                'Ipolytölgyes' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.9229342, 'lng' => 18.7744892],
                ],
                'Isaszeg' => [
                    'constituencies' => ['Pest 06.'],
                    'coordinates' => ['lat' => 47.5353306, 'lng' => 19.3977809],
                ],
                'Jászkarajenő' => [
                    'constituencies' => ['Pest 12.'],
                    'coordinates' => ['lat' => 47.0545025, 'lng' => 20.0653166],
                ],
                'Kakucs' => [
                    'constituencies' => ['Pest 11.'],
                    'coordinates' => ['lat' => 47.2431117, 'lng' => 19.3588571],
                ],
                'Kartal' => [
                    'constituencies' => ['Pest 06.'],
                    'coordinates' => ['lat' => 47.6685035, 'lng' => 19.5312436],
                ],
                'Káva' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.3554048, 'lng' => 19.5876244],
                ],
                'Kemence' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 48.0153558, 'lng' => 18.8925622],
                ],
                'Kerepes' => [
                    'constituencies' => ['Pest 06.'],
                    'coordinates' => ['lat' => 47.5662807, 'lng' => 19.2756517],
                ],
                'Kiskunlacháza' => [
                    'constituencies' => ['Pest 11.'],
                    'coordinates' => ['lat' => 47.18256, 'lng' => 19.0092269],
                ],
                'Kismaros' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.8279522, 'lng' => 19.0108936],
                ],
                'Kisnémedi' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.7391255, 'lng' => 19.2872763],
                ],
                'Kisoroszi' => [
                    'constituencies' => ['Pest 03.'],
                    'coordinates' => ['lat' => 47.8091992, 'lng' => 19.013878],
                ],
                'Kistarcsa' => [
                    'constituencies' => ['Pest 06.'],
                    'coordinates' => ['lat' => 47.5446377, 'lng' => 19.2610341],
                ],
                'Kocsér' => [
                    'constituencies' => ['Pest 12.'],
                    'coordinates' => ['lat' => 47.0018902, 'lng' => 19.9182583],
                ],
                'Kóka' => [
                    'constituencies' => ['Pest 09.'],
                    'coordinates' => ['lat' => 47.4845919, 'lng' => 19.5798512],
                ],
                'Kőröstetétlen' => [
                    'constituencies' => ['Pest 12.'],
                    'coordinates' => ['lat' => 47.0976011, 'lng' => 20.0227627],
                ],
                'Kosd' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.8052708, 'lng' => 19.1779341],
                ],
                'Kóspallag' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.8752485, 'lng' => 18.9338747],
                ],
                'Leányfalu' => [
                    'constituencies' => ['Pest 03.'],
                    'coordinates' => ['lat' => 47.7181009, 'lng' => 19.0842471],
                ],
                'Letkés' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.8843259, 'lng' => 18.7755541],
                ],
                'Lórév' => [
                    'constituencies' => ['Pest 11.'],
                    'coordinates' => ['lat' => 47.1156758, 'lng' => 18.8981702],
                ],
                'Maglód' => [
                    'constituencies' => ['Pest 07.'],
                    'coordinates' => ['lat' => 47.4440276, 'lng' => 19.354801],
                ],
                'Majosháza' => [
                    'constituencies' => ['Pest 08.'],
                    'coordinates' => ['lat' => 47.2641226, 'lng' => 18.9945367],
                ],
                'Makád' => [
                    'constituencies' => ['Pest 11.'],
                    'coordinates' => ['lat' => 47.0865805, 'lng' => 18.929995],
                ],
                'Márianosztra' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.8650711, 'lng' => 18.8723308],
                ],
                'Mende' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.4317228, 'lng' => 19.4589424],
                ],
                'Mikebuda' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.1590231, 'lng' => 19.6169286],
                ],
                'Mogyoród' => [
                    'constituencies' => ['Pest 06.'],
                    'coordinates' => ['lat' => 47.6007371, 'lng' => 19.2399319],
                ],
                'Monor' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.3480638, 'lng' => 19.4401291],
                ],
                'Monorierdő' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.3100446, 'lng' => 19.5033655],
                ],
                'Nagybörzsöny' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.9367293, 'lng' => 18.8256778],
                ],
                'Nagykáta' => [
                    'constituencies' => ['Pest 09.'],
                    'coordinates' => ['lat' => 47.4126422, 'lng' => 19.7401812],
                ],
                'Nagykőrös' => [
                    'constituencies' => ['Pest 12.'],
                    'coordinates' => ['lat' => 47.0316684, 'lng' => 19.7803862],
                ],
                'Nagykovácsi' => [
                    'constituencies' => ['Pest 02.'],
                    'coordinates' => ['lat' => 47.5747756, 'lng' => 18.8836779],
                ],
                'Nagymaros' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.7880379, 'lng' => 18.9541462],
                ],
                'Nagytarcsa' => [
                    'constituencies' => ['Pest 06.'],
                    'coordinates' => ['lat' => 47.5263601, 'lng' => 19.2826293],
                ],
                'Nyáregyháza' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.2594511, 'lng' => 19.5005778],
                ],
                'Nyársapát' => [
                    'constituencies' => ['Pest 12.'],
                    'coordinates' => ['lat' => 47.0985549, 'lng' => 19.7983695],
                ],
                'Ócsa' => [
                    'constituencies' => ['Pest 11.'],
                    'coordinates' => ['lat' => 47.2987842, 'lng' => 19.2292811],
                ],
                'Őrbottyán' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.685806, 'lng' => 19.278376],
                ],
                'Örkény' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.1302928, 'lng' => 19.4326744],
                ],
                'Pánd' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.3519431, 'lng' => 19.6336214],
                ],
                'Páty' => [
                    'constituencies' => ['Pest 02.'],
                    'coordinates' => ['lat' => 47.5152562, 'lng' => 18.8276879],
                ],
                'Pécel' => [
                    'constituencies' => ['Pest 07.'],
                    'coordinates' => ['lat' => 47.4908971, 'lng' => 19.337941],
                ],
                'Penc' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.8027222, 'lng' => 19.2493255],
                ],
                'Perbál' => [
                    'constituencies' => ['Pest 02.'],
                    'coordinates' => ['lat' => 47.5903472, 'lng' => 18.7587246],
                ],
                'Perőcsény' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.9942882, 'lng' => 18.860154],
                ],
                'Péteri' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.3880901, 'lng' => 19.4094502],
                ],
                'Pilis' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.2840844, 'lng' => 19.545477],
                ],
                'Pilisborosjenő' => [
                    'constituencies' => ['Pest 03.'],
                    'coordinates' => ['lat' => 47.6092307, 'lng' => 18.9918365],
                ],
                'Piliscsaba' => [
                    'constituencies' => ['Pest 02.'],
                    'coordinates' => ['lat' => 47.6356691, 'lng' => 18.8335907],
                ],
                'Pilisjászfalu' => [
                    'constituencies' => ['Pest 02.'],
                    'coordinates' => ['lat' => 47.6577701, 'lng' => 18.7959191],
                ],
                'Pilisszántó' => [
                    'constituencies' => ['Pest 03.'],
                    'coordinates' => ['lat' => 47.6692066, 'lng' => 18.8851223],
                ],
                'Pilisszentiván' => [
                    'constituencies' => ['Pest 02.'],
                    'coordinates' => ['lat' => 47.6101518, 'lng' => 18.8940286],
                ],
                'Pilisszentkereszt' => [
                    'constituencies' => ['Pest 03.'],
                    'coordinates' => ['lat' => 47.6916974, 'lng' => 18.8997812],
                ],
                'Pilisszentlászló' => [
                    'constituencies' => ['Pest 03.'],
                    'coordinates' => ['lat' => 47.7240115, 'lng' => 18.9877803],
                ],
                'Pilisvörösvár' => [
                    'constituencies' => ['Pest 03.'],
                    'coordinates' => ['lat' => 47.6194053, 'lng' => 18.9065538],
                ],
                'Pócsmegyer' => [
                    'constituencies' => ['Pest 03.'],
                    'coordinates' => ['lat' => 47.7172581, 'lng' => 19.0970625],
                ],
                'Pomáz' => [
                    'constituencies' => ['Pest 03.'],
                    'coordinates' => ['lat' => 47.6456397, 'lng' => 19.0218001],
                ],
                'Püspökhatvan' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.7760021, 'lng' => 19.3640846],
                ],
                'Püspökszilágy' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.7401736, 'lng' => 19.3146749],
                ],
                'Pusztavacs' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.1718531, 'lng' => 19.5013194],
                ],
                'Pusztazámor' => [
                    'constituencies' => ['Pest 01.'],
                    'coordinates' => ['lat' => 47.4044249, 'lng' => 18.7830465],
                ],
                'Ráckeve' => [
                    'constituencies' => ['Pest 11.'],
                    'coordinates' => ['lat' => 47.1596861, 'lng' => 18.9385715],
                ],
                'Rád' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.7916513, 'lng' => 19.2208393],
                ],
                'Remeteszőlős' => [
                    'constituencies' => ['Pest 02.'],
                    'coordinates' => ['lat' => 47.5563951, 'lng' => 18.9290733],
                ],
                'Solymár' => [
                    'constituencies' => ['Pest 02.'],
                    'coordinates' => ['lat' => 47.5892196, 'lng' => 18.9296062],
                ],
                'Sóskút' => [
                    'constituencies' => ['Pest 01.'],
                    'coordinates' => ['lat' => 47.4046702, 'lng' => 18.8277597],
                ],
                'Sülysáp' => [
                    'constituencies' => ['Pest 09.'],
                    'coordinates' => ['lat' => 47.453293, 'lng' => 19.5312122],
                ],
                'Szada' => [
                    'constituencies' => ['Pest 06.'],
                    'coordinates' => ['lat' => 47.6345716, 'lng' => 19.3106822],
                ],
                'Százhalombatta' => [
                    'constituencies' => ['Pest 08.'],
                    'coordinates' => ['lat' => 47.3083325, 'lng' => 18.9096709],
                ],
                'Szentendre' => [
                    'constituencies' => ['Pest 03.'],
                    'coordinates' => ['lat' => 47.6795337, 'lng' => 19.0668602],
                ],
                'Szentlőrinckáta' => [
                    'constituencies' => ['Pest 09.'],
                    'coordinates' => ['lat' => 47.519653, 'lng' => 19.7537597],
                ],
                'Szentmártonkáta' => [
                    'constituencies' => ['Pest 09.'],
                    'coordinates' => ['lat' => 47.44525, 'lng' => 19.6907916],
                ],
                'Szigetbecse' => [
                    'constituencies' => ['Pest 11.'],
                    'coordinates' => ['lat' => 47.1307853, 'lng' => 18.9471344],
                ],
                'Szigetcsép' => [
                    'constituencies' => ['Pest 08.'],
                    'coordinates' => ['lat' => 47.2684366, 'lng' => 18.9669844],
                ],
                'Szigethalom' => [
                    'constituencies' => ['Pest 08.'],
                    'coordinates' => ['lat' => 47.3184296, 'lng' => 19.0066748],
                ],
                'Szigetmonostor' => [
                    'constituencies' => ['Pest 03.'],
                    'coordinates' => ['lat' => 47.687804, 'lng' => 19.1035803],
                ],
                'Szigetszentmárton' => [
                    'constituencies' => ['Pest 08.'],
                    'coordinates' => ['lat' => 47.2271163, 'lng' => 18.9586038],
                ],
                'Szigetszentmiklós' => [
                    'constituencies' => ['Pest 08.'],
                    'coordinates' => ['lat' => 47.3396005, 'lng' => 19.0351771],
                ],
                'Szigetújfalu' => [
                    'constituencies' => ['Pest 08.'],
                    'coordinates' => ['lat' => 47.232444, 'lng' => 18.9266441],
                ],
                'Szob' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.8186867, 'lng' => 18.8699819],
                ],
                'Sződ' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.7179401, 'lng' => 19.1832083],
                ],
                'Sződliget' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.7299706, 'lng' => 19.1432303],
                ],
                'Szokolya' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.8647419, 'lng' => 19.0073274],
                ],
                'Táborfalva' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.1079105, 'lng' => 19.4822506],
                ],
                'Tahitótfalu' => [
                    'constituencies' => ['Pest 03.'],
                    'coordinates' => ['lat' => 47.7503911, 'lng' => 19.0791055],
                ],
                'Taksony' => [
                    'constituencies' => ['Pest 11.'],
                    'coordinates' => ['lat' => 47.3277932, 'lng' => 19.0679136],
                ],
                'Tápióbicske' => [
                    'constituencies' => ['Pest 09.'],
                    'coordinates' => ['lat' => 47.3661203, 'lng' => 19.6863365],
                ],
                'Tápiógyörgye' => [
                    'constituencies' => ['Pest 09.'],
                    'coordinates' => ['lat' => 47.3345394, 'lng' => 19.9483755],
                ],
                'Tápióság' => [
                    'constituencies' => ['Pest 09.'],
                    'coordinates' => ['lat' => 47.4014645, 'lng' => 19.6269761],
                ],
                'Tápiószecső' => [
                    'constituencies' => ['Pest 09.'],
                    'coordinates' => ['lat' => 47.4500221, 'lng' => 19.6003929],
                ],
                'Tápiószele' => [
                    'constituencies' => ['Pest 09.'],
                    'coordinates' => ['lat' => 47.3330651, 'lng' => 19.8734993],
                ],
                'Tápiószentmárton' => [
                    'constituencies' => ['Pest 09.'],
                    'coordinates' => ['lat' => 47.3413267, 'lng' => 19.754832],
                ],
                'Tápiószőlős' => [
                    'constituencies' => ['Pest 09.'],
                    'coordinates' => ['lat' => 47.2938321, 'lng' => 19.8435577],
                ],
                'Tárnok' => [
                    'constituencies' => ['Pest 01.'],
                    'coordinates' => ['lat' => 47.3684498, 'lng' => 18.860627],
                ],
                'Tatárszentgyörgy' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.0795333, 'lng' => 19.3712171],
                ],
                'Telki' => [
                    'constituencies' => ['Pest 02.'],
                    'coordinates' => ['lat' => 47.5498385, 'lng' => 18.8246298],
                ],
                'Tésa' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 48.0330963, 'lng' => 18.8429469],
                ],
                'Tinnye' => [
                    'constituencies' => ['Pest 02.'],
                    'coordinates' => ['lat' => 47.6206785, 'lng' => 18.7772389],
                ],
                'Tóalmás' => [
                    'constituencies' => ['Pest 09.'],
                    'coordinates' => ['lat' => 47.5089186, 'lng' => 19.6645248],
                ],
                'Tök' => [
                    'constituencies' => ['Pest 02.'],
                    'coordinates' => ['lat' => 47.5655237, 'lng' => 18.7332381],
                ],
                'Tököl' => [
                    'constituencies' => ['Pest 08.'],
                    'coordinates' => ['lat' => 47.3187057, 'lng' => 18.9675223],
                ],
                'Törökbálint' => [
                    'constituencies' => ['Pest 01.'],
                    'coordinates' => ['lat' => 47.4384914, 'lng' => 18.9108101],
                ],
                'Törtel' => [
                    'constituencies' => ['Pest 12.'],
                    'coordinates' => ['lat' => 47.1204752, 'lng' => 19.9328971],
                ],
                'Tura' => [
                    'constituencies' => ['Pest 09.'],
                    'coordinates' => ['lat' => 47.6077064, 'lng' => 19.6004032],
                ],
                'Újhartyán' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.2190126, 'lng' => 19.390058],
                ],
                'Újlengyel' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.2285471, 'lng' => 19.4492411],
                ],
                'Újszilvás' => [
                    'constituencies' => ['Pest 12.'],
                    'coordinates' => ['lat' => 47.2709483, 'lng' => 19.9140418],
                ],
                'Üllő' => [
                    'constituencies' => ['Pest 07.'],
                    'coordinates' => ['lat' => 47.3846843, 'lng' => 19.344861],
                ],
                'Úri' => [
                    'constituencies' => ['Pest 09.'],
                    'coordinates' => ['lat' => 47.4151322, 'lng' => 19.5239407],
                ],
                'Üröm' => [
                    'constituencies' => ['Pest 03.'],
                    'coordinates' => ['lat' => 47.598213, 'lng' => 19.013075],
                ],
                'Vác' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.7841803, 'lng' => 19.1351781],
                ],
                'Vácduka' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.7455395, 'lng' => 19.2131066],
                ],
                'Vácegres' => [
                    'constituencies' => ['Pest 06.'],
                    'coordinates' => ['lat' => 47.6756361, 'lng' => 19.3662892],
                ],
                'Váchartyán' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.7249753, 'lng' => 19.2561428],
                ],
                'Váckisújfalu' => [
                    'constituencies' => ['Pest 06.'],
                    'coordinates' => ['lat' => 47.7044749, 'lng' => 19.3465254],
                ],
                'Vácrátót' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.710568, 'lng' => 19.2358342],
                ],
                'Vácszentlászló' => [
                    'constituencies' => ['Pest 09.'],
                    'coordinates' => ['lat' => 47.5750904, 'lng' => 19.5341898],
                ],
                'Valkó' => [
                    'constituencies' => ['Pest 09.'],
                    'coordinates' => ['lat' => 47.5646742, 'lng' => 19.5072743],
                ],
                'Vámosmikola' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.9783454, 'lng' => 18.7876069],
                ],
                'Vasad' => [
                    'constituencies' => ['Pest 10.'],
                    'coordinates' => ['lat' => 47.3215855, 'lng' => 19.4037492],
                ],
                'Vecsés' => [
                    'constituencies' => ['Pest 07.'],
                    'coordinates' => ['lat' => 47.4068857, 'lng' => 19.2624362],
                ],
                'Veresegyház' => [
                    'constituencies' => ['Pest 05.'],
                    'coordinates' => ['lat' => 47.6542478, 'lng' => 19.280257],
                ],
                'Verőce' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.826509, 'lng' => 19.0301295],
                ],
                'Verseg' => [
                    'constituencies' => ['Pest 06.'],
                    'coordinates' => ['lat' => 47.7229081, 'lng' => 19.5461405],
                ],
                'Visegrád' => [
                    'constituencies' => ['Pest 03.'],
                    'coordinates' => ['lat' => 47.7829694, 'lng' => 18.9733391],
                ],
                'Zebegény' => [
                    'constituencies' => ['Pest 04.'],
                    'coordinates' => ['lat' => 47.8028493, 'lng' => 18.9103832],
                ],
                'Zsámbék' => [
                    'constituencies' => ['Pest 02.'],
                    'coordinates' => ['lat' => 47.5473984, 'lng' => 18.7213733],
                ],
                'Zsámbok' => [
                    'constituencies' => ['Pest 09.'],
                    'coordinates' => ['lat' => 47.542928, 'lng' => 19.6105611],
                ],
            ],
            'Somogy' => [
                'Ádánd' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.8570381, 'lng' => 18.1579842],
                ],
                'Alsóbogát' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.5089101, 'lng' => 17.7455323],
                ],
                'Andocs' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.6496853, 'lng' => 17.9234954],
                ],
                'Babócsa' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.0364369, 'lng' => 17.3429004],
                ],
                'Bábonymegyer' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.7532896, 'lng' => 18.1097379],
                ],
                'Bakháza' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.1064382, 'lng' => 17.3612302],
                ],
                'Balatonberény' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.7099843, 'lng' => 17.3200743],
                ],
                'Balatonboglár' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.7739764, 'lng' => 17.6560695],
                ],
                'Balatonendréd' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.8408198, 'lng' => 17.9739418],
                ],
                'Balatonfenyves' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.7118686, 'lng' => 17.4762412],
                ],
                'Balatonföldvár' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.8485621, 'lng' => 17.8807976],
                ],
                'Balatonkeresztúr' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.7000381, 'lng' => 17.382492],
                ],
                'Balatonlelle' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.7842192, 'lng' => 17.6967856],
                ],
                'Balatonmáriafürdő' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.7042365, 'lng' => 17.4012783],
                ],
                'Balatonőszöd' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.8140315, 'lng' => 17.7982381],
                ],
                'Balatonszabadi' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.8937438, 'lng' => 18.135975],
                ],
                'Balatonszárszó' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.828413, 'lng' => 17.8354894],
                ],
                'Balatonszemes' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.8056876, 'lng' => 17.7670544],
                ],
                'Balatonszentgyörgy' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.6901748, 'lng' => 17.2972664],
                ],
                'Balatonújlak' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.6740397, 'lng' => 17.3853601],
                ],
                'Balatonvilágos' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.9786006, 'lng' => 18.1670212],
                ],
                'Bálványos' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.7806091, 'lng' => 17.9529189],
                ],
                'Barcs' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 45.9584735, 'lng' => 17.4613774],
                ],
                'Bárdudvarnok' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.3288556, 'lng' => 17.6876556],
                ],
                'Baté' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.3593043, 'lng' => 17.9635008],
                ],
                'Bedegkér' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.6499711, 'lng' => 18.0606778],
                ],
                'Bélavár' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.1220034, 'lng' => 17.2165689],
                ],
                'Beleg' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.3153028, 'lng' => 17.4118652],
                ],
                'Berzence' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2108729, 'lng' => 17.1501764],
                ],
                'Bodrog' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.4853247, 'lng' => 17.6568717],
                ],
                'Böhönye' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.4103747, 'lng' => 17.3939446],
                ],
                'Bolhás' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2672208, 'lng' => 17.2708647],
                ],
                'Bolhó' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.0405451, 'lng' => 17.3031107],
                ],
                'Bonnya' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.5935409, 'lng' => 17.9010074],
                ],
                'Bőszénfa' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.2388142, 'lng' => 17.8496848],
                ],
                'Büssü' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.4577379, 'lng' => 17.962195],
                ],
                'Buzsák' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.6430577, 'lng' => 17.5815636],
                ],
                'Csákány' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.5370901, 'lng' => 17.274477],
                ],
                'Cserénfa' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.3085725, 'lng' => 17.8809056],
                ],
                'Csököly' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2998858, 'lng' => 17.558872],
                ],
                'Csokonyavisonta' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.0787472, 'lng' => 17.4448944],
                ],
                'Csoma' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.3706644, 'lng' => 18.0478223],
                ],
                'Csombárd' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.4525505, 'lng' => 17.6704831],
                ],
                'Csömend' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.5715305, 'lng' => 17.490958],
                ],
                'Csurgó' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2565653, 'lng' => 17.1002994],
                ],
                'Csurgónagymarton' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2955062, 'lng' => 17.0810905],
                ],
                'Darány' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 45.9810956, 'lng' => 17.5901784],
                ],
                'Drávagárdony' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 45.9446224, 'lng' => 17.6051174],
                ],
                'Drávatamási' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 45.9416167, 'lng' => 17.5710243],
                ],
                'Ecseny' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.553946, 'lng' => 17.8561801],
                ],
                'Edde' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.5242928, 'lng' => 17.7168144],
                ],
                'Felsőmocsolád' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.5800858, 'lng' => 17.82514],
                ],
                'Fiad' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.6326484, 'lng' => 17.8384516],
                ],
                'Fonó' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.3980516, 'lng' => 17.9557576],
                ],
                'Főnyed' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.6309446, 'lng' => 17.2563394],
                ],
                'Fonyód' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.7424519, 'lng' => 17.5591474],
                ],
                'Gadács' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.5386773, 'lng' => 18.0045029],
                ],
                'Gadány' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.5183809, 'lng' => 17.3895963],
                ],
                'Gálosfa' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.2551332, 'lng' => 17.8855423],
                ],
                'Gamás' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.6205718, 'lng' => 17.7608985],
                ],
                'Gige' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.3029531, 'lng' => 17.6078028],
                ],
                'Gölle' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.4366176, 'lng' => 18.0112682],
                ],
                'Görgeteg' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.146934, 'lng' => 17.4426482],
                ],
                'Gyékényes' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2409657, 'lng' => 16.9946799],
                ],
                'Gyugy' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.6875388, 'lng' => 17.6795527],
                ],
                'Hács' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.6470172, 'lng' => 17.6879466],
                ],
                'Hajmás' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.2766047, 'lng' => 17.9110823],
                ],
                'Háromfa' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.1030749, 'lng' => 17.3316813],
                ],
                'Hedrehely' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.1971182, 'lng' => 17.6490129],
                ],
                'Hencse' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2010406, 'lng' => 17.6236878],
                ],
                'Heresznye' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.0545162, 'lng' => 17.2773699],
                ],
                'Hetes' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.4214622, 'lng' => 17.6942899],
                ],
                'Hollád' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.6409102, 'lng' => 17.3068933],
                ],
                'Homokszentgyörgy' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.1180802, 'lng' => 17.570441],
                ],
                'Hosszúvíz' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.517694, 'lng' => 17.4417862],
                ],
                'Igal' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.5393893, 'lng' => 17.9392444],
                ],
                'Iharos' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.3411346, 'lng' => 17.0970337],
                ],
                'Iharosberény' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.3650225, 'lng' => 17.111924],
                ],
                'Inke' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.3914632, 'lng' => 17.1975865],
                ],
                'Istvándi' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.0195232, 'lng' => 17.6187415],
                ],
                'Jákó' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.3336531, 'lng' => 17.5541502],
                ],
                'Juta' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.4082512, 'lng' => 17.7332602],
                ],
                'Kadarkút' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2321141, 'lng' => 17.6189931],
                ],
                'Kálmáncsa' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.0664097, 'lng' => 17.6194737],
                ],
                'Kánya' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.7013367, 'lng' => 18.0652987],
                ],
                'Kapoly' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.7268229, 'lng' => 17.9730707],
                ],
                'Kaposfő' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.3589443, 'lng' => 17.6616957],
                ],
                'Kaposgyarmat' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.2802908, 'lng' => 17.8804718],
                ],
                'Kaposhomok' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.3591789, 'lng' => 17.9239817],
                ],
                'Kaposkeresztúr' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.3314435, 'lng' => 17.9660111],
                ],
                'Kaposmérő' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.3592364, 'lng' => 17.7017126],
                ],
                'Kaposszerdahely' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.3222188, 'lng' => 17.7567757],
                ],
                'Kaposújlak' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.3598087, 'lng' => 17.7321198],
                ],
                'Kaposvár' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.3593606, 'lng' => 17.7967639],
                ],
                'Kára' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.6181153, 'lng' => 18.0109746],
                ],
                'Karád' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.6910376, 'lng' => 17.8423326],
                ],
                'Kastélyosdombó' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 45.9546291, 'lng' => 17.6175308],
                ],
                'Kaszó' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.3206121, 'lng' => 17.2230593],
                ],
                'Kazsok' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.480264, 'lng' => 17.9642617],
                ],
                'Kelevíz' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.5229587, 'lng' => 17.4180567],
                ],
                'Kercseliget' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.327427, 'lng' => 18.0636147],
                ],
                'Kereki' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.7941966, 'lng' => 17.9120786],
                ],
                'Kéthely' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.6453646, 'lng' => 17.393223],
                ],
                'Kisasszond' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.3311479, 'lng' => 17.6361899],
                ],
                'Kisbajom' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.3054288, 'lng' => 17.4923819],
                ],
                'Kisbárapáti' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.6071312, 'lng' => 17.8658343],
                ],
                'Kisberény' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.6404503, 'lng' => 17.6579315],
                ],
                'Kisgyalán' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.4237214, 'lng' => 17.9765127],
                ],
                'Kiskorpád' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.3587192, 'lng' => 17.6037985],
                ],
                'Kőkút' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.1911142, 'lng' => 17.5758825],
                ],
                'Komlósd' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.0185124, 'lng' => 17.3844551],
                ],
                'Kőröshegy' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.8350066, 'lng' => 17.8988043],
                ],
                'Kötcse' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.7525257, 'lng' => 17.8614636],
                ],
                'Kutas' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.3339075, 'lng' => 17.4542123],
                ],
                'Lábod' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2024931, 'lng' => 17.4543325],
                ],
                'Lad' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.1446665, 'lng' => 17.6454767],
                ],
                'Lakócsa' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 45.8956265, 'lng' => 17.691989],
                ],
                'Látrány' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.7477626, 'lng' => 17.7452248],
                ],
                'Lengyeltóti' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.6677937, 'lng' => 17.6363572],
                ],
                'Libickozma' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.5220612, 'lng' => 17.5327213],
                ],
                'Lulla' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.7916758, 'lng' => 18.0220741],
                ],
                'Magyaratád' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.4661089, 'lng' => 17.9018095],
                ],
                'Magyaregres' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.4596948, 'lng' => 17.7728225],
                ],
                'Marcali' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.585193, 'lng' => 17.4099961],
                ],
                'Mernye' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.5081351, 'lng' => 17.8226541],
                ],
                'Mesztegnyő' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.4995514, 'lng' => 17.4260262],
                ],
                'Mezőcsokonya' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.4315657, 'lng' => 17.6420579],
                ],
                'Mike' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2427837, 'lng' => 17.5295658],
                ],
                'Miklósi' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.6468279, 'lng' => 17.9949004],
                ],
                'Mosdós' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.3548782, 'lng' => 17.9893519],
                ],
                'Nágocs' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.6603648, 'lng' => 17.9580578],
                ],
                'Nagyatád' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2261652, 'lng' => 17.3649851],
                ],
                'Nagybajom' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.3957444, 'lng' => 17.5092762],
                ],
                'Nagyberény' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.7991196, 'lng' => 18.1671875],
                ],
                'Nagyberki' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.3600782, 'lng' => 18.0051605],
                ],
                'Nagycsepely' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.7491055, 'lng' => 17.8345209],
                ],
                'Nagykorpád' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2610722, 'lng' => 17.4560579],
                ],
                'Nagyszakácsi' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.4924942, 'lng' => 17.3188807],
                ],
                'Nemesdéd' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.4328996, 'lng' => 17.2409155],
                ],
                'Nemeskisfalud' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.4400335, 'lng' => 17.3663429],
                ],
                'Nemesvid' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.4902541, 'lng' => 17.2550105],
                ],
                'Nikla' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.5810356, 'lng' => 17.5141371],
                ],
                'Nyim' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.8047462, 'lng' => 18.1066874],
                ],
                'Orci' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.4050762, 'lng' => 17.874134],
                ],
                'Ordacsehi' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.7401848, 'lng' => 17.6228659],
                ],
                'Öreglak' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.6083478, 'lng' => 17.6348098],
                ],
                'Őrtilos' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2815297, 'lng' => 16.9257864],
                ],
                'Osztopán' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.5199229, 'lng' => 17.6708541],
                ],
                'Ötvöskónyi' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2872657, 'lng' => 17.3647613],
                ],
                'Pálmajor' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.3852599, 'lng' => 17.5671324],
                ],
                'Pamuk' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.5505753, 'lng' => 17.6364859],
                ],
                'Patalom' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.4494876, 'lng' => 17.9232314],
                ],
                'Patca' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.2829684, 'lng' => 17.722643],
                ],
                'Patosfa' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.12823, 'lng' => 17.664426],
                ],
                'Péterhida' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.0098032, 'lng' => 17.3609986],
                ],
                'Pogányszentpéter' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.383519, 'lng' => 17.0616687],
                ],
                'Polány' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.5589681, 'lng' => 17.7721999],
                ],
                'Porrog' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2883522, 'lng' => 17.0347315],
                ],
                'Porrogszentkirály' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2752622, 'lng' => 17.0417462],
                ],
                'Porrogszentpál' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2870404, 'lng' => 17.0147449],
                ],
                'Potony' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 45.9306385, 'lng' => 17.6493799],
                ],
                'Pusztakovácsi' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.5431407, 'lng' => 17.5714577],
                ],
                'Pusztaszemes' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.768913, 'lng' => 17.9243395],
                ],
                'Ráksi' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.5153856, 'lng' => 17.9186971],
                ],
                'Rinyabesenyő' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.1671054, 'lng' => 17.5159641],
                ],
                'Rinyakovácsi' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2825691, 'lng' => 17.5976417],
                ],
                'Rinyaszentkirály' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.1515715, 'lng' => 17.3941021],
                ],
                'Rinyaújlak' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.0837884, 'lng' => 17.4212785],
                ],
                'Rinyaújnép' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.0804695, 'lng' => 17.3551474],
                ],
                'Ságvár' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.8359688, 'lng' => 18.1011862],
                ],
                'Sántos' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.3465778, 'lng' => 17.8809964],
                ],
                'Sávoly' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.5933916, 'lng' => 17.266947],
                ],
                'Segesd' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.3441975, 'lng' => 17.3456213],
                ],
                'Sérsekszőlős' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.7650947, 'lng' => 18.0144498],
                ],
                'Simonfa' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.2834568, 'lng' => 17.8248235],
                ],
                'Siófok' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.9090603, 'lng' => 18.0746239],
                ],
                'Siójut' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.8771762, 'lng' => 18.1413204],
                ],
                'Som' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.8064199, 'lng' => 18.140602],
                ],
                'Somodor' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.4773129, 'lng' => 17.8425261],
                ],
                'Somogyacsa' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.5909386, 'lng' => 17.9554068],
                ],
                'Somogyaracs' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.0544834, 'lng' => 17.3917133],
                ],
                'Somogyaszaló' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.4572507, 'lng' => 17.804542],
                ],
                'Somogybabod' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.6696393, 'lng' => 17.7768237],
                ],
                'Somogybükkösd' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.3021645, 'lng' => 16.9922556],
                ],
                'Somogycsicsó' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.3100781, 'lng' => 17.1334791],
                ],
                'Somogydöröcske' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.5872333, 'lng' => 18.005858],
                ],
                'Somogyegres' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.6758618, 'lng' => 18.0262552],
                ],
                'Somogyfajsz' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.5059429, 'lng' => 17.5706706],
                ],
                'Somogygeszti' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.523536, 'lng' => 17.7785716],
                ],
                'Somogyjád' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.4918778, 'lng' => 17.7115271],
                ],
                'Somogymeggyes' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.7105393, 'lng' => 17.9111301],
                ],
                'Somogysámson' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.5916089, 'lng' => 17.2985033],
                ],
                'Somogysárd' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.4141626, 'lng' => 17.5957772],
                ],
                'Somogysimonyi' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.4871723, 'lng' => 17.2085422],
                ],
                'Somogyszentpál' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.6359095, 'lng' => 17.4779547],
                ],
                'Somogyszil' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.5151982, 'lng' => 17.9990815],
                ],
                'Somogyszob' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2919395, 'lng' => 17.2969494],
                ],
                'Somogytúr' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.7082431, 'lng' => 17.7649236],
                ],
                'Somogyudvarhely' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.1757068, 'lng' => 17.1888897],
                ],
                'Somogyvámos' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.5692048, 'lng' => 17.6838395],
                ],
                'Somogyvár' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.5831195, 'lng' => 17.6500124],
                ],
                'Somogyzsitfa' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.5587877, 'lng' => 17.2963132],
                ],
                'Szabadi' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.3657638, 'lng' => 18.0327083],
                ],
                'Szabás' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2876571, 'lng' => 17.4469362],
                ],
                'Szántód' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.8716075, 'lng' => 17.9126455],
                ],
                'Szegerdő' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.6335976, 'lng' => 17.2756859],
                ],
                'Szenna' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.3065563, 'lng' => 17.7291384],
                ],
                'Szenta' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2518683, 'lng' => 17.1711823],
                ],
                'Szentbalázs' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.319132, 'lng' => 17.8975645],
                ],
                'Szentborbás' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 45.8746846, 'lng' => 17.659917],
                ],
                'Szentgáloskér' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.5038379, 'lng' => 17.8811522],
                ],
                'Szenyér' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.4615603, 'lng' => 17.3694753],
                ],
                'Szilvásszentmárton' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.2694059, 'lng' => 17.7227426],
                ],
                'Szőkedencs' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.5593009, 'lng' => 17.2479998],
                ],
                'Szólád' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.7881094, 'lng' => 17.8398853],
                ],
                'Szőlősgyörök' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.7098826, 'lng' => 17.6742282],
                ],
                'Szorosad' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.6016138, 'lng' => 18.0233388],
                ],
                'Szulok' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.0493581, 'lng' => 17.5508136],
                ],
                'Tab' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.7322262, 'lng' => 18.0325771],
                ],
                'Tapsony' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.4551759, 'lng' => 17.3355824],
                ],
                'Tarany' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.1808755, 'lng' => 17.3077191],
                ],
                'Táska' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.6160424, 'lng' => 17.5251293],
                ],
                'Taszár' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.3738256, 'lng' => 17.9054373],
                ],
                'Teleki' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.7712471, 'lng' => 17.825549],
                ],
                'Tengőd' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.7047636, 'lng' => 18.096548],
                ],
                'Tikos' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.6352084, 'lng' => 17.2876693],
                ],
                'Törökkoppány' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.6016031, 'lng' => 18.0492896],
                ],
                'Torvaj' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.7660082, 'lng' => 18.0431057],
                ],
                'Tótújfalu' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 45.9026149, 'lng' => 17.6441517],
                ],
                'Újvárfalva' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.4361458, 'lng' => 17.5736366],
                ],
                'Varászló' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.4335126, 'lng' => 17.2199431],
                ],
                'Várda' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.4605278, 'lng' => 17.7402875],
                ],
                'Vése' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.4126391, 'lng' => 17.2898611],
                ],
                'Visnye' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.1908878, 'lng' => 17.6768253],
                ],
                'Visz' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.7264845, 'lng' => 17.7836718],
                ],
                'Vízvár' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.0903352, 'lng' => 17.2339769],
                ],
                'Vörs' => [
                    'constituencies' => ['Somogy 3.'],
                    'coordinates' => ['lat' => 46.6650429, 'lng' => 17.2709284],
                ],
                'Zákány' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2545763, 'lng' => 16.9488786],
                ],
                'Zákányfalu' => [
                    'constituencies' => ['Somogy 2.'],
                    'coordinates' => ['lat' => 46.2733179, 'lng' => 16.9460646],
                ],
                'Zala' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.7384404, 'lng' => 16.9152252],
                ],
                'Zamárdi' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.8840799, 'lng' => 17.9580947],
                ],
                'Zics' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.6779417, 'lng' => 17.9794041],
                ],
                'Zimány' => [
                    'constituencies' => ['Somogy 4.'],
                    'coordinates' => ['lat' => 46.4242158, 'lng' => 17.9109691],
                ],
                'Zselickisfalud' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.2734004, 'lng' => 17.734639],
                ],
                'Zselickislak' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.3135007, 'lng' => 17.7989583],
                ],
                'Zselicszentpál' => [
                    'constituencies' => ['Somogy 1.'],
                    'coordinates' => ['lat' => 46.3083853, 'lng' => 17.8208142],
                ],
            ],
            'Szabolcs-Szatmár-Bereg' => [
                'Ajak' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.1792431, 'lng' => 22.0589655],
                ],
                'Anarcs' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.1761661, 'lng' => 22.1022577],
                ],
                'Apagy' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.9602791, 'lng' => 21.9357891],
                ],
                'Aranyosapáti' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.2082674, 'lng' => 22.2571306],
                ],
                'Baktalórántháza' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.9981255, 'lng' => 22.0807519],
                ],
                'Balkány' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.768757, 'lng' => 21.859283],
                ],
                'Balsa' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 2.'],
                    'coordinates' => ['lat' => 48.1729053, 'lng' => 21.533648],
                ],
                'Barabás' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.231032, 'lng' => 22.4307119],
                ],
                'Bátorliget' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.7603153, 'lng' => 22.2709837],
                ],
                'Benk' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.3008869, 'lng' => 22.2338051],
                ],
                'Beregdaróc' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.1985108, 'lng' => 22.5320069],
                ],
                'Beregsurány' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.1617415, 'lng' => 22.5490463],
                ],
                'Berkesz' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.0949733, 'lng' => 21.9766822],
                ],
                'Besenyőd' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.9644487, 'lng' => 22.0086357],
                ],
                'Beszterec' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.1590817, 'lng' => 21.8375725],
                ],
                'Biri' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.8125733, 'lng' => 21.8531599],
                ],
                'Bököny' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.7299139, 'lng' => 21.7548153],
                ],
                'Botpalád' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.0271744, 'lng' => 22.8056055],
                ],
                'Buj' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 2.'],
                    'coordinates' => ['lat' => 48.1019047, 'lng' => 21.6459906],
                ],
                'Cégénydányád' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9321664, 'lng' => 22.5446233],
                ],
                'Csaholc' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9833733, 'lng' => 22.7323201],
                ],
                'Csaroda' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.1612875, 'lng' => 22.4596749],
                ],
                'Császló' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9131306, 'lng' => 22.7199866],
                ],
                'Csegöld' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9003571, 'lng' => 22.6814876],
                ],
                'Csenger' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.8351243, 'lng' => 22.6757695],
                ],
                'Csengersima' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.8661778, 'lng' => 22.7292015],
                ],
                'Csengerújfalu' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.8062787, 'lng' => 22.6195089],
                ],
                'Darnó' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9574368, 'lng' => 22.6612912],
                ],
                'Demecser' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.1152506, 'lng' => 21.9221551],
                ],
                'Döge' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.2602903, 'lng' => 22.0691916],
                ],
                'Dombrád' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.227121, 'lng' => 21.9264885],
                ],
                'Encsencs' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.742168, 'lng' => 22.1143996],
                ],
                'Eperjeske' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.34481, 'lng' => 22.2125079],
                ],
                'Érpatak' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.8079477, 'lng' => 21.7564396],
                ],
                'Fábiánháza' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.8439484, 'lng' => 22.3567615],
                ],
                'Fehérgyarmat' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9854319, 'lng' => 22.517771],
                ],
                'Fényeslitke' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.2690868, 'lng' => 22.1014229],
                ],
                'Fülesd' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.0244105, 'lng' => 22.6760258],
                ],
                'Fülpösdaróc' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.938571, 'lng' => 22.4798723],
                ],
                'Gacsály' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9289666, 'lng' => 22.7582359],
                ],
                'Garbolc' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9448241, 'lng' => 22.8628],
                ],
                'Gávavencsellő' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 2.'],
                    'coordinates' => ['lat' => 48.1634723, 'lng' => 21.5789275],
                ],
                'Géberjén' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.9353269, 'lng' => 22.4619299],
                ],
                'Gégény' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.1419496, 'lng' => 21.9486663],
                ],
                'Gelénes' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.1992086, 'lng' => 22.4461671],
                ],
                'Gemzse' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.1385875, 'lng' => 22.1919491],
                ],
                'Geszteréd' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.7635882, 'lng' => 21.7765872],
                ],
                'Gulács' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.0863497, 'lng' => 22.4698764],
                ],
                'Győröcske' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.3854237, 'lng' => 22.1518204],
                ],
                'Győrtelek' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.9304117, 'lng' => 22.4370403],
                ],
                'Gyügye' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9239745, 'lng' => 22.5683586],
                ],
                'Gyulaháza' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.1363353, 'lng' => 22.1135044],
                ],
                'Gyüre' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.1738096, 'lng' => 22.2700418],
                ],
                'Hermánszeg' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9039531, 'lng' => 22.6252943],
                ],
                'Hetefejércse' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.1205083, 'lng' => 22.4756381],
                ],
                'Hodász' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.9159041, 'lng' => 22.2036656],
                ],
                'Ibrány' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 2.'],
                    'coordinates' => ['lat' => 48.1267353, 'lng' => 21.7111382],
                ],
                'Ilk' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.1175204, 'lng' => 22.2325671],
                ],
                'Jánd' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.1143095, 'lng' => 22.3708104],
                ],
                'Jánkmajtis' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9388564, 'lng' => 22.6522684],
                ],
                'Jármi' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.9689613, 'lng' => 22.2526047],
                ],
                'Jéke' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.2398785, 'lng' => 22.1556034],
                ],
                'Kállósemjén' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.8566905, 'lng' => 21.9267378],
                ],
                'Kálmánháza' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 2.'],
                    'coordinates' => ['lat' => 47.8790462, 'lng' => 21.5786848],
                ],
                'Kántorjánosi' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.9341371, 'lng' => 22.1497666],
                ],
                'Kék' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.1163528, 'lng' => 21.8771784],
                ],
                'Kékcse' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.2502874, 'lng' => 22.0067158],
                ],
                'Kemecse' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.070465, 'lng' => 21.7989418],
                ],
                'Kérsemjén' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.0244253, 'lng' => 22.4188638],
                ],
                'Kisar' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.0569041, 'lng' => 22.5174513],
                ],
                'Kishódos' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9699476, 'lng' => 22.8353155],
                ],
                'Kisléta' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.8321691, 'lng' => 22.001006],
                ],
                'Kisnamény' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9545642, 'lng' => 22.6948664],
                ],
                'Kispalád' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.0206439, 'lng' => 22.8356744],
                ],
                'Kisszekeres' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9732254, 'lng' => 22.6359328],
                ],
                'Kisvárda' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.2181151, 'lng' => 22.0823557],
                ],
                'Kisvarsány' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.1494555, 'lng' => 22.2976855],
                ],
                'Kocsord' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.9412147, 'lng' => 22.3828748],
                ],
                'Kölcse' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.0528737, 'lng' => 22.7167332],
                ],
                'Komlódtótfalu' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.8425922, 'lng' => 22.7013048],
                ],
                'Komoró' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.0283962, 'lng' => 22.5901445],
                ],
                'Kömörő' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.0283962, 'lng' => 22.5901445],
                ],
                'Kótaj' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 2.'],
                    'coordinates' => ['lat' => 48.0497052, 'lng' => 21.7087174],
                ],
                'Laskod' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 48.0555244, 'lng' => 22.0414722],
                ],
                'Levelek' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.9651778, 'lng' => 21.9862961],
                ],
                'Lónya' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.3214472, 'lng' => 22.2703366],
                ],
                'Lövőpetri' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.1810149, 'lng' => 22.1974935],
                ],
                'Magosliget' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.0523699, 'lng' => 22.8646071],
                ],
                'Magy' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.9396058, 'lng' => 21.9807193],
                ],
                'Mánd' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9983429, 'lng' => 22.6071491],
                ],
                'Mándok' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.320085, 'lng' => 22.1853757],
                ],
                'Máriapócs' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.8779424, 'lng' => 22.0208433],
                ],
                'Márokpapi' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.1461042, 'lng' => 22.5083542],
                ],
                'Mátészalka' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.9515675, 'lng' => 22.316139],
                ],
                'Mátyus' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.2855799, 'lng' => 22.28153],
                ],
                'Méhtelek' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9308843, 'lng' => 22.8447128],
                ],
                'Mérk' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.782692, 'lng' => 22.3756046],
                ],
                'Mezőladány' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.2752082, 'lng' => 22.2197708],
                ],
                'Milota' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.1035992, 'lng' => 22.7814179],
                ],
                'Nábrád' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.0057936, 'lng' => 22.4489893],
                ],
                'Nagyar' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.0573077, 'lng' => 22.55463],
                ],
                'Nagycserkesz' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 2.'],
                    'coordinates' => ['lat' => 47.966385, 'lng' => 21.558204],
                ],
                'Nagydobos' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.0516746, 'lng' => 22.3055751],
                ],
                'Nagyecsed' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.8650809, 'lng' => 22.3883812],
                ],
                'Nagyhalász' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.1294884, 'lng' => 21.7555443],
                ],
                'Nagyhódos' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.962561, 'lng' => 22.8473648],
                ],
                'Nagykálló' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.8749081, 'lng' => 21.8447933],
                ],
                'Nagyszekeres' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9638758, 'lng' => 22.6071494],
                ],
                'Nagyvarsány' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.1602757, 'lng' => 22.2830314],
                ],
                'Napkor' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 47.9419501, 'lng' => 21.869],
                ],
                'Nemesborzova' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9918627, 'lng' => 22.6303524],
                ],
                'Nyírbátor' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.8390013, 'lng' => 22.130818],
                ],
                'Nyírbéltek' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.6956264, 'lng' => 22.1278759],
                ],
                'Nyírbogát' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.8019774, 'lng' => 22.0608515],
                ],
                'Nyírbogdány' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.0554457, 'lng' => 21.8751492],
                ],
                'Nyírcsaholy' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.9041907, 'lng' => 22.3373645],
                ],
                'Nyírcsászári' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.8677814, 'lng' => 22.1761922],
                ],
                'Nyírderzs' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.8982786, 'lng' => 22.1604267],
                ],
                'Nyíregyháza' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 2.', 'Szabolcs-Szatmár-Bereg 1.'],
                    'coordinates' => ['lat' => 47.9495324, 'lng' => 21.7244053],
                ],
                'Nyírgelse' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.7580202, 'lng' => 21.9822061],
                ],
                'Nyírgyulaj' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.8857956, 'lng' => 22.092384],
                ],
                'Nyíribrony' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.0175678, 'lng' => 21.961336],
                ],
                'Nyírjákó' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 48.0283205, 'lng' => 22.0752459],
                ],
                'Nyírkarász' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 48.0985976, 'lng' => 22.1001168],
                ],
                'Nyírkáta' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.8662284, 'lng' => 22.2464632],
                ],
                'Nyírkércs' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 48.0136773, 'lng' => 22.0464152],
                ],
                'Nyírlövő' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.2009467, 'lng' => 22.1862498],
                ],
                'Nyírlugos' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.6917038, 'lng' => 22.0400467],
                ],
                'Nyírmada' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 48.0661834, 'lng' => 22.1941258],
                ],
                'Nyírmeggyes' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.9144384, 'lng' => 22.2647033],
                ],
                'Nyírmihálydi' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.7363044, 'lng' => 21.9632694],
                ],
                'Nyírparasznya' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 48.0243439, 'lng' => 22.2699385],
                ],
                'Nyírpazony' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 47.9827682, 'lng' => 21.7966359],
                ],
                'Nyírpilis' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.7817586, 'lng' => 22.1869914],
                ],
                'Nyírtass' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.1139903, 'lng' => 22.0261399],
                ],
                'Nyírtelek' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 2.'],
                    'coordinates' => ['lat' => 48.0149768, 'lng' => 21.6383505],
                ],
                'Nyírtét' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.0072864, 'lng' => 21.9211203],
                ],
                'Nyírtura' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.0134266, 'lng' => 21.8296006],
                ],
                'Nyírvasvári' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.818321, 'lng' => 22.1859279],
                ],
                'Ófehértó' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.9337349, 'lng' => 22.0389497],
                ],
                'Ököritófülpös' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.9196492, 'lng' => 22.5071657],
                ],
                'Olcsva' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.0878657, 'lng' => 22.3358304],
                ],
                'Olcsvaapáti' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.0881233, 'lng' => 22.3525726],
                ],
                'Ömböly' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.6984754, 'lng' => 22.2139978],
                ],
                'Ópályi' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.99536, 'lng' => 22.3236772],
                ],
                'Őr' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.9765499, 'lng' => 22.1857739],
                ],
                'Panyola' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.044588, 'lng' => 22.3960223],
                ],
                'Pap' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.215527, 'lng' => 22.144685],
                ],
                'Papos' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.9840898, 'lng' => 22.2486205],
                ],
                'Paszab' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 2.'],
                    'coordinates' => ['lat' => 48.1474092, 'lng' => 21.6660484],
                ],
                'Pátroha' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.1694297, 'lng' => 21.9940228],
                ],
                'Pátyod' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.8667688, 'lng' => 22.6205773],
                ],
                'Penészlek' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.6382263, 'lng' => 22.1486565],
                ],
                'Penyige' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9987475, 'lng' => 22.5622405],
                ],
                'Petneháza' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 48.0559177, 'lng' => 22.0814642],
                ],
                'Piricse' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.7685263, 'lng' => 22.1489574],
                ],
                'Pócspetri' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.8815092, 'lng' => 21.9931288],
                ],
                'Porcsalma' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.8797172, 'lng' => 22.5696867],
                ],
                'Pusztadobos' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 48.0569533, 'lng' => 22.2280917],
                ],
                'Rakamaz' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 2.'],
                    'coordinates' => ['lat' => 48.1233113, 'lng' => 21.469806],
                ],
                'Ramocsaháza' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.03998, 'lng' => 21.9883004],
                ],
                'Rápolt' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.9183677, 'lng' => 22.5545786],
                ],
                'Rétközberencs' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.2057564, 'lng' => 22.009898],
                ],
                'Rohod' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 48.0272849, 'lng' => 22.1348012],
                ],
                'Rozsály' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9242143, 'lng' => 22.8003396],
                ],
                'Sényő' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.001579, 'lng' => 21.8759483],
                ],
                'Sonkád' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.0541044, 'lng' => 22.7485293],
                ],
                'Szabolcs' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 2.'],
                    'coordinates' => ['lat' => 48.172427, 'lng' => 21.4955708],
                ],
                'Szabolcsbáka' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.154569, 'lng' => 22.1418337],
                ],
                'Szabolcsveresmart' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.2913082, 'lng' => 22.0186408],
                ],
                'Szakoly' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.7603635, 'lng' => 21.9037022],
                ],
                'Szamosangyalos' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.8722318, 'lng' => 22.6517066],
                ],
                'Szamosbecs' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.8587626, 'lng' => 22.6886451],
                ],
                'Szamoskér' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.015425, 'lng' => 22.4159595],
                ],
                'Szamossályi' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.906812, 'lng' => 22.6079817],
                ],
                'Szamosszeg' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.0435642, 'lng' => 22.3666149],
                ],
                'Szamostatárfalva' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.8754056, 'lng' => 22.6659656],
                ],
                'Szamosújlak' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9181367, 'lng' => 22.5873771],
                ],
                'Szatmárcseke' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.0870877, 'lng' => 22.6254074],
                ],
                'Székely' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.0572496, 'lng' => 21.9372893],
                ],
                'Szorgalmatos' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 2.'],
                    'coordinates' => ['lat' => 47.9878281, 'lng' => 21.3720783],
                ],
                'Tákos' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.1513483, 'lng' => 22.4284184],
                ],
                'Tarpa' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.1055366, 'lng' => 22.5316393],
                ],
                'Terem' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.8004931, 'lng' => 22.2802087],
                ],
                'Tiborszállás' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.8155192, 'lng' => 22.4091458],
                ],
                'Timár' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 2.'],
                    'coordinates' => ['lat' => 48.1561686, 'lng' => 21.463415],
                ],
                'Tiszaadony' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.2245634, 'lng' => 22.2927152],
                ],
                'Tiszabecs' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.0971659, 'lng' => 22.8199139],
                ],
                'Tiszabercel' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 2.'],
                    'coordinates' => ['lat' => 48.1564745, 'lng' => 21.6463652],
                ],
                'Tiszabezdéd' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.3633432, 'lng' => 22.151102],
                ],
                'Tiszacsécse' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.109593, 'lng' => 22.745917],
                ],
                'Tiszadada' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 2.'],
                    'coordinates' => ['lat' => 48.0275909, 'lng' => 21.2438163],
                ],
                'Tiszadob' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 2.'],
                    'coordinates' => ['lat' => 48.0063029, 'lng' => 21.170962],
                ],
                'Tiszaeszlár' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 2.'],
                    'coordinates' => ['lat' => 48.0375746, 'lng' => 21.4561979],
                ],
                'Tiszakanyár' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.2498264, 'lng' => 21.9605445],
                ],
                'Tiszakerecseny' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.2580278, 'lng' => 22.2988224],
                ],
                'Tiszakóród' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.1028617, 'lng' => 22.7115037],
                ],
                'Tiszalök' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 2.'],
                    'coordinates' => ['lat' => 48.0159357, 'lng' => 21.376707],
                ],
                'Tiszamogyorós' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.322194, 'lng' => 22.229311],
                ],
                'Tiszanagyfalu' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 2.'],
                    'coordinates' => ['lat' => 48.0920604, 'lng' => 21.4730428],
                ],
                'Tiszarád' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.1222821, 'lng' => 21.7979516],
                ],
                'Tiszaszalka' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.188112, 'lng' => 22.313724],
                ],
                'Tiszaszentmárton' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.3787027, 'lng' => 22.2283189],
                ],
                'Tiszatelek' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.1910971, 'lng' => 21.814554],
                ],
                'Tiszavasvári' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 2.'],
                    'coordinates' => ['lat' => 47.9600496, 'lng' => 21.36113],
                ],
                'Tiszavid' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.1951278, 'lng' => 22.2945875],
                ],
                'Tisztaberek' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9579481, 'lng' => 22.7904551],
                ],
                'Tivadar' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.0639778, 'lng' => 22.5131187],
                ],
                'Tornyospálca' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.2673418, 'lng' => 22.1808792],
                ],
                'Tunyogmatolcs' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.971572, 'lng' => 22.453838],
                ],
                'Túristvándi' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.0514887, 'lng' => 22.6458771],
                ],
                'Túrricse' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9739151, 'lng' => 22.7617628],
                ],
                'Tuzsér' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.3401948, 'lng' => 22.121069],
                ],
                'Tyukod' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.8530348, 'lng' => 22.5568265],
                ],
                'Újdombrád' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.1947877, 'lng' => 21.8716915],
                ],
                'Újfehértó' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.803116, 'lng' => 21.6801985],
                ],
                'Újkenéz' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.2505505, 'lng' => 22.2232613],
                ],
                'Ura' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 47.8178841, 'lng' => 22.6022636],
                ],
                'Uszka' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.0720607, 'lng' => 22.8565053],
                ],
                'Vaja' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 5.'],
                    'coordinates' => ['lat' => 48.0001294, 'lng' => 22.1639152],
                ],
                'Vállaj' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 6.'],
                    'coordinates' => ['lat' => 47.763359, 'lng' => 22.3813023],
                ],
                'Vámosatya' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.1993374, 'lng' => 22.4084398],
                ],
                'Vámosoroszi' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9921699, 'lng' => 22.6824514],
                ],
                'Vásárosnamény' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.1161031, 'lng' => 22.3108004],
                ],
                'Vasmegyer' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 3.'],
                    'coordinates' => ['lat' => 48.1127497, 'lng' => 21.8172458],
                ],
                'Záhony' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.4046827, 'lng' => 22.177433],
                ],
                'Zajta' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.905893, 'lng' => 22.7973796],
                ],
                'Zsarolyán' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 47.9492285, 'lng' => 22.587805],
                ],
                'Zsurk' => [
                    'constituencies' => ['Szabolcs-Szatmár-Bereg 4.'],
                    'coordinates' => ['lat' => 48.4061953, 'lng' => 22.2194758],
                ],
            ],
            'Tolna' => [
                'Alsónána' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.2509368, 'lng' => 18.658412],
                ],
                'Alsónyék' => [
                    'constituencies' => ['Tolna 1.'],
                    'coordinates' => ['lat' => 46.2026754, 'lng' => 18.7347437],
                ],
                'Aparhant' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.3317074, 'lng' => 18.4514616],
                ],
                'Attala' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.3775653, 'lng' => 18.0648797],
                ],
                'Báta' => [
                    'constituencies' => ['Tolna 1.'],
                    'coordinates' => ['lat' => 46.126703, 'lng' => 18.7741311],
                ],
                'Bátaapáti' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.2232332, 'lng' => 18.5987202],
                ],
                'Bátaszék' => [
                    'constituencies' => ['Tolna 1.'],
                    'coordinates' => ['lat' => 46.1933979, 'lng' => 18.7213706],
                ],
                'Belecska' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.641649, 'lng' => 18.4157324],
                ],
                'Bikács' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.67563, 'lng' => 18.6663013],
                ],
                'Bogyiszló' => [
                    'constituencies' => ['Tolna 1.'],
                    'coordinates' => ['lat' => 46.3859236, 'lng' => 18.8241471],
                ],
                'Bölcske' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.7425961, 'lng' => 18.9608558],
                ],
                'Bonyhád' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.2981016, 'lng' => 18.5263534],
                ],
                'Bonyhádvarasd' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.3683239, 'lng' => 18.4807472],
                ],
                'Cikó' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.2531394, 'lng' => 18.5578434],
                ],
                'Csibrák' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.4644224, 'lng' => 18.3459552],
                ],
                'Csikóstőttős' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.338796, 'lng' => 18.1591084],
                ],
                'Dalmand' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.492377, 'lng' => 18.1900812],
                ],
                'Decs' => [
                    'constituencies' => ['Tolna 1.'],
                    'coordinates' => ['lat' => 46.2838413, 'lng' => 18.7579886],
                ],
                'Diósberény' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.5328096, 'lng' => 18.4438026],
                ],
                'Döbrököz' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.4223095, 'lng' => 18.2489256],
                ],
                'Dombóvár' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.3828653, 'lng' => 18.1452879],
                ],
                'Dunaföldvár' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.806202, 'lng' => 18.9167877],
                ],
                'Dunaszentgyörgy' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.5278409, 'lng' => 18.8163311],
                ],
                'Dúzs' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.4907232, 'lng' => 18.3807104],
                ],
                'Értény' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.6120053, 'lng' => 18.1346646],
                ],
                'Fácánkert' => [
                    'constituencies' => ['Tolna 1.'],
                    'coordinates' => ['lat' => 46.4468316, 'lng' => 18.7293668],
                ],
                'Fadd' => [
                    'constituencies' => ['Tolna 1.'],
                    'coordinates' => ['lat' => 46.4647628, 'lng' => 18.821837],
                ],
                'Felsőnána' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.4672021, 'lng' => 18.5269862],
                ],
                'Felsőnyék' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.7907662, 'lng' => 18.2900452],
                ],
                'Fürged' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.7227603, 'lng' => 18.3108543],
                ],
                'Gerjen' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.4905666, 'lng' => 18.9021977],
                ],
                'Grábóc' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.2891636, 'lng' => 18.6077594],
                ],
                'Gyönk' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.5576484, 'lng' => 18.4776294],
                ],
                'Györe' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.2949736, 'lng' => 18.3985102],
                ],
                'Györköny' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.6355888, 'lng' => 18.6957842],
                ],
                'Gyulaj' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.508822, 'lng' => 18.2931741],
                ],
                'Harc' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.4029326, 'lng' => 18.6219403],
                ],
                'Hőgyész' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.4965037, 'lng' => 18.4197183],
                ],
                'Iregszemcse' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.6960644, 'lng' => 18.1800363],
                ],
                'Izmény' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.3144923, 'lng' => 18.4134216],
                ],
                'Jágónak' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.3140625, 'lng' => 18.0917526],
                ],
                'Kajdacs' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.5627338, 'lng' => 18.6235951],
                ],
                'Kakasd' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.3487322, 'lng' => 18.590752],
                ],
                'Kalaznó' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.5013806, 'lng' => 18.4752202],
                ],
                'Kapospula' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.3760312, 'lng' => 18.099275],
                ],
                'Kaposszekcső' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.3302668, 'lng' => 18.1315267],
                ],
                'Keszőhidegkút' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.6122875, 'lng' => 18.4238767],
                ],
                'Kéty' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.4391854, 'lng' => 18.5230523],
                ],
                'Kisdorog' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.3881178, 'lng' => 18.4947501],
                ],
                'Kismányok' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.2730142, 'lng' => 18.4738971],
                ],
                'Kisszékely' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.6793599, 'lng' => 18.5383296],
                ],
                'Kistormás' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.5025334, 'lng' => 18.5652942],
                ],
                'Kisvejke' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.3809967, 'lng' => 18.413523],
                ],
                'Kocsola' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.5271978, 'lng' => 18.1826046],
                ],
                'Kölesd' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.5071238, 'lng' => 18.5862709],
                ],
                'Koppányszántó' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.5945331, 'lng' => 18.1089302],
                ],
                'Kurd' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.4467219, 'lng' => 18.3152818],
                ],
                'Lápafő' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.5127433, 'lng' => 18.0594511],
                ],
                'Lengyel' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.3752668, 'lng' => 18.368477],
                ],
                'Madocsa' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.6879756, 'lng' => 18.9527688],
                ],
                'Magyarkeszi' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.7496915, 'lng' => 18.2317537],
                ],
                'Medina' => [
                    'constituencies' => ['Tolna 1.'],
                    'coordinates' => ['lat' => 46.4743386, 'lng' => 18.642078],
                ],
                'Miszla' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.6325553, 'lng' => 18.480824],
                ],
                'Mőcsény' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.2611641, 'lng' => 18.592209],
                ],
                'Mórágy' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.2151502, 'lng' => 18.6422894],
                ],
                'Mucsfa' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.3548859, 'lng' => 18.4180675],
                ],
                'Mucsi' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.4296609, 'lng' => 18.393435],
                ],
                'Murga' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.4599737, 'lng' => 18.486594],
                ],
                'Nagydorog' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.6223848, 'lng' => 18.6582474],
                ],
                'Nagykónyi' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.5911428, 'lng' => 18.2001785],
                ],
                'Nagymányok' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.2803522, 'lng' => 18.4555131],
                ],
                'Nagyszékely' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.650546, 'lng' => 18.5290114],
                ],
                'Nagyszokoly' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.7209057, 'lng' => 18.211549],
                ],
                'Nagyvejke' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.3775192, 'lng' => 18.4451612],
                ],
                'Nak' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.478707, 'lng' => 18.0547201],
                ],
                'Németkér' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.7216958, 'lng' => 18.7575485],
                ],
                'Őcsény' => [
                    'constituencies' => ['Tolna 1.'],
                    'coordinates' => ['lat' => 46.3149257, 'lng' => 18.7544131],
                ],
                'Ozora' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.7525477, 'lng' => 18.3908146],
                ],
                'Paks' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.6060722, 'lng' => 18.8546832],
                ],
                'Pálfa' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.7137053, 'lng' => 18.6130159],
                ],
                'Pári' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.5785918, 'lng' => 18.2569677],
                ],
                'Pincehely' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.682893, 'lng' => 18.4361751],
                ],
                'Pörböly' => [
                    'constituencies' => ['Tolna 1.'],
                    'coordinates' => ['lat' => 46.2047205, 'lng' => 18.8120148],
                ],
                'Pusztahencse' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.59362, 'lng' => 18.7143108],
                ],
                'Regöly' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.5792329, 'lng' => 18.3851686],
                ],
                'Sárpilis' => [
                    'constituencies' => ['Tolna 1.'],
                    'coordinates' => ['lat' => 46.2477756, 'lng' => 18.7400703],
                ],
                'Sárszentlőrinc' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.6251475, 'lng' => 18.6058426],
                ],
                'Simontornya' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.7535532, 'lng' => 18.5475948],
                ],
                'Sióagárd' => [
                    'constituencies' => ['Tolna 1.'],
                    'coordinates' => ['lat' => 46.3921386, 'lng' => 18.6527402],
                ],
                'Szakadát' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.5401505, 'lng' => 18.4701375],
                ],
                'Szakály' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.5238677, 'lng' => 18.3824635],
                ],
                'Szakcs' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.5416281, 'lng' => 18.1101296],
                ],
                'Szálka' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.2775223, 'lng' => 18.6295747],
                ],
                'Szárazd' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.5711003, 'lng' => 18.4251139],
                ],
                'Szedres' => [
                    'constituencies' => ['Tolna 1.'],
                    'coordinates' => ['lat' => 46.478823, 'lng' => 18.6821789],
                ],
                'Szekszárd' => [
                    'constituencies' => ['Tolna 1.'],
                    'coordinates' => ['lat' => 46.3474326, 'lng' => 18.7062293],
                ],
                'Tamási' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.6332018, 'lng' => 18.2854998],
                ],
                'Tengelic' => [
                    'constituencies' => ['Tolna 1.'],
                    'coordinates' => ['lat' => 46.527182, 'lng' => 18.7106581],
                ],
                'Tevel' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.4122992, 'lng' => 18.4553455],
                ],
                'Tolna' => [
                    'constituencies' => ['Tolna 1.'],
                    'coordinates' => ['lat' => 46.4258265, 'lng' => 18.7752069],
                ],
                'Tolnanémedi' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.7158954, 'lng' => 18.4798194],
                ],
                'Udvari' => [
                    'constituencies' => ['Tolna 3.'],
                    'coordinates' => ['lat' => 46.5948722, 'lng' => 18.5111762],
                ],
                'Újireg' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.6581348, 'lng' => 18.1754072],
                ],
                'Váralja' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.2693702, 'lng' => 18.4299158],
                ],
                'Várdomb' => [
                    'constituencies' => ['Tolna 1.'],
                    'coordinates' => ['lat' => 46.2472326, 'lng' => 18.6882931],
                ],
                'Várong' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.527584, 'lng' => 18.0436246],
                ],
                'Varsád' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.5249663, 'lng' => 18.5192473],
                ],
                'Závod' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.3938012, 'lng' => 18.4158786],
                ],
                'Zomba' => [
                    'constituencies' => ['Tolna 2.'],
                    'coordinates' => ['lat' => 46.4132263, 'lng' => 18.5628727],
                ],
            ],
            'Vas' => [
                'Acsád' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3231668, 'lng' => 16.734166],
                ],
                'Alsószölnök' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9264207, 'lng' => 16.2033509],
                ],
                'Alsóújlak' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0812626, 'lng' => 16.8537769],
                ],
                'Andrásfa' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9622336, 'lng' => 16.7944011],
                ],
                'Apátistvánfalva' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.8971924, 'lng' => 16.2550914],
                ],
                'Bajánsenye' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.8040492, 'lng' => 16.3840214],
                ],
                'Balogunyom' => [
                    'constituencies' => ['Vas 1.'],
                    'coordinates' => ['lat' => 47.1582309, 'lng' => 16.6442734],
                ],
                'Bejcgyertyános' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1532405, 'lng' => 16.9223338],
                ],
                'Bérbaltavár' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0095082, 'lng' => 16.9638949],
                ],
                'Bő' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3665732, 'lng' => 16.8160271],
                ],
                'Boba' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.183219, 'lng' => 17.1872226],
                ],
                'Bögöt' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.2508929, 'lng' => 16.8291948],
                ],
                'Bögöte' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.08163, 'lng' => 17.046715],
                ],
                'Borgáta' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1606793, 'lng' => 17.0804925],
                ],
                'Bozsok' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3236358, 'lng' => 16.4902867],
                ],
                'Bozzai' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.2082908, 'lng' => 16.7648284],
                ],
                'Bucsu' => [
                    'constituencies' => ['Vas 1.'],
                    'coordinates' => ['lat' => 47.264515, 'lng' => 16.4938597],
                ],
                'Bük' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.383876, 'lng' => 16.7616288],
                ],
                'Cák' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3541342, 'lng' => 16.5146569],
                ],
                'Celldömölk' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.2472192, 'lng' => 17.1489022],
                ],
                'Chernelházadamonya' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3610125, 'lng' => 16.8427541],
                ],
                'Csákánydoroszló' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9727783, 'lng' => 16.502247],
                ],
                'Csánig' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.4320792, 'lng' => 17.0239011],
                ],
                'Csehi' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.034688, 'lng' => 16.9449451],
                ],
                'Csehimindszent' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0481602, 'lng' => 16.9538392],
                ],
                'Csempeszkopács' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1539643, 'lng' => 16.8054448],
                ],
                'Csénye' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.2366653, 'lng' => 16.8685909],
                ],
                'Csepreg' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.4001421, 'lng' => 16.7084108],
                ],
                'Csipkerek' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.07421, 'lng' => 16.9390741],
                ],
                'Csönge' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3516237, 'lng' => 17.0642799],
                ],
                'Csörötnek' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9498177, 'lng' => 16.3707766],
                ],
                'Daraboshegy' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9597876, 'lng' => 16.5686069],
                ],
                'Döbörhegy' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9908072, 'lng' => 16.7014221],
                ],
                'Döröske' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0106058, 'lng' => 16.6965645],
                ],
                'Dozmat' => [
                    'constituencies' => ['Vas 1.'],
                    'coordinates' => ['lat' => 47.2337587, 'lng' => 16.5137134],
                ],
                'Duka' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1225983, 'lng' => 17.1127855],
                ],
                'Egervölgy' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1192602, 'lng' => 16.9085679],
                ],
                'Egyházashetye' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1687724, 'lng' => 17.1192876],
                ],
                'Egyházashollós' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0570744, 'lng' => 16.6941255],
                ],
                'Egyházasrádóc' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0872194, 'lng' => 16.6175049],
                ],
                'Felsőcsatár' => [
                    'constituencies' => ['Vas 1.'],
                    'coordinates' => ['lat' => 47.2121988, 'lng' => 16.4442598],
                ],
                'Felsőjánosfa' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.8458383, 'lng' => 16.5495916],
                ],
                'Felsőmarác' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.932447, 'lng' => 16.5186679],
                ],
                'Felsőszölnök' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.8777997, 'lng' => 16.1654522],
                ],
                'Gasztony' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9644634, 'lng' => 16.4490856],
                ],
                'Gencsapáti' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.2845326, 'lng' => 16.5962741],
                ],
                'Gérce' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.2146606, 'lng' => 17.0157356],
                ],
                'Gersekarát' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9733809, 'lng' => 16.7428114],
                ],
                'Gór' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3575615, 'lng' => 16.8016105],
                ],
                'Gyanógeregye' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1230625, 'lng' => 16.7645322],
                ],
                'Gyöngyösfalu' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3165088, 'lng' => 16.5846929],
                ],
                'Győrvár' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9871238, 'lng' => 16.8414767],
                ],
                'Halastó' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9498086, 'lng' => 16.6860316],
                ],
                'Halogy' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9712205, 'lng' => 16.5636252],
                ],
                'Harasztifalu' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0496428, 'lng' => 16.5523482],
                ],
                'Hegyfalu' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3528653, 'lng' => 16.8827556],
                ],
                'Hegyháthodász' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9356384, 'lng' => 16.6559218],
                ],
                'Hegyhátsál' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9591341, 'lng' => 16.6439504],
                ],
                'Hegyhátszentjakab' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.8695811, 'lng' => 16.5470697],
                ],
                'Hegyhátszentmárton' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9355501, 'lng' => 16.4800829],
                ],
                'Hegyhátszentpéter' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9813098, 'lng' => 16.814706],
                ],
                'Horvátlövő' => [
                    'constituencies' => ['Vas 1.'],
                    'coordinates' => ['lat' => 47.1798129, 'lng' => 16.4622921],
                ],
                'Horvátzsidány' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.4112203, 'lng' => 16.6263413],
                ],
                'Hosszúpereszteg' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0957663, 'lng' => 17.0250077],
                ],
                'Ikervár' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.2071732, 'lng' => 16.8931065],
                ],
                'Iklanberény' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.4241017, 'lng' => 16.8050931],
                ],
                'Ispánk' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.8641321, 'lng' => 16.4397119],
                ],
                'Ivánc' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9405932, 'lng' => 16.4998554],
                ],
                'Ják' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1432823, 'lng' => 16.5818826],
                ],
                'Jákfa' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3386959, 'lng' => 16.9557811],
                ],
                'Jánosháza' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1199783, 'lng' => 17.1634865],
                ],
                'Káld' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.16296, 'lng' => 17.0441807],
                ],
                'Kám' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1033571, 'lng' => 16.8777372],
                ],
                'Karakó' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1157895, 'lng' => 17.2005739],
                ],
                'Katafa' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9782619, 'lng' => 16.6326072],
                ],
                'Keléd' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0757981, 'lng' => 17.1171154],
                ],
                'Kemeneskápolna' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.2086015, 'lng' => 17.1066617],
                ],
                'Kemenesmagasi' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3318924, 'lng' => 17.2087323],
                ],
                'Kemenesmihályfa' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.2854325, 'lng' => 17.1130787],
                ],
                'Kemenespálfa' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1367758, 'lng' => 17.1753388],
                ],
                'Kemenessömjén' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.2927394, 'lng' => 17.1296661],
                ],
                'Kemenesszentmárton' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.2945186, 'lng' => 17.1616267],
                ],
                'Kemestaródfa' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9987913, 'lng' => 16.528194],
                ],
                'Kenéz' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.2039567, 'lng' => 16.7872306],
                ],
                'Kenyeri' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3848616, 'lng' => 17.0906553],
                ],
                'Kercaszomor' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.7899007, 'lng' => 16.3551058],
                ],
                'Kerkáskápolna' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.7855514, 'lng' => 16.4275904],
                ],
                'Kétvölgy' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.8809139, 'lng' => 16.2234385],
                ],
                'Kisrákos' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.8601504, 'lng' => 16.498111],
                ],
                'Kissomlyó' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1424719, 'lng' => 17.1020651],
                ],
                'Kisunyom' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1461871, 'lng' => 16.6414882],
                ],
                'Kiszsidány' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.4098186, 'lng' => 16.6398043],
                ],
                'Köcsk' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.192077, 'lng' => 17.1076143],
                ],
                'Kondorfa' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.8970068, 'lng' => 16.3988359],
                ],
                'Körmend' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.014104, 'lng' => 16.5957667],
                ],
                'Kőszeg' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3859176, 'lng' => 16.5397128],
                ],
                'Kőszegdoroszló' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3456463, 'lng' => 16.5425445],
                ],
                'Kőszegpaty' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.32596, 'lng' => 16.6491755],
                ],
                'Kőszegszerdahely' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3410657, 'lng' => 16.5149802],
                ],
                'Lócs' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.4046597, 'lng' => 16.8117515],
                ],
                'Lukácsháza' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.33525, 'lng' => 16.5786444],
                ],
                'Magyarlak' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9507279, 'lng' => 16.3454576],
                ],
                'Magyarnádalja' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0104989, 'lng' => 16.5352905],
                ],
                'Magyarszecsőd' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0366998, 'lng' => 16.6483033],
                ],
                'Magyarszombatfa' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.7599374, 'lng' => 16.3421845],
                ],
                'Meggyeskovácsi' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1570573, 'lng' => 16.8651701],
                ],
                'Megyehíd' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.2123771, 'lng' => 16.8433327],
                ],
                'Mersevát' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.2890403, 'lng' => 17.2052354],
                ],
                'Mesterháza' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3735489, 'lng' => 16.8620631],
                ],
                'Mesteri' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.2239279, 'lng' => 17.0888689],
                ],
                'Meszlen' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3319585, 'lng' => 16.7007666],
                ],
                'Mikosszéplak' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.033147, 'lng' => 16.9764858],
                ],
                'Molnaszecsőd' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0445494, 'lng' => 16.6747582],
                ],
                'Nádasd' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9658004, 'lng' => 16.6109092],
                ],
                'Nagygeresd' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3929157, 'lng' => 16.9285797],
                ],
                'Nagykölked' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0665282, 'lng' => 16.5524757],
                ],
                'Nagymizdó' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9903675, 'lng' => 16.654173],
                ],
                'Nagyrákos' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.8294165, 'lng' => 16.459137],
                ],
                'Nagysimonyi' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.2637179, 'lng' => 17.0628845],
                ],
                'Nagytilaj' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9763734, 'lng' => 16.9635778],
                ],
                'Nárai' => [
                    'constituencies' => ['Vas 1.'],
                    'coordinates' => ['lat' => 47.1925946, 'lng' => 16.5554133],
                ],
                'Narda' => [
                    'constituencies' => ['Vas 1.'],
                    'coordinates' => ['lat' => 47.2388584, 'lng' => 16.4571612],
                ],
                'Nemesbőd' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.2705814, 'lng' => 16.7348137],
                ],
                'Nemescsó' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3493052, 'lng' => 16.6160971],
                ],
                'Nemeskeresztúr' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0921345, 'lng' => 17.1921805],
                ],
                'Nemeskocs' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.2025872, 'lng' => 17.1856185],
                ],
                'Nemeskolta' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1414399, 'lng' => 16.7673424],
                ],
                'Nemesládony' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.4002119, 'lng' => 16.8867598],
                ],
                'Nemesmedves' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9982991, 'lng' => 16.4013115],
                ],
                'Nemesrempehollós' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0942864, 'lng' => 16.6823945],
                ],
                'Nick' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.4026711, 'lng' => 17.0144998],
                ],
                'Nyőgér' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1812447, 'lng' => 16.9384423],
                ],
                'Olaszfa' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0162422, 'lng' => 16.8851611],
                ],
                'Ölbő' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3006879, 'lng' => 16.8604334],
                ],
                'Ólmod' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.4147263, 'lng' => 16.591081],
                ],
                'Orfalu' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.8803448, 'lng' => 16.2687618],
                ],
                'Őrimagyarósd' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.887774, 'lng' => 16.5357604],
                ],
                'Őriszentpéter' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.8418469, 'lng' => 16.4178428],
                ],
                'Ostffyasszonyfa' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3285365, 'lng' => 17.0432368],
                ],
                'Oszkó' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.043107, 'lng' => 16.8759393],
                ],
                'Pácsony' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0161538, 'lng' => 16.8553765],
                ],
                'Pankasz' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.839247, 'lng' => 16.4994581],
                ],
                'Pápoc' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.4117693, 'lng' => 17.1302583],
                ],
                'Pecöl' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.2045471, 'lng' => 16.8248879],
                ],
                'Perenye' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.290658, 'lng' => 16.5762116],
                ],
                'Peresznye' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.4233437, 'lng' => 16.6513097],
                ],
                'Petőmihályfa' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9848606, 'lng' => 16.7832297],
                ],
                'Pinkamindszent' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0372407, 'lng' => 16.485094],
                ],
                'Pornóapáti' => [
                    'constituencies' => ['Vas 1.'],
                    'coordinates' => ['lat' => 47.156681, 'lng' => 16.46456],
                ],
                'Porpác' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.2417598, 'lng' => 16.8033727],
                ],
                'Pósfa' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3300051, 'lng' => 16.8521541],
                ],
                'Püspökmolnári' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0879582, 'lng' => 16.7907095],
                ],
                'Pusztacsó' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3318373, 'lng' => 16.6206],
                ],
                'Rábagyarmat' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9412658, 'lng' => 16.4192218],
                ],
                'Rábahídvég' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0715547, 'lng' => 16.7431766],
                ],
                'Rábapaty' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3025182, 'lng' => 16.9318907],
                ],
                'Rábatöttös' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1325451, 'lng' => 16.8088561],
                ],
                'Rádóckölked' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0747624, 'lng' => 16.5854566],
                ],
                'Rátót' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.96521, 'lng' => 16.4277497],
                ],
                'Répcelak' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.4202895, 'lng' => 17.0175812],
                ],
                'Répceszentgyörgy' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3512406, 'lng' => 16.846689],
                ],
                'Rönök' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9783939, 'lng' => 16.3630549],
                ],
                'Rum' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1285518, 'lng' => 16.8427532],
                ],
                'Sajtoskál' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.4016707, 'lng' => 16.8557046],
                ],
                'Salköveskút' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.2962416, 'lng' => 16.6956469],
                ],
                'Sárfimizdó' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9410657, 'lng' => 16.7155669],
                ],
                'Sárvár' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.2524196, 'lng' => 16.9294866],
                ],
                'Sé' => [
                    'constituencies' => ['Vas 1.'],
                    'coordinates' => ['lat' => 47.2416682, 'lng' => 16.5535311],
                ],
                'Simaság' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.4254643, 'lng' => 16.8417237],
                ],
                'Sitke' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.2420916, 'lng' => 17.0249262],
                ],
                'Söpte' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.2839482, 'lng' => 16.6531085],
                ],
                'Sorkifalud' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1323074, 'lng' => 16.7426484],
                ],
                'Sorkikápolna' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.140292, 'lng' => 16.6984957],
                ],
                'Sorokpolány' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1359787, 'lng' => 16.6727572],
                ],
                'Sótony' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1949744, 'lng' => 16.9473124],
                ],
                'Szaknyér' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.8645834, 'lng' => 16.5271244],
                ],
                'Szakonyfalu' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.923766, 'lng' => 16.2279782],
                ],
                'Szalafő' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.8645176, 'lng' => 16.3569789],
                ],
                'Szarvaskend' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9877111, 'lng' => 16.6747003],
                ],
                'Szatta' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.7981357, 'lng' => 16.4803112],
                ],
                'Szeleste' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3133052, 'lng' => 16.8281074],
                ],
                'Szemenye' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1011188, 'lng' => 16.9046117],
                ],
                'Szentgotthárd' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9500038, 'lng' => 16.2853985],
                ],
                'Szentpéterfa' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0910287, 'lng' => 16.4798379],
                ],
                'Szergény' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3282331, 'lng' => 17.2686117],
                ],
                'Szőce' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.8871138, 'lng' => 16.5708807],
                ],
                'Szombathely' => [
                    'constituencies' => ['Vas 1.'],
                    'coordinates' => ['lat' => 47.2306851, 'lng' => 16.6218441],
                ],
                'Tanakajd' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1904428, 'lng' => 16.7346502],
                ],
                'Táplánszentkereszt' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1974582, 'lng' => 16.6982875],
                ],
                'Telekes' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.942122, 'lng' => 16.7685577],
                ],
                'Tokorcs' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.2697507, 'lng' => 17.0972077],
                ],
                'Tömörd' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3645799, 'lng' => 16.6748701],
                ],
                'Tompaládony' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3800101, 'lng' => 16.8828359],
                ],
                'Tormásliget' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.4291621, 'lng' => 16.777497],
                ],
                'Torony' => [
                    'constituencies' => ['Vas 1.'],
                    'coordinates' => ['lat' => 47.2387668, 'lng' => 16.5347532],
                ],
                'Uraiújfalu' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3702705, 'lng' => 16.9785971],
                ],
                'Vámoscsalád' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3906111, 'lng' => 16.9694801],
                ],
                'Vasalja' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0145565, 'lng' => 16.5123032],
                ],
                'Vásárosmiske' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.2083526, 'lng' => 17.0644427],
                ],
                'Vasasszonyfa' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3117118, 'lng' => 16.6709709],
                ],
                'Vasegerszeg' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3714688, 'lng' => 16.9176125],
                ],
                'Vashosszúfalu' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1126505, 'lng' => 17.0616113],
                ],
                'Vaskeresztes' => [
                    'constituencies' => ['Vas 1.'],
                    'coordinates' => ['lat' => 47.1933189, 'lng' => 16.448286],
                ],
                'Vassurány' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.2838116, 'lng' => 16.7024602],
                ],
                'Vasszécseny' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1813996, 'lng' => 16.7600901],
                ],
                'Vasszentmihály' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.9692387, 'lng' => 16.4067431],
                ],
                'Vasszilvágy' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3013394, 'lng' => 16.7520345],
                ],
                'Vasvár' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.0496603, 'lng' => 16.8019336],
                ],
                'Vát' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.2802481, 'lng' => 16.7768551],
                ],
                'Velem' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3459436, 'lng' => 16.4912682],
                ],
                'Velemér' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.7393717, 'lng' => 16.3683172],
                ],
                'Vép' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.2273595, 'lng' => 16.7209448],
                ],
                'Viszák' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 46.8821737, 'lng' => 16.4964997],
                ],
                'Vönöck' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3127049, 'lng' => 17.1581982],
                ],
                'Zsédeny' => [
                    'constituencies' => ['Vas 2.'],
                    'coordinates' => ['lat' => 47.3400117, 'lng' => 16.9030587],
                ],
                'Zsennye' => [
                    'constituencies' => ['Vas 3.'],
                    'coordinates' => ['lat' => 47.1110141, 'lng' => 16.8202575],
                ],
            ],
            'Veszprém' => [
                'Ábrahámhegy' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.815857, 'lng' => 17.5662827],
                ],
                'Adásztevel' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3031533, 'lng' => 17.5394985],
                ],
                'Adorjánháza' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2430444, 'lng' => 17.2413994],
                ],
                'Ajka' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.1036349, 'lng' => 17.5517783],
                ],
                'Alsóörs' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9864994, 'lng' => 17.9714286],
                ],
                'Apácatorna' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.1124815, 'lng' => 17.294901],
                ],
                'Aszófő' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9280454, 'lng' => 17.8329477],
                ],
                'Badacsonytomaj' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.8025268, 'lng' => 17.5108737],
                ],
                'Badacsonytördemic' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.8017556, 'lng' => 17.4744533],
                ],
                'Bakonybél' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2541292, 'lng' => 17.7282211],
                ],
                'Bakonyjákó' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2247141, 'lng' => 17.6015604],
                ],
                'Bakonykoppány' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3302313, 'lng' => 17.6864904],
                ],
                'Bakonynána' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.2810832, 'lng' => 17.9693318],
                ],
                'Bakonyoszlop' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.344273, 'lng' => 17.9254272],
                ],
                'Bakonypölöske' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2081674, 'lng' => 17.4867946],
                ],
                'Bakonyság' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.4002271, 'lng' => 17.6511633],
                ],
                'Bakonyszentiván' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3903378, 'lng' => 17.67112],
                ],
                'Bakonyszentkirály' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3639954, 'lng' => 17.8839478],
                ],
                'Bakonyszücs' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3426492, 'lng' => 17.6824498],
                ],
                'Bakonytamási' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.4120832, 'lng' => 17.732416],
                ],
                'Balatonakali' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.8828526, 'lng' => 17.7514452],
                ],
                'Balatonakarattya' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 47.0128489, 'lng' => 18.163036],
                ],
                'Balatonalmádi' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 47.0317027, 'lng' => 18.0086997],
                ],
                'Balatoncsicsó' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9267746, 'lng' => 17.668777],
                ],
                'Balatonederics' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.8060711, 'lng' => 17.3808427],
                ],
                'Balatonfőkajár' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 47.0196638, 'lng' => 18.2109785],
                ],
                'Balatonfüred' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9599039, 'lng' => 17.8851202],
                ],
                'Balatonfűzfő' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 47.0680827, 'lng' => 18.0352214],
                ],
                'Balatonhenye' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.91221, 'lng' => 17.6144569],
                ],
                'Balatonkenese' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 47.0285715, 'lng' => 18.1277871],
                ],
                'Balatonrendes' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.8282436, 'lng' => 17.5830653],
                ],
                'Balatonszepezd' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.8506952, 'lng' => 17.6584227],
                ],
                'Balatonszőlős' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9658557, 'lng' => 17.8272761],
                ],
                'Balatonudvari' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9010132, 'lng' => 17.7996485],
                ],
                'Bánd' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.1230821, 'lng' => 17.7797124],
                ],
                'Barnag' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9789969, 'lng' => 17.7467265],
                ],
                'Bazsi' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.9328475, 'lng' => 17.2461264],
                ],
                'Béb' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.343322, 'lng' => 17.6016451],
                ],
                'Békás' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3320031, 'lng' => 17.3514484],
                ],
                'Berhida' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 47.1131864, 'lng' => 18.127434],
                ],
                'Bodorfa' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.075444, 'lng' => 17.3428552],
                ],
                'Borszörcsök' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.1338396, 'lng' => 17.4052979],
                ],
                'Borzavár' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2917736, 'lng' => 17.8327589],
                ],
                'Csabrendek' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.0121482, 'lng' => 17.2919127],
                ],
                'Csajág' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 47.0441186, 'lng' => 18.1855208],
                ],
                'Csehbánya' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.1789148, 'lng' => 17.6860046],
                ],
                'Csesznek' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3537678, 'lng' => 17.8838335],
                ],
                'Csetény' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.3169231, 'lng' => 17.9967592],
                ],
                'Csögle' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2178752, 'lng' => 17.2565165],
                ],
                'Csopak' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9755884, 'lng' => 17.9266992],
                ],
                'Csót' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3613819, 'lng' => 17.6060908],
                ],
                'Dabronc' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.0307114, 'lng' => 17.1678791],
                ],
                'Dabrony' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2428989, 'lng' => 17.3293417],
                ],
                'Dáka' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2846279, 'lng' => 17.4226315],
                ],
                'Devecser' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.1035622, 'lng' => 17.4376764],
                ],
                'Doba' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.1652453, 'lng' => 17.3803268],
                ],
                'Döbrönte' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2289278, 'lng' => 17.5466958],
                ],
                'Dörgicse' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9163856, 'lng' => 17.7231305],
                ],
                'Dudar' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.307038, 'lng' => 17.9424121],
                ],
                'Egeralja' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2371592, 'lng' => 17.2411524],
                ],
                'Egyházaskesző' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.4170906, 'lng' => 17.3273886],
                ],
                'Eplény' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.2110521, 'lng' => 17.9118724],
                ],
                'Farkasgyepű' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2035501, 'lng' => 17.6284268],
                ],
                'Felsőörs' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 47.014529, 'lng' => 17.9528849],
                ],
                'Ganna' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2324919, 'lng' => 17.528832],
                ],
                'Gecse' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.445017, 'lng' => 17.5284505],
                ],
                'Gic' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.4321719, 'lng' => 17.7537086],
                ],
                'Gógánfa' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.0216344, 'lng' => 17.1894021],
                ],
                'Gyepükaján' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.0431582, 'lng' => 17.3284859],
                ],
                'Gyulakeszi' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.8679522, 'lng' => 17.4801194],
                ],
                'Hajmáskér' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.1478249, 'lng' => 18.0244932],
                ],
                'Halimba' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.0328084, 'lng' => 17.5353134],
                ],
                'Hárskút' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.186511, 'lng' => 17.810875],
                ],
                'Hegyesd' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.9162143, 'lng' => 17.5222049],
                ],
                'Hegymagas' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.832869, 'lng' => 17.4319902],
                ],
                'Herend' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.1339597, 'lng' => 17.7517713],
                ],
                'Hetyefő' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.0459081, 'lng' => 17.1601035],
                ],
                'Hidegkút' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 47.0023704, 'lng' => 17.8276581],
                ],
                'Homokbödöge' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3023928, 'lng' => 17.5912743],
                ],
                'Hosztót' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.085633, 'lng' => 17.2419899],
                ],
                'Iszkáz' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.167956, 'lng' => 17.2964689],
                ],
                'Jásd' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.2870091, 'lng' => 18.0238903],
                ],
                'Kamond' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.1461747, 'lng' => 17.204369],
                ],
                'Kapolcs' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.9545983, 'lng' => 17.606756],
                ],
                'Káptalanfa' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.0641581, 'lng' => 17.3436367],
                ],
                'Káptalantóti' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.8496823, 'lng' => 17.5143016],
                ],
                'Karakószörcsök' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.1310434, 'lng' => 17.2859163],
                ],
                'Kékkút' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.849451, 'lng' => 17.5587833],
                ],
                'Kemeneshőgyész' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3550532, 'lng' => 17.2957279],
                ],
                'Kemenesszentpéter' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.4262865, 'lng' => 17.2302277],
                ],
                'Kerta' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.162409, 'lng' => 17.2732252],
                ],
                'Királyszentistván' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.1085816, 'lng' => 18.0463661],
                ],
                'Kisapáti' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.8442899, 'lng' => 17.4680237],
                ],
                'Kisberzseny' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.1058542, 'lng' => 17.2663052],
                ],
                'Kiscsősz' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.1955892, 'lng' => 17.2803113],
                ],
                'Kislőd' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.145129, 'lng' => 17.6221857],
                ],
                'Kispirit' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.1976686, 'lng' => 17.2396133],
                ],
                'Kisszőlős' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.196145, 'lng' => 17.331286],
                ],
                'Kolontár' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.085541, 'lng' => 17.4756235],
                ],
                'Kővágóörs' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.8489086, 'lng' => 17.6006181],
                ],
                'Köveskál' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.8836097, 'lng' => 17.6076535],
                ],
                'Külsővat' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2961471, 'lng' => 17.2267284],
                ],
                'Küngös' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 47.0659306, 'lng' => 18.1728413],
                ],
                'Kup' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2477003, 'lng' => 17.4634664],
                ],
                'Lesencefalu' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.844367, 'lng' => 17.3453474],
                ],
                'Lesenceistvánd' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.8701608, 'lng' => 17.3585011],
                ],
                'Lesencetomaj' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.855546, 'lng' => 17.3634736],
                ],
                'Litér' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 47.0990212, 'lng' => 18.0074074],
                ],
                'Lókút' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.2058801, 'lng' => 17.860406],
                ],
                'Lovas' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9925028, 'lng' => 17.9586856],
                ],
                'Lovászpatona' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.4355701, 'lng' => 17.626407],
                ],
                'Magyargencs' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3771345, 'lng' => 17.2892637],
                ],
                'Magyarpolány' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.1682011, 'lng' => 17.5463803],
                ],
                'Malomsok' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.4502762, 'lng' => 17.3930343],
                ],
                'Marcalgergelyi' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3090396, 'lng' => 17.2710338],
                ],
                'Marcaltő' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.4326649, 'lng' => 17.3671483],
                ],
                'Márkó' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.1241441, 'lng' => 17.817446],
                ],
                'Megyer' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.0614072, 'lng' => 17.1917368],
                ],
                'Mencshely' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9451849, 'lng' => 17.699145],
                ],
                'Mezőlak' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3297839, 'lng' => 17.3712496],
                ],
                'Mihályháza' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3076497, 'lng' => 17.33703],
                ],
                'Mindszentkálla' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.8742184, 'lng' => 17.5526369],
                ],
                'Monostorapáti' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.9254754, 'lng' => 17.5554767],
                ],
                'Monoszló' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9028555, 'lng' => 17.6411095],
                ],
                'Nagyacsád' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3654428, 'lng' => 17.3739048],
                ],
                'Nagyalásony' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2307621, 'lng' => 17.3557424],
                ],
                'Nagydém' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.4388649, 'lng' => 17.6758326],
                ],
                'Nagyesztergár' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2778801, 'lng' => 17.9043578],
                ],
                'Nagygyimót' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3395054, 'lng' => 17.5497835],
                ],
                'Nagypirit' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.1983576, 'lng' => 17.2261877],
                ],
                'Nagytevel' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2950927, 'lng' => 17.5683205],
                ],
                'Nagyvázsony' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.9827018, 'lng' => 17.6960646],
                ],
                'Nemesgörzsöny' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.39421, 'lng' => 17.3636956],
                ],
                'Nemesgulács' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.8338578, 'lng' => 17.4841619],
                ],
                'Nemeshany' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.0701835, 'lng' => 17.3650404],
                ],
                'Nemesszalók' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2755176, 'lng' => 17.2997564],
                ],
                'Nemesvámos' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.0558733, 'lng' => 17.8743185],
                ],
                'Nemesvita' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.8244758, 'lng' => 17.368753],
                ],
                'Németbánya' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.217077, 'lng' => 17.6446429],
                ],
                'Nóráp' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.273026, 'lng' => 17.4579987],
                ],
                'Noszlop' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.176802, 'lng' => 17.4577169],
                ],
                'Nyárád' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2848344, 'lng' => 17.363172],
                ],
                'Nyirád' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.0030636, 'lng' => 17.4497501],
                ],
                'Óbudavár' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9369878, 'lng' => 17.6903904],
                ],
                'Öcs' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.9999157, 'lng' => 17.6118445],
                ],
                'Olaszfalu' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.2416704, 'lng' => 17.9115042],
                ],
                'Oroszi' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.155417, 'lng' => 17.4162916],
                ],
                'Örvényes' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9141285, 'lng' => 17.8188445],
                ],
                'Ősi' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 47.1445512, 'lng' => 18.1872527],
                ],
                'Öskü' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.1609011, 'lng' => 18.0711066],
                ],
                'Paloznak' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9828339, 'lng' => 17.9415954],
                ],
                'Pápa' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3260464, 'lng' => 17.4697834],
                ],
                'Pápadereske' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2934775, 'lng' => 17.3997986],
                ],
                'Pápakovácsi' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2647348, 'lng' => 17.4859138],
                ],
                'Pápasalamon' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2281398, 'lng' => 17.4233757],
                ],
                'Pápateszér' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3830451, 'lng' => 17.7019758],
                ],
                'Papkeszi' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 47.0844113, 'lng' => 18.0816256],
                ],
                'Pécsely' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9556714, 'lng' => 17.7856485],
                ],
                'Pénzesgyőr' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2298173, 'lng' => 17.7904282],
                ],
                'Pétfürdő' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 47.1616056, 'lng' => 18.1337393],
                ],
                'Porva' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3069997, 'lng' => 17.8120154],
                ],
                'Pula' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.9980934, 'lng' => 17.6479249],
                ],
                'Pusztamiske' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.0620251, 'lng' => 17.4485286],
                ],
                'Raposka' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.8488751, 'lng' => 17.4258502],
                ],
                'Révfülöp' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.8292229, 'lng' => 17.6272798],
                ],
                'Rigács' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.0653643, 'lng' => 17.2141141],
                ],
                'Salföld' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.8340024, 'lng' => 17.5505245],
                ],
                'Sáska' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.9357629, 'lng' => 17.4789484],
                ],
                'Sóly' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.1295503, 'lng' => 18.0310553],
                ],
                'Somlójenő' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.1248257, 'lng' => 17.3547108],
                ],
                'Somlószőlős' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.1714107, 'lng' => 17.3573831],
                ],
                'Somlóvásárhely' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.1208168, 'lng' => 17.3763883],
                ],
                'Somlóvecse' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.1948868, 'lng' => 17.3509893],
                ],
                'Sümeg' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.9799564, 'lng' => 17.2810038],
                ],
                'Sümegprága' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.9410384, 'lng' => 17.2763838],
                ],
                'Szápár' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.3179688, 'lng' => 18.0377135],
                ],
                'Szentantalfa' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9126399, 'lng' => 17.6745195],
                ],
                'Szentbékkálla' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.8870806, 'lng' => 17.5639566],
                ],
                'Szentgál' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.1147506, 'lng' => 17.7331443],
                ],
                'Szentimrefalva' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.0741655, 'lng' => 17.2809094],
                ],
                'Szentjakabfa' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9333519, 'lng' => 17.6771067],
                ],
                'Szentkirályszabadja' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 47.056719, 'lng' => 17.9702394],
                ],
                'Szigliget' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.7933903, 'lng' => 17.438156],
                ],
                'Szőc' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.0213774, 'lng' => 17.5137469],
                ],
                'Tagyon' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9030388, 'lng' => 17.680224],
                ],
                'Takácsi' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3985477, 'lng' => 17.4708849],
                ],
                'Taliándörögd' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.9802373, 'lng' => 17.5685616],
                ],
                'Tapolca' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.8834475, 'lng' => 17.4371124],
                ],
                'Tés' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.2600026, 'lng' => 18.0333393],
                ],
                'Tihany' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9128663, 'lng' => 17.8880006],
                ],
                'Tótvázsony' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 47.0077859, 'lng' => 17.7872418],
                ],
                'Tüskevár' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.1188497, 'lng' => 17.3121441],
                ],
                'Ugod' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3210495, 'lng' => 17.6019333],
                ],
                'Ukk' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.0432214, 'lng' => 17.210956],
                ],
                'Úrkút' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.0849055, 'lng' => 17.6417026],
                ],
                'Uzsa' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.8958579, 'lng' => 17.3375414],
                ],
                'Vanyola' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3857478, 'lng' => 17.5925788],
                ],
                'Várkesző' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.4296188, 'lng' => 17.3151549],
                ],
                'Városlőd' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.1438504, 'lng' => 17.64992],
                ],
                'Várpalota' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 47.2005642, 'lng' => 18.1614063],
                ],
                'Vaszar' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.4025234, 'lng' => 17.5153031],
                ],
                'Vászoly' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9403118, 'lng' => 17.7575026],
                ],
                'Veszprém' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.1028087, 'lng' => 17.9093019],
                ],
                'Veszprémfajsz' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 47.0362311, 'lng' => 17.8958941],
                ],
                'Veszprémgalsa' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.092865, 'lng' => 17.2678872],
                ],
                'Vid' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2131783, 'lng' => 17.3360502],
                ],
                'Vigántpetend' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.9643546, 'lng' => 17.6286155],
                ],
                'Vilonya' => [
                    'constituencies' => ['Veszprém 1.'],
                    'coordinates' => ['lat' => 47.1091348, 'lng' => 18.0621011],
                ],
                'Vinár' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.3096788, 'lng' => 17.282759],
                ],
                'Vöröstó' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.9710918, 'lng' => 17.7218377],
                ],
                'Zalaerdőd' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.0549809, 'lng' => 17.1394845],
                ],
                'Zalagyömörő' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.0182311, 'lng' => 17.2277523],
                ],
                'Zalahaláp' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 46.9142835, 'lng' => 17.4588527],
                ],
                'Zalameggyes' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.0804037, 'lng' => 17.2188477],
                ],
                'Zalaszegvár' => [
                    'constituencies' => ['Veszprém 3.'],
                    'coordinates' => ['lat' => 47.0978422, 'lng' => 17.2243874],
                ],
                'Zánka' => [
                    'constituencies' => ['Veszprém 2.'],
                    'coordinates' => ['lat' => 46.8750577, 'lng' => 17.685025],
                ],
                'Zirc' => [
                    'constituencies' => ['Veszprém 4.'],
                    'coordinates' => ['lat' => 47.2620958, 'lng' => 17.8707843],
                ],
            ],
            'Zala' => [
                'Alibánfa' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8850651, 'lng' => 16.9227091],
                ],
                'Almásháza' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8428136, 'lng' => 17.0480668],
                ],
                'Alsónemesapáti' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8532968, 'lng' => 16.9358269],
                ],
                'Alsópáhok' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.7652926, 'lng' => 17.1718104],
                ],
                'Alsórajk' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.6537207, 'lng' => 16.9958526],
                ],
                'Alsószenterzsébet' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7441832, 'lng' => 16.4757695],
                ],
                'Babosdöbréte' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.8137531, 'lng' => 16.7765264],
                ],
                'Baglad' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.6809099, 'lng' => 16.4854227],
                ],
                'Bagod' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.8801596, 'lng' => 16.7448314],
                ],
                'Bak' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7319345, 'lng' => 16.8453869],
                ],
                'Baktüttös' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.700095, 'lng' => 16.8187481],
                ],
                'Balatongyörök' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.759294, 'lng' => 17.3519573],
                ],
                'Balatonmagyaród' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.596774, 'lng' => 17.1780204],
                ],
                'Bánokszentgyörgy' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5465023, 'lng' => 16.7826389],
                ],
                'Barlahida' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7154507, 'lng' => 16.7011138],
                ],
                'Batyk' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9902751, 'lng' => 17.0355472],
                ],
                'Bázakerettye' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5235543, 'lng' => 16.7290648],
                ],
                'Becsehely' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.4480572, 'lng' => 16.7900283],
                ],
                'Becsvölgye' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7570283, 'lng' => 16.6890138],
                ],
                'Belezna' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.3283696, 'lng' => 16.9382905],
                ],
                'Belsősárd' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.6424785, 'lng' => 16.4722491],
                ],
                'Bezeréd' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8705142, 'lng' => 17.012051],
                ],
                'Bocfölde' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7798036, 'lng' => 16.8460359],
                ],
                'Bocska' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5538226, 'lng' => 16.9114014],
                ],
                'Böde' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.837503, 'lng' => 16.7185404],
                ],
                'Bödeháza' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.6457727, 'lng' => 16.398583],
                ],
                'Bókaháza' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.7754903, 'lng' => 17.1038906],
                ],
                'Boncodfölde' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.8692041, 'lng' => 16.7381302],
                ],
                'Borsfa' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5072514, 'lng' => 16.7805706],
                ],
                'Börzönce' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5796968, 'lng' => 16.8889453],
                ],
                'Búcsúszentlászló' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.7879472, 'lng' => 16.9326019],
                ],
                'Bucsuta' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5643437, 'lng' => 16.8339609],
                ],
                'Csapi' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5307775, 'lng' => 17.0889337],
                ],
                'Csatár' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7774473, 'lng' => 16.8724885],
                ],
                'Cserszegtomaj' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8036121, 'lng' => 17.2327563],
                ],
                'Csertalakos' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.6424165, 'lng' => 16.6991611],
                ],
                'Csesztreg' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.715221, 'lng' => 16.5151895],
                ],
                'Csöde' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.835821, 'lng' => 16.5431634],
                ],
                'Csömödér' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.6095999, 'lng' => 16.6396482],
                ],
                'Csonkahegyhát' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.8002007, 'lng' => 16.7190724],
                ],
                'Csörnyeföld' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5004056, 'lng' => 16.6330724],
                ],
                'Dióskál' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.672845, 'lng' => 17.0523553],
                ],
                'Dobri' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5162242, 'lng' => 16.5820104],
                ],
                'Döbröce' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9360491, 'lng' => 17.1890673],
                ],
                'Dobronhegy' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.8152627, 'lng' => 16.7479038],
                ],
                'Dötk' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9436651, 'lng' => 17.0069445],
                ],
                'Egeraracsa' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.671135, 'lng' => 17.0797801],
                ],
                'Egervár' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9374942, 'lng' => 16.8525716],
                ],
                'Eszteregnye' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.4722547, 'lng' => 16.8824138],
                ],
                'Esztergályhorváti' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.7002528, 'lng' => 17.1069655],
                ],
                'Felsőpáhok' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.7855125, 'lng' => 17.158207],
                ],
                'Felsőrajk' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.6782939, 'lng' => 16.9857392],
                ],
                'Felsőszenterzsébet' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7536269, 'lng' => 16.454266],
                ],
                'Fityeház' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.3765425, 'lng' => 16.9039881],
                ],
                'Fűzvölgy' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5240843, 'lng' => 16.9407404],
                ],
                'Gáborjánháza' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.6315304, 'lng' => 16.420031],
                ],
                'Galambok' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5211851, 'lng' => 17.1246414],
                ],
                'Garabonc' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.5892904, 'lng' => 17.1215733],
                ],
                'Gellénháza' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7642584, 'lng' => 16.7828708],
                ],
                'Gelse' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.6006867, 'lng' => 16.9876731],
                ],
                'Gelsesziget' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5661932, 'lng' => 16.988739],
                ],
                'Gétye' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.7643035, 'lng' => 17.0698535],
                ],
                'Gombosszeg' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7559022, 'lng' => 16.7203796],
                ],
                'Gősfa' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9588777, 'lng' => 16.8590113],
                ],
                'Gosztola' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.5873387, 'lng' => 16.5275301],
                ],
                'Gutorfölde' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.6424899, 'lng' => 16.7312092],
                ],
                'Gyenesdiás' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.7736669, 'lng' => 17.2851781],
                ],
                'Gyűrűs' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8844774, 'lng' => 16.9909608],
                ],
                'Hagyárosbörönd' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.9081273, 'lng' => 16.7031566],
                ],
                'Hahót' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.6425065, 'lng' => 16.921536],
                ],
                'Hernyék' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.6486218, 'lng' => 16.6413302],
                ],
                'Hévíz' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.7902864, 'lng' => 17.1866936],
                ],
                'Homokkomárom' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.504277, 'lng' => 16.9148459],
                ],
                'Hosszúvölgy' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5141871, 'lng' => 16.9321886],
                ],
                'Hottó' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.846601, 'lng' => 16.7512196],
                ],
                'Iborfia' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7390543, 'lng' => 16.7484673],
                ],
                'Iklódbördőce' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.6049594, 'lng' => 16.6102262],
                ],
                'Kacorlak' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.574974, 'lng' => 16.953702],
                ],
                'Kallósd' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8713599, 'lng' => 17.0629023],
                ],
                'Kálócfa' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7562076, 'lng' => 16.5652705],
                ],
                'Kányavár' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5722009, 'lng' => 16.6805197],
                ],
                'Karmacs' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8359539, 'lng' => 17.1722974],
                ],
                'Kávás' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.8623738, 'lng' => 16.7100278],
                ],
                'Kehidakustány' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8383647, 'lng' => 17.0960779],
                ],
                'Kemendollár' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9044046, 'lng' => 16.9450204],
                ],
                'Keménfa' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.8402596, 'lng' => 16.6373382],
                ],
                'Kerecseny' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.6220381, 'lng' => 17.0452715],
                ],
                'Kerkabarabás' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.67972, 'lng' => 16.5557792],
                ],
                'Kerkafalva' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7707709, 'lng' => 16.4862602],
                ],
                'Kerkakutas' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7589775, 'lng' => 16.5041687],
                ],
                'Kerkaszentkirály' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.499678, 'lng' => 16.5839454],
                ],
                'Kerkateskánd' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5751748, 'lng' => 16.5702104],
                ],
                'Keszthely' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.7654716, 'lng' => 17.2479554],
                ],
                'Kilimán' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.6363528, 'lng' => 16.995822],
                ],
                'Kisbucsa' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8191843, 'lng' => 16.9404178],
                ],
                'Kiscsehi' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5207181, 'lng' => 16.6738733],
                ],
                'Kisgörbő' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9363795, 'lng' => 17.1555285],
                ],
                'Kiskutas' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.9125679, 'lng' => 16.7965674],
                ],
                'Kispáli' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.9111647, 'lng' => 16.8286778],
                ],
                'Kisrécse' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5005454, 'lng' => 17.060979],
                ],
                'Kissziget' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.6217161, 'lng' => 16.6647583],
                ],
                'Kistolmács' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.4875452, 'lng' => 16.7498836],
                ],
                'Kisvásárhely' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9944431, 'lng' => 17.1986619],
                ],
                'Kozmadombja' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7668629, 'lng' => 16.5592357],
                ],
                'Külsősárd' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.6279798, 'lng' => 16.4851688],
                ],
                'Kustánszeg' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7827502, 'lng' => 16.6779539],
                ],
                'Lakhegy' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9480077, 'lng' => 16.8342681],
                ],
                'Lasztonya' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5566777, 'lng' => 16.7162405],
                ],
                'Lendvadedes' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.5802741, 'lng' => 16.5093183],
                ],
                'Lendvajakabfa' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.6759482, 'lng' => 16.4431571],
                ],
                'Lenti' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.6225193, 'lng' => 16.5368334],
                ],
                'Letenye' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.4300795, 'lng' => 16.7318855],
                ],
                'Lickóvadamos' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7459672, 'lng' => 16.7700542],
                ],
                'Ligetfalva' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8228439, 'lng' => 17.0603013],
                ],
                'Lispeszentadorján' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5405312, 'lng' => 16.6936726],
                ],
                'Liszó' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.3691004, 'lng' => 17.0042635],
                ],
                'Lovászi' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5537589, 'lng' => 16.5570473],
                ],
                'Magyarföld' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7775884, 'lng' => 16.4177832],
                ],
                'Magyarszentmiklós' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5378994, 'lng' => 16.9367774],
                ],
                'Magyarszerdahely' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5550468, 'lng' => 16.9376435],
                ],
                'Maróc' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5478523, 'lng' => 16.6648936],
                ],
                'Márokföld' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7167478, 'lng' => 16.4402177],
                ],
                'Miháld' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.4490509, 'lng' => 17.1253117],
                ],
                'Mihályfa' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9790894, 'lng' => 17.1878294],
                ],
                'Mikekarácsonyfa' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.6578721, 'lng' => 16.6987538],
                ],
                'Milejszeg' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7911298, 'lng' => 16.7407933],
                ],
                'Misefa' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.805844, 'lng' => 16.9851398],
                ],
                'Molnári' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.3851524, 'lng' => 16.83244],
                ],
                'Murakeresztúr' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.3574162, 'lng' => 16.8799419],
                ],
                'Murarátka' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.458455, 'lng' => 16.6733082],
                ],
                'Muraszemenye' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.4791839, 'lng' => 16.6205241],
                ],
                'Nagybakónak' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5523896, 'lng' => 17.0417159],
                ],
                'Nagygörbő' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9301498, 'lng' => 17.177087],
                ],
                'Nagykanizsa' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.4590218, 'lng' => 16.9896796],
                ],
                'Nagykapornak' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8187913, 'lng' => 16.9935715],
                ],
                'Nagykutas' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.92748, 'lng' => 16.8049125],
                ],
                'Nagylengyel' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7798892, 'lng' => 16.7667276],
                ],
                'Nagypáli' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.9097117, 'lng' => 16.8440747],
                ],
                'Nagyrada' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.6202918, 'lng' => 17.1123641],
                ],
                'Nagyrécse' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.4878669, 'lng' => 17.0524072],
                ],
                'Nemesapáti' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8683567, 'lng' => 16.9506977],
                ],
                'Nemesbük' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8186282, 'lng' => 17.1531168],
                ],
                'Nemeshetés' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8006777, 'lng' => 16.9124072],
                ],
                'Nemesnép' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7004462, 'lng' => 16.4564627],
                ],
                'Nemespátró' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.324868, 'lng' => 17.0031728],
                ],
                'Nemesrádó' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.7787336, 'lng' => 16.9946119],
                ],
                'Nemessándorháza' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.7849863, 'lng' => 16.9482028],
                ],
                'Nemesszentandrás' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.7743546, 'lng' => 16.9419034],
                ],
                'Németfalu' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.8157725, 'lng' => 16.6853715],
                ],
                'Nova' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.6853485, 'lng' => 16.6788234],
                ],
                'Óhíd' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9620183, 'lng' => 17.1717364],
                ],
                'Oltárc' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5299729, 'lng' => 16.8325981],
                ],
                'Orbányosfa' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8540661, 'lng' => 16.9805699],
                ],
                'Ormándlak' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7587399, 'lng' => 16.7518347],
                ],
                'Orosztony' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.6259926, 'lng' => 17.0610355],
                ],
                'Ortaháza' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.6213246, 'lng' => 16.6798217],
                ],
                'Ozmánbük' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.9226425, 'lng' => 16.6730724],
                ],
                'Pacsa' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.717545, 'lng' => 17.0147772],
                ],
                'Padár' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8520937, 'lng' => 17.0158993],
                ],
                'Páka' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5933679, 'lng' => 16.6480271],
                ],
                'Pakod' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9585428, 'lng' => 17.0001826],
                ],
                'Pálfiszeg' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7812673, 'lng' => 16.7283145],
                ],
                'Pat' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.441208, 'lng' => 17.1837115],
                ],
                'Pethőhenye' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8771136, 'lng' => 16.9139014],
                ],
                'Petrikeresztúr' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7391053, 'lng' => 16.7230055],
                ],
                'Petrivente' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.4401934, 'lng' => 16.8406576],
                ],
                'Pókaszepetk' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9242449, 'lng' => 16.9679338],
                ],
                'Pölöske' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.7508424, 'lng' => 16.9226905],
                ],
                'Pölöskefő' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.6023293, 'lng' => 16.9474135],
                ],
                'Pördefölde' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5883529, 'lng' => 16.7109811],
                ],
                'Pórszombat' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7286283, 'lng' => 16.575571],
                ],
                'Pötréte' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.6798761, 'lng' => 16.9511959],
                ],
                'Pusztaapáti' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7623876, 'lng' => 16.609982],
                ],
                'Pusztaederics' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.6397452, 'lng' => 16.7988306],
                ],
                'Pusztamagyaród' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.6028067, 'lng' => 16.8265431],
                ],
                'Pusztaszentlászló' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.638191, 'lng' => 16.8379572],
                ],
                'Ramocsa' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7758952, 'lng' => 16.4471398],
                ],
                'Rédics' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.6140083, 'lng' => 16.4741205],
                ],
                'Resznek' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.6637509, 'lng' => 16.4745239],
                ],
                'Rezi' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8404703, 'lng' => 17.2197815],
                ],
                'Rigyác' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.4666954, 'lng' => 16.8620436],
                ],
                'Salomvár' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.8497402, 'lng' => 16.6591287],
                ],
                'Sand' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.422728, 'lng' => 17.1240354],
                ],
                'Sárhida' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7566862, 'lng' => 16.843108],
                ],
                'Sármellék' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.7159777, 'lng' => 17.1687979],
                ],
                'Semjénháza' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.3982599, 'lng' => 16.8472682],
                ],
                'Sénye' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8950106, 'lng' => 17.1350179],
                ],
                'Söjtör' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.671752, 'lng' => 16.8548664],
                ],
                'Sormás' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.4579721, 'lng' => 16.9183209],
                ],
                'Sümegcsehi' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9435094, 'lng' => 17.2157551],
                ],
                'Surd' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.3214586, 'lng' => 16.9700398],
                ],
                'Szalapa' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9926588, 'lng' => 17.1470408],
                ],
                'Szécsisziget' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.572805, 'lng' => 16.591706],
                ],
                'Szentgyörgyvár' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.7591042, 'lng' => 17.1308568],
                ],
                'Szentgyörgyvölgy' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7247643, 'lng' => 16.4036447],
                ],
                'Szentkozmadombja' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.6778861, 'lng' => 16.760746],
                ],
                'Szentliszló' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5792691, 'lng' => 16.8217825],
                ],
                'Szentmargitfalva' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.4962642, 'lng' => 16.6600543],
                ],
                'Szentpéterfölde' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.6154705, 'lng' => 16.7562927],
                ],
                'Szentpéterúr' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.759476, 'lng' => 17.0391952],
                ],
                'Szepetnek' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.4323443, 'lng' => 16.898497],
                ],
                'Szijártóháza' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.6339631, 'lng' => 16.4370525],
                ],
                'Szilvágy' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7323295, 'lng' => 16.6268861],
                ],
                'Tekenye' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9609929, 'lng' => 17.125278],
                ],
                'Teskánd' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.8510374, 'lng' => 16.7766477],
                ],
                'Tilaj' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8040804, 'lng' => 17.0485939],
                ],
                'Tófej' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.6704735, 'lng' => 16.8034031],
                ],
                'Tormafölde' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5381984, 'lng' => 16.5904921],
                ],
                'Tornyiszentmiklós' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5188668, 'lng' => 16.5536412],
                ],
                'Tótszentmárton' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.4215012, 'lng' => 16.8043814],
                ],
                'Tótszerdahely' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.3999282, 'lng' => 16.7986489],
                ],
                'Türje' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9853666, 'lng' => 17.1000419],
                ],
                'Újudvar' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5395758, 'lng' => 16.9903004],
                ],
                'Valkonya' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.4999087, 'lng' => 16.8094867],
                ],
                'Vállus' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8424517, 'lng' => 17.3026438],
                ],
                'Várfölde' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5568656, 'lng' => 16.7616303],
                ],
                'Várvölgy' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8685246, 'lng' => 17.2981598],
                ],
                'Vasboldogasszony' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9426879, 'lng' => 16.8712789],
                ],
                'Vaspör' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.9164552, 'lng' => 16.6452881],
                ],
                'Vindornyafok' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8562047, 'lng' => 17.1740875],
                ],
                'Vindornyalak' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8868007, 'lng' => 17.1933102],
                ],
                'Vindornyaszőlős' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.899026, 'lng' => 17.1525872],
                ],
                'Vöckönd' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8889347, 'lng' => 16.9540576],
                ],
                'Vonyarcvashegy' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.7613433, 'lng' => 17.3152337],
                ],
                'Zajk' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.484382, 'lng' => 16.7200595],
                ],
                'Zalaapáti' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.7385743, 'lng' => 17.1073607],
                ],
                'Zalabaksa' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7042458, 'lng' => 16.5413852],
                ],
                'Zalabér' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9741192, 'lng' => 17.0269412],
                ],
                'Zalaboldogfa' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.898796, 'lng' => 16.7688354],
                ],
                'Zalacsány' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8069158, 'lng' => 17.0996658],
                ],
                'Zalacséb' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.8606387, 'lng' => 16.6589982],
                ],
                'Zalaegerszeg' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.8416936, 'lng' => 16.8416322],
                ],
                'Zalaháshágy' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.8886163, 'lng' => 16.6288219],
                ],
                'Zalaigrice' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.746839, 'lng' => 17.0093287],
                ],
                'Zalaistvánd' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9182238, 'lng' => 16.9790209],
                ],
                'Zalakaros' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.5552711, 'lng' => 17.1222089],
                ],
                'Zalakomár' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5348351, 'lng' => 17.1782441],
                ],
                'Zalaköveskút' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8479466, 'lng' => 17.1426385],
                ],
                'Zalalövő' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.8486204, 'lng' => 16.5922358],
                ],
                'Zalamerenye' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.5742195, 'lng' => 17.094991],
                ],
                'Zalasárszeg' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.4923355, 'lng' => 17.0806479],
                ],
                'Zalaszabar' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.6406096, 'lng' => 17.1095737],
                ],
                'Zalaszántó' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8895909, 'lng' => 17.2268659],
                ],
                'Zalaszentbalázs' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5902558, 'lng' => 16.9186168],
                ],
                'Zalaszentgrót' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9427308, 'lng' => 17.0818249],
                ],
                'Zalaszentgyörgy' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.8717868, 'lng' => 16.7037755],
                ],
                'Zalaszentiván' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8919376, 'lng' => 16.8999401],
                ],
                'Zalaszentjakab' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.485286, 'lng' => 17.128849],
                ],
                'Zalaszentlászló' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.8737266, 'lng' => 17.1124672],
                ],
                'Zalaszentlőrinc' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.9157441, 'lng' => 16.8857401],
                ],
                'Zalaszentmárton' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.7041285, 'lng' => 17.062882],
                ],
                'Zalaszentmihály' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.7256048, 'lng' => 16.9476533],
                ],
                'Zalaszombatfa' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.6386288, 'lng' => 16.4436947],
                ],
                'Zalatárnok' => [
                    'constituencies' => ['Zala 1.'],
                    'coordinates' => ['lat' => 46.7014545, 'lng' => 16.7605059],
                ],
                'Zalaújlak' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.5594855, 'lng' => 17.078341],
                ],
                'Zalavár' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 46.667407, 'lng' => 17.1598007],
                ],
                'Zalavég' => [
                    'constituencies' => ['Zala 2.'],
                    'coordinates' => ['lat' => 47.0067015, 'lng' => 17.0246849],
                ],
                'Zebecke' => [
                    'constituencies' => ['Zala 3.'],
                    'coordinates' => ['lat' => 46.6434945, 'lng' => 16.6862071],
                ],
            ],
        ];
    }
}
