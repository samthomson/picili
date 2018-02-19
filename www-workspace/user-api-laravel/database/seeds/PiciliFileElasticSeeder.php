<?php

use Illuminate\Database\Seeder;

use \Carbon\Carbon;

use Share\PiciliFile;

use App\Library\Helper;
use SharedLibrary\TagHelper;
use SharedLibrary\ElasticHelper;

class PiciliFileElasticSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $sIndexToUse = 'phpunit_files';
        $sIndexToUse = null;

        // seed files
        $oOtherUser = new PiciliFile;
        $oOtherUser->id = 1001;
        $oOtherUser->user_id = 1;
        $oOtherUser->signature = 'other-user';
        $oOtherUser->bHasThumbs = true;
        $oOtherUser->datetime = Carbon::parse('2016:07:08 13:29:45');
        $oOtherUser->baseName = 'DSC00145.JPG';
        $oOtherUser->extension = 'JPG';
        $oOtherUser->sParentPath = '';
        $oOtherUser->bInFolder = true;
        $oOtherUser->save();
        
        TagHelper::setTagsToFile($oOtherUser, [
            [
                "type" => "imagga",
                "confidence" => 82,
                "value" => "windmill"
            ]
        ]);
        
        ElasticHelper::bSaveFileToElastic($oOtherUser, $sIndexToUse);

        $oFileOne = new PiciliFile;
        $oFileOne->id = 1002;
        $oFileOne->user_id = 0;
        $oFileOne->signature = 'file-one';
        $oFileOne->bHasThumbs = true;
        $oFileOne->bInFolder = true;
        $oFileOne->datetime = Carbon::parse('2015:06:05 09:46:26');
        $oFileOne->sParentPath = 'subfolder';
        $oFileOne->save();

        TagHelper::setTagsToFile($oFileOne, [
            [
                "type" => "folder",
                "confidence" => 80,
                "value" => "subfolder"
            ]
        ]);

        $aaColourTags = TagHelper::getColourTagsFromColours([
            "pallette" => [
                [
                    "r" => 71,
                    "g" => 71,
                    "b" => 61
                ],
                [
                    "r" => 30,
                    "g" => 121,
                    "b" => 165
                ],
                [
                    "r" => 134,
                    "g" => 127,
                    "b" => 101
                ],
                [
                    "r" => 17,
                    "g" => 61,
                    "b" => 88
                ],
                [
                    "r" => 64,
                    "g" => 128,
                    "b" => 32
                ]
            ],
            "best" => [
                "r" => 66,
                "g" => 65,
                "b" => 83,
                "a" => 0
            ]
        ], $oFileOne->id);

        $oFileOne->addTags($aaColourTags);

        TagHelper::setTagsToFile($oFileOne, [
            [
                "type" => "imagga",
                "confidence" => 82,
                "value" => "tree"
            ],
            [
                "type" => "imagga",
                "confidence" => 59,
                "value" => "city"
            ],
            [
                "type" => "imagga",
                "confidence" => 40,
                "value" => "aboveconfidencethreshold"
            ],
            [
                "type" => "imagga",
                "confidence" => 25,
                "value" => "belowconfidencethreshold"
            ],
            [
                "type" => "imagga",
                "confidence" => 64,
                "value" => "monkey"
            ],
            [
                "type" => "imagga",
                "confidence" => 64,
                "value" => "giraffe"
            ]
        ]);

        TagHelper::setTagsToFile($oFileOne, [
            ["type" => "exif", "subtype" => "cameramake", "value" => "SONY", "confidence" => 80],
            ["type" => "exif", "subtype" => "cameramodel", "value" => "SLT-A55V", "confidence" => 80],
            ["type" => "exif", "subtype" => "datetime", "value" => "2016:06:05 09:46:26", "confidence" => 80],
            ["type" => "exif", "subtype" => "orientation", "value" => "landscape", "confidence" => 80],
            ["type" => "exif", "subtype" => "latitude", "value" => 46.2032830555555590, "confidence" => 80],
            ["type" => "exif", "subtype" => "longitude", "value" => 105.9481513888888900, "confidence" => 80],
            ["type" => "exif", "subtype" => "altitude", "value" => 1516.2000000000000000, "confidence" => 80]
        ]);

        /*
        $oFileOne->awsfaces = [
            [
                "BoundingBox" => [
                    "Width" => 0.0488888882100582,
                    "Height" => 0.0737018436193466,
                    "Left" => 0.1333333402872086,
                    "Top" => 0.4757118821144104
                ],
                "AgeRange" => [
                    "Low" => 26,
                    "High" => 43
                ],
                "Smile" => [
                    "Value" => false,
                    "Confidence" => 72.5521087646484380
                ],
                "Eyeglasses" => [
                    "Value" => false,
                    "Confidence" => 99.9987869262695310
                ],
                "Sunglasses" => [
                    "Value" => false,
                    "Confidence" => 99.8739929199218750
                ],
                "Gender" => [
                    "Value" => "Male",
                    "Confidence" => 99.9292221069335940
                ],
                "Beard" => [
                    "Value" => false,
                    "Confidence" => 99.6781311035156250
                ],
                "Mustache" => [
                    "Value" => false,
                    "Confidence" => 99.9178085327148440
                ],
                "EyesOpen" => [
                    "Value" => true,
                    "Confidence" => 99.7795639038085940
                ],
                "MouthOpen" => [
                    "Value" => false,
                    "Confidence" => 99.9755249023437500
                ],
                "Emotions" => [
                    [
                        "Type" => "HAPPY",
                        "Confidence" => 72.4056396484375000
                    ],
                    [
                        "Type" => "CALM",
                        "Confidence" => 24.0551776885986330
                    ],
                    [
                        "Type" => "SAD",
                        "Confidence" => 4.1926026344299316
                    ]
                ],
                "Landmarks" => [
                    [
                        "Type" => "eyeLeft",
                        "X" => 0.1520117372274399,
                        "Y" => 0.5049114227294922
                    ],
                    [
                        "Type" => "eyeRight",
                        "X" => 0.1675464063882828,
                        "Y" => 0.5080120563507080
                    ],
                    [
                        "Type" => "nose",
                        "X" => 0.1607612818479538,
                        "Y" => 0.5180960297584534
                    ],
                    [
                        "Type" => "mouthLeft",
                        "X" => 0.1523981839418411,
                        "Y" => 0.5301584005355835
                    ],
                    [
                        "Type" => "mouthRight",
                        "X" => 0.1637816727161408,
                        "Y" => 0.5325462818145752
                    ],
                    [
                        "Type" => "leftPupil",
                        "X" => 0.1523052752017975,
                        "Y" => 0.5045676827430725
                    ],
                    [
                        "Type" => "rightPupil",
                        "X" => 0.1676508188247681,
                        "Y" => 0.5078217387199402
                    ],
                    [
                        "Type" => "leftEyeBrowLeft",
                        "X" => 0.1466073095798492,
                        "Y" => 0.4961613714694977
                    ],
                    [
                        "Type" => "leftEyeBrowRight",
                        "X" => 0.1510266810655594,
                        "Y" => 0.4935131967067719
                    ],
                    [
                        "Type" => "leftEyeBrowUp",
                        "X" => 0.1560783237218857,
                        "Y" => 0.4951068162918091
                    ],
                    [
                        "Type" => "rightEyeBrowLeft",
                        "X" => 0.1653798073530197,
                        "Y" => 0.4973110258579254
                    ],
                    [
                        "Type" => "rightEyeBrowRight",
                        "X" => 0.1698715239763260,
                        "Y" => 0.4975319206714630
                    ],
                    [
                        "Type" => "rightEyeBrowUp",
                        "X" => 0.1735546141862869,
                        "Y" => 0.5004729628562927
                    ],
                    [
                        "Type" => "leftEyeLeft",
                        "X" => 0.1491305083036423,
                        "Y" => 0.5049479603767395
                    ],
                    [
                        "Type" => "leftEyeRight",
                        "X" => 0.1548370867967606,
                        "Y" => 0.5057407021522522
                    ],
                    [
                        "Type" => "leftEyeUp",
                        "X" => 0.1520939767360687,
                        "Y" => 0.5032374262809753
                    ],
                    [
                        "Type" => "leftEyeDown",
                        "X" => 0.1519574373960495,
                        "Y" => 0.5061523914337158
                    ],
                    [
                        "Type" => "rightEyeLeft",
                        "X" => 0.1646712571382523,
                        "Y" => 0.5078021287918091
                    ],
                    [
                        "Type" => "rightEyeRight",
                        "X" => 0.1703661084175110,
                        "Y" => 0.5087335705757141
                    ],
                    [
                        "Type" => "rightEyeUp",
                        "X" => 0.1676040291786194,
                        "Y" => 0.5063865780830383
                    ],
                    [
                        "Type" => "rightEyeDown",
                        "X" => 0.1675164997577667,
                        "Y" => 0.5093817114830017
                    ],
                    [
                        "Type" => "noseLeft",
                        "X" => 0.1570051908493042,
                        "Y" => 0.5221642255783081
                    ],
                    [
                        "Type" => "noseRight",
                        "X" => 0.1622159779071808,
                        "Y" => 0.5232959389686585
                    ],
                    [
                        "Type" => "mouthUp",
                        "X" => 0.1582422852516174,
                        "Y" => 0.5294979214668274
                    ],
                    [
                        "Type" => "mouthDown",
                        "X" => 0.1576846688985825,
                        "Y" => 0.5349718928337097
                    ]
                ],
                "Pose" => [
                    "Roll" => 6.2727432250976563,
                    "Yaw" => 13.4098529815673830,
                    "Pitch" => 5.0251626968383789
                ],
                "Quality" => [
                    "Brightness" => 44.5774536132812500,
                    "Sharpness" => 97.6150741577148440
                ],
                "Confidence" => 99.9997711181640630
            ],
            [
                "BoundingBox" => [
                    "Width" => 0.0388888902962208,
                    "Height" => 0.0586264654994011,
                    "Left" => 0.2355555593967438,
                    "Top" => 0.5242881178855896
                ],
                "AgeRange" => [
                    "Low" => 26,
                    "High" => 43
                ],
                "Smile" => [
                    "Value" => false,
                    "Confidence" => 99.9873580932617190
                ],
                "Eyeglasses" => [
                    "Value" => false,
                    "Confidence" => 99.9966354370117190
                ],
                "Sunglasses" => [
                    "Value" => false,
                    "Confidence" => 99.8604354858398440
                ],
                "Gender" => [
                    "Value" => "Female",
                    "Confidence" => 100.0000000000000000
                ],
                "Beard" => [
                    "Value" => false,
                    "Confidence" => 99.9639358520507810
                ],
                "Mustache" => [
                    "Value" => false,
                    "Confidence" => 99.9510269165039060
                ],
                "EyesOpen" => [
                    "Value" => true,
                    "Confidence" => 99.9690246582031250
                ],
                "MouthOpen" => [
                    "Value" => true,
                    "Confidence" => 53.1738700866699220
                ],
                "Emotions" => [
                    [
                        "Type" => "SURPRISED",
                        "Confidence" => 58.1441650390625000
                    ],
                    [
                        "Type" => "SAD",
                        "Confidence" => 38.0179405212402340
                    ],
                    [
                        "Type" => "HAPPY",
                        "Confidence" => 7.5276336669921875
                    ]
                ],
                "Landmarks" => [
                    [
                        "Type" => "eyeLeft",
                        "X" => 0.2480842322111130,
                        "Y" => 0.5472990870475769
                    ],
                    [
                        "Type" => "eyeRight",
                        "X" => 0.2604268193244934,
                        "Y" => 0.5482956171035767
                    ],
                    [
                        "Type" => "nose",
                        "X" => 0.2500864267349243,
                        "Y" => 0.5615454912185669
                    ],
                    [
                        "Type" => "mouthLeft",
                        "X" => 0.2499623596668243,
                        "Y" => 0.5701325535774231
                    ],
                    [
                        "Type" => "mouthRight",
                        "X" => 0.2575996220111847,
                        "Y" => 0.5715546011924744
                    ],
                    [
                        "Type" => "leftPupil",
                        "X" => 0.2488092780113220,
                        "Y" => 0.5473732948303223
                    ],
                    [
                        "Type" => "rightPupil",
                        "X" => 0.2603603005409241,
                        "Y" => 0.5485174059867859
                    ],
                    [
                        "Type" => "leftEyeBrowLeft",
                        "X" => 0.2444751858711243,
                        "Y" => 0.5407928824424744
                    ],
                    [
                        "Type" => "leftEyeBrowRight",
                        "X" => 0.2473894208669663,
                        "Y" => 0.5401480197906494
                    ],
                    [
                        "Type" => "leftEyeBrowUp",
                        "X" => 0.2500906586647034,
                        "Y" => 0.5414589643478394
                    ],
                    [
                        "Type" => "rightEyeBrowLeft",
                        "X" => 0.2567360401153565,
                        "Y" => 0.5413647294044495
                    ],
                    [
                        "Type" => "rightEyeBrowRight",
                        "X" => 0.2612582147121429,
                        "Y" => 0.5404905676841736
                    ],
                    [
                        "Type" => "rightEyeBrowUp",
                        "X" => 0.2655807435512543,
                        "Y" => 0.5422928333282471
                    ],
                    [
                        "Type" => "leftEyeLeft",
                        "X" => 0.2461005449295044,
                        "Y" => 0.5469018220901489
                    ],
                    [
                        "Type" => "leftEyeRight",
                        "X" => 0.2502118349075317,
                        "Y" => 0.5483269691467285
                    ],
                    [
                        "Type" => "leftEyeUp",
                        "X" => 0.2481866180896759,
                        "Y" => 0.5456506013870239
                    ],
                    [
                        "Type" => "leftEyeDown",
                        "X" => 0.2479098886251450,
                        "Y" => 0.5486322641372681
                    ],
                    [
                        "Type" => "rightEyeLeft",
                        "X" => 0.2580683529376984,
                        "Y" => 0.5489451289176941
                    ],
                    [
                        "Type" => "rightEyeRight",
                        "X" => 0.2629183828830719,
                        "Y" => 0.5485021471977234
                    ],
                    [
                        "Type" => "rightEyeUp",
                        "X" => 0.2603506743907929,
                        "Y" => 0.5465466976165772
                    ],
                    [
                        "Type" => "rightEyeDown",
                        "X" => 0.2604364454746246,
                        "Y" => 0.5496164560317993
                    ],
                    [
                        "Type" => "noseLeft",
                        "X" => 0.2500762939453125,
                        "Y" => 0.5639411807060242
                    ],
                    [
                        "Type" => "noseRight",
                        "X" => 0.2548401355743408,
                        "Y" => 0.5645008087158203
                    ],
                    [
                        "Type" => "mouthUp",
                        "X" => 0.2526037395000458,
                        "Y" => 0.5682482719421387
                    ],
                    [
                        "Type" => "mouthDown",
                        "X" => 0.2521798908710480,
                        "Y" => 0.5764312744140625
                    ]
                ],
                "Pose" => [
                    "Roll" => 4.1717610359191895,
                    "Yaw" => -21.3820037841796880,
                    "Pitch" => -9.0623636245727539
                ],
                "Quality" => [
                    "Brightness" => 39.3540306091308590,
                    "Sharpness" => 65.9488830566406250
                ],
                "Confidence" => 99.9984359741210940
            ]
        ];
        */

        $oFileTwo = new PiciliFile;
        $oFileTwo->id = 1003;
        $oFileTwo->user_id = 0;
        $oFileTwo->bHasThumbs = true;
        $oFileTwo->signature = 'file-two';
        $oFileTwo->datetime = Carbon::parse('2016:08:08 13:29:45');
        $oFileTwo->save();

        TagHelper::setTagsToFile($oFileTwo, [
            [
                "type" => "imagga",
                "confidence" => 75.8830352483615660,
                "value" => "city"
            ],
            [
                "type" => "imagga",
                "confidence" => 65.6699567020113760,
                "value" => "manhattan"
            ],
            [
                "type" => "imagga",
                "confidence" => 58,
                "value" => "monkey"
            ],
            [
                "type" => "imagga",
                "confidence" => 72,
                "value" => "giraffe"
            ],
            [
                "type" => "imagga",
                "confidence" => 72,
                "value" => "uniquetofiletwo"
            ]
        ]);
        $oFileTwo->bHasThumbs = true;

        /*
        $oFileTwo->awsfaces = [
            [
                "BoundingBox" => [
                    "Width" => 0.2073578536510468,
                    "Height" => 0.1377777755260468,
                    "Left" => 0.3578595221042633,
                    "Top" => 0.1688888818025589
                ],
                "AgeRange" => [
                    "Low" => 48,
                    "High" => 68
                ],
                "Smile" => [
                    "Value" => true,
                    "Confidence" => 96.3778762817382810
                ],
                "Eyeglasses" => [
                    "Value" => true,
                    "Confidence" => 83.2112274169921880
                ],
                "Sunglasses" => [
                    "Value" => false,
                    "Confidence" => 99.6732940673828120
                ],
                "Gender" => [
                    "Value" => "Female",
                    "Confidence" => 100.0000000000000000
                ],
                "Beard" => [
                    "Value" => false,
                    "Confidence" => 99.9890518188476560
                ],
                "Mustache" => [
                    "Value" => false,
                    "Confidence" => 99.9689178466796880
                ],
                "EyesOpen" => [
                    "Value" => false,
                    "Confidence" => 99.9139022827148440
                ],
                "MouthOpen" => [
                    "Value" => false,
                    "Confidence" => 99.9264602661132810
                ],
                "Emotions" => [
                    [
                        "Type" => "HAPPY",
                        "Confidence" => 94.8748626708984370
                    ],
                    [
                        "Type" => "SAD",
                        "Confidence" => 1.1998772621154785
                    ],
                    [
                        "Type" => "ANGRY",
                        "Confidence" => 0.8911073207855225
                    ]
                ],
                "Confidence" => 99.9996795654296880
            ]
        ];
        */

        $oFileTwo->bInFolder = true;
        $oFileTwo->sParentPath = 'subfolder';
        TagHelper::setTagsToFile($oFileTwo, [
            [
                "type" => "folder",
                "confidence" => 80,
                "value" => "subfolder"
            ]
        ]);

        $aaColourTags = TagHelper::getColourTagsFromColours([
            "pallette" => [
                [
                    "r" => 71,
                    "g" => 71,
                    "b" => 61
                ],
                [
                    "r" => 30,
                    "g" => 121,
                    "b" => 165
                ],
                [
                    "r" => 134,
                    "g" => 127,
                    "b" => 101
                ],
                [
                    "r" => 17,
                    "g" => 61,
                    "b" => 88
                ],
                [
                    "r" => 64,
                    "g" => 128,
                    "b" => 32
                ]
            ],
            "best" => [
                "r" => 66,
                "g" => 65,
                "b" => 83,
                "a" => 0
            ]
        ], $oFileTwo->id);

        $oFileTwo->addTags($aaColourTags);
        $oFileTwo->save();
        
        
        ElasticHelper::bSaveFileToElastic($oFileOne, $sIndexToUse);
        ElasticHelper::bSaveFileToElastic($oFileTwo, $sIndexToUse);

        $oGeoDataOne = new PiciliFile;
        $oGeoDataOne->id = 1004;
        $oGeoDataOne->user_id = 0;
        $oGeoDataOne->datetime = Carbon::parse('2016:08:06 14:29:45');
        $oGeoDataOne->bHasThumbs = true;
        $oGeoDataOne->signature = 'geo-data-one';
        $oGeoDataOne->address = "Queen's Park";

        $oGeoDataOne->bHasGPS = true;
        $oGeoDataOne->latitude = 55.8310644444444510;
        $oGeoDataOne->longitude = -4.2700674999999997;

        $oGeoDataOne->bInFolder = true;
        $oGeoDataOne->sParentPath = 'scotland/glasgow/bowman flat';
        $oGeoDataOne->save();

        TagHelper::setTagsToFile($oGeoDataOne, [
            [
                "type" => "folder",
                "confidence" => 80,
                "value" => "subfolder"
            ],
            [
                "type" => "folder",
                "confidence" => 80,
                "value" => "glasgow"
            ],
            [
                "type" => "folder",
                "confidence" => 80,
                "value" => "bowman flat"
            ]
        ]);

        TagHelper::setTagsToFile($oGeoDataOne, [
            [
                "type" => "geo.park",
                "value" => "Queen's Park",
                "confidence" => 65
            ],
            [
                "type" => "geo.postcode",
                "value" => "G42 8QA",
                "confidence" => 65
            ],
            [
                "type" => "geo.road",
                "value" => "Balvicar Drive",
                "confidence" => 65
            ],
            [
                "type" => "geo.state",
                "value" => "Scotland",
                "confidence" => 65
            ],
            [
                "type" => "geo.suburb",
                "value" => "Strathbungo",
                "confidence" => 65
            ]
        ]);
        
        ElasticHelper::bSaveFileToElastic($oGeoDataOne, $sIndexToUse);


        $oFileThree = new PiciliFile;
        $oFileThree->id = 1005;
        $oFileThree->user_id = 0;
        $oFileThree->bHasThumbs = true;
        $oFileThree->signature = 'file-three';
        $oFileThree->datetime = Carbon::parse('2016:08:18 12:29:45');
        $oFileThree->save();
        
        TagHelper::setTagsToFile($oFileThree, [
            [
                "type" => "imagga",
                "confidence" => 75.8830352483615660,
                "value" => "city"
            ],
            [
                "type" => "imagga",
                "confidence" => 65.6699567020113760,
                "value" => "manhattan"
            ],
            [
                "type" => "imagga",
                "confidence" => 72,
                "value" => "monkey"
            ],
            [
                "type" => "imagga",
                "confidence" => 64,
                "value" => "giraffe"
            ]
        ]);
        /*
        $oFileThree->awsfaces = [
            [
                "BoundingBox" => [
                    "Width" => 0.0411111116409302,
                    "Height" => 0.0616666674613953,
                    "Left" => 0.1733333319425583,
                    "Top" => 0.5166666507720947
                ],
                "AgeRange" => [
                    "Low" => 45,
                    "High" => 63
                ],
                "Smile" => [
                    "Value" => false,
                    "Confidence" => 68.5351333618164060
                ],
                "Eyeglasses" => [
                    "Value" => true,
                    "Confidence" => 73.5727615356445310
                ],
                "Sunglasses" => [
                    "Value" => false,
                    "Confidence" => 72.2353363037109380
                ],
                "Gender" => [
                    "Value" => "Male",
                    "Confidence" => 99.9239425659179690
                ],
                "Beard" => [
                    "Value" => false,
                    "Confidence" => 89.9762268066406250
                ],
                "Mustache" => [
                    "Value" => false,
                    "Confidence" => 94.3044357299804690
                ],
                "EyesOpen" => [
                    "Value" => false,
                    "Confidence" => 99.2815170288085940
                ],
                "MouthOpen" => [
                    "Value" => true,
                    "Confidence" => 69.9757461547851560
                ],
                "Emotions" => [
                    [
                        "Type" => "ANGRY",
                        "Confidence" => 99.3600692749023440
                    ],
                    [
                        "Type" => "HAPPY",
                        "Confidence" => 4.0850806236267090
                    ],
                    [
                        "Type" => "CONFUSED",
                        "Confidence" => 1.0643422603607178
                    ]
                ],
                "Landmarks" => [
                    [
                        "Type" => "eyeLeft",
                        "X" => 0.1855601221323013,
                        "Y" => 0.5467326641082764
                    ],
                    [
                        "Type" => "eyeRight",
                        "X" => 0.1980998218059540,
                        "Y" => 0.5440105199813843
                    ],
                    [
                        "Type" => "nose",
                        "X" => 0.1909493505954742,
                        "Y" => 0.5598288178443909
                    ],
                    [
                        "Type" => "mouthLeft",
                        "X" => 0.1905251592397690,
                        "Y" => 0.5674225091934204
                    ],
                    [
                        "Type" => "mouthRight",
                        "X" => 0.2012989521026611,
                        "Y" => 0.5649721026420593
                    ],
                    [
                        "Type" => "leftPupil",
                        "X" => 0.1861790120601654,
                        "Y" => 0.5465196967124939
                    ],
                    [
                        "Type" => "rightPupil",
                        "X" => 0.1964040398597717,
                        "Y" => 0.5436239838600159
                    ],
                    [
                        "Type" => "leftEyeBrowLeft",
                        "X" => 0.1816996484994888,
                        "Y" => 0.5398356914520264
                    ],
                    [
                        "Type" => "leftEyeBrowRight",
                        "X" => 0.1846113950014114,
                        "Y" => 0.5409110784530640
                    ],
                    [
                        "Type" => "leftEyeBrowUp",
                        "X" => 0.1870783865451813,
                        "Y" => 0.5414422750473023
                    ],
                    [
                        "Type" => "rightEyeBrowLeft",
                        "X" => 0.1933505535125732,
                        "Y" => 0.5397489666938782
                    ],
                    [
                        "Type" => "rightEyeBrowRight",
                        "X" => 0.1970263570547104,
                        "Y" => 0.5376077294349670
                    ],
                    [
                        "Type" => "rightEyeBrowUp",
                        "X" => 0.2010083645582199,
                        "Y" => 0.5363326072692871
                    ],
                    [
                        "Type" => "leftEyeLeft",
                        "X" => 0.1834620535373688,
                        "Y" => 0.5470669865608215
                    ],
                    [
                        "Type" => "leftEyeRight",
                        "X" => 0.1878218054771423,
                        "Y" => 0.5462348461151123
                    ],
                    [
                        "Type" => "leftEyeUp",
                        "X" => 0.1855363249778748,
                        "Y" => 0.5464500188827515
                    ],
                    [
                        "Type" => "leftEyeDown",
                        "X" => 0.1855021119117737,
                        "Y" => 0.5470970273017883
                    ],
                    [
                        "Type" => "rightEyeLeft",
                        "X" => 0.1955972015857697,
                        "Y" => 0.5442794561386108
                    ],
                    [
                        "Type" => "rightEyeRight",
                        "X" => 0.2006379663944244,
                        "Y" => 0.5434495806694031
                    ],
                    [
                        "Type" => "rightEyeUp",
                        "X" => 0.1980975717306137,
                        "Y" => 0.5438421368598938
                    ],
                    [
                        "Type" => "rightEyeDown",
                        "X" => 0.1980843096971512,
                        "Y" => 0.5443248748779297
                    ],
                    [
                        "Type" => "noseLeft",
                        "X" => 0.1899961382150650,
                        "Y" => 0.5620025396347046
                    ],
                    [
                        "Type" => "noseRight",
                        "X" => 0.1962503045797348,
                        "Y" => 0.5600395202636719
                    ],
                    [
                        "Type" => "mouthUp",
                        "X" => 0.1949055194854736,
                        "Y" => 0.5655182600021362
                    ],
                    [
                        "Type" => "mouthDown",
                        "X" => 0.1955030262470245,
                        "Y" => 0.5699133276939392
                    ]
                ],
                "Pose" => [
                    "Roll" => -7.0807538032531738,
                    "Yaw" => -25.4501914978027340,
                    "Pitch" => -15.0213003158569340
                ],
                "Quality" => [
                    "Brightness" => 30.5693359375000000,
                    "Sharpness" => 76.3053054809570310
                ],
                "Confidence" => 99.9725494384765620
            ]
        ];
        */

        $oFileThree->bHasThumbs = true;
        ElasticHelper::bSaveFileToElastic($oFileThree, $sIndexToUse);


        $oFileFour = new PiciliFile;
        $oFileFour->id = 1006;
        $oFileFour->user_id = 0;
        $oFileFour->bHasThumbs = true;
        $oFileFour->signature = 'file-four';
        $oFileFour->datetime = Carbon::parse('2016:08:18 15:29:45');
        $oFileFour->save();
        
        TagHelper::setTagsToFile($oFileFour, [
            [
                "type" => "imagga",
                "confidence" => 75.8830352483615660,
                "value" => "city"
            ],
            [
                "type" => "imagga",
                "confidence" => 65.6699567020113760,
                "value" => "manhattan"
            ],
            [
                "type" => "imagga",
                "confidence" => 44,
                "value" => "monkey"
            ],
            [
                "type" => "imagga",
                "confidence" => 72,
                "value" => "giraffe"
            ]
        ]);
        
        $oFileFour->bHasThumbs = true;
        ElasticHelper::bSaveFileToElastic($oFileFour, $sIndexToUse);


        $oFileFive = new PiciliFile;
        $oFileFive->id = 1007;
        $oFileFive->user_id = 0;
        $oFileFive->bHasThumbs = true;
        $oFileFive->signature = 'file-five';
        $oFileFive->datetime = Carbon::parse('2016:08:18 11:29:45');
        $oFileFive->save();

        TagHelper::setTagsToFile($oFileFive, [
            [
                "type" => "imagga",
                "confidence" => 75.8830352483615660,
                "value" => "city"
            ],
            [
                "type" => "imagga",
                "confidence" => 65.6699567020113760,
                "value" => "manhattan"
            ],
            [
                "type" => "imagga",
                "confidence" => 60,
                "value" => "monkey"
            ],
            [
                "type" => "imagga",
                "confidence" => 60,
                "value" => "giraffe"
            ]
        ]);

        $oFileFive->bHasThumbs = true;
        ElasticHelper::bSaveFileToElastic($oFileFive, $sIndexToUse);


        $oFolderAggOne = new PiciliFile;
        $oFolderAggOne->id = 1008;
        $oFolderAggOne->user_id = 0;
        $oFolderAggOne->bHasThumbs = true;
        $oFolderAggOne->signature = 'folder-agg-one';
        $oFolderAggOne->datetime = Carbon::parse('2016:08:08 13:29:45');
        $oFolderAggOne->save();
        
        TagHelper::setTagsToFile($oFolderAggOne, [
            [
                "type" => "imagga",
                "confidence" => 85.8830352483615660,
                "value" => "island"
            ],
            [
                "type" => "imagga",
                "confidence" => 65.6699567020113760,
                "value" => "banana"
            ]
        ]);
        
        $oFolderAggOne->bHasThumbs = true;

        $oFolderAggOne->bInFolder = true;
        $oFolderAggOne->sParentPath = 'green island';
        $oFolderAggOne->save();
        
        TagHelper::setTagsToFile($oFolderAggOne, [
            [
                "type" => "folder",
                "confidence" => 80,
                "value" => "green island"
            ]
        ]);

        ElasticHelper::bSaveFileToElastic($oFolderAggOne, $sIndexToUse);

        $oFolderAggTwo = new PiciliFile;
        $oFolderAggTwo->id = 1009;
        $oFolderAggTwo->bHasThumbs = true;
        $oFolderAggTwo->user_id = 0;
        $oFolderAggTwo->signature = 'folder-agg-two';
        $oFolderAggTwo->datetime = Carbon::parse('2016:08:09 13:29:44');
        $oFolderAggTwo->save();

        TagHelper::setTagsToFile($oFolderAggTwo, [
            [
                "type" => "imagga",
                "confidence" => 78.8830352483615660,
                "value" => "island"
            ],
            [
                "type" => "imagga",
                "confidence" => 62.6699567020113760,
                "value" => "banana"
            ]
        ]);
        
        $oFolderAggTwo->bHasThumbs = true;

        $oFolderAggTwo->bInFolder = true;
        $oFolderAggTwo->sParentPath = 'green island';
        $oFolderAggTwo->save();
        TagHelper::setTagsToFile($oFolderAggTwo, [
            [
                "type" => "folder",
                "confidence" => 80,
                "value" => "green island"
            ]
        ]);

        ElasticHelper::bSaveFileToElastic($oFolderAggTwo, $sIndexToUse);

        $oFolderAggThree = new PiciliFile;
        $oFolderAggThree->id = 1010;
        $oFolderAggThree->sParentPath = '';
        $oFolderAggThree->user_id = 0;
        $oFolderAggThree->bHasThumbs = true;
        $oFolderAggThree->signature = 'folder-agg-three';
        $oFolderAggThree->datetime = Carbon::parse('2014:08:09 13:29:45');
        $oFolderAggThree->save();
        
        TagHelper::setTagsToFile($oFolderAggThree, [
            [
                "type" => "imagga",
                "confidence" => 78.8830352483615660,
                "value" => "island"
            ],
            [
                "type" => "imagga",
                "confidence" => 72.6699567020113760,
                "value" => "banana"
            ]
        ]);
        $oFolderAggThree->bHasThumbs = true;

        $oFolderAggThree->bInFolder = true;
        $oFolderAggThree->sParentPath = 'green island';
        TagHelper::setTagsToFile($oFolderAggThree, [
            [
                "type" => "folder",
                "confidence" => 80,
                "value" => "green island"
            ]
        ]);

        ElasticHelper::bSaveFileToElastic($oFolderAggThree, $sIndexToUse);


        $oHistoricFile = new PiciliFile;
        $oHistoricFile->id = '5-years-old';
        $oHistoricFile->user_id = 0;
        $oHistoricFile->signature = '5-years-old';
        $oHistoricFile->bHasThumbs = true;
        $oHistoricFile->datetime = Carbon::now()->addYears(-5);
        $oHistoricFile->save();


        $oHistoricFile2 = new PiciliFile;
        $oHistoricFile2->user_id = 0;
        $oHistoricFile2->id = '3-years-old';
        $oHistoricFile2->signature = '3-years-old';
        $oHistoricFile2->bHasThumbs = true;
        $oHistoricFile2->datetime = Carbon::now()->addYears(-3);
        $oHistoricFile2->save();


        $oHistoricFile3 = new PiciliFile;
        $oHistoricFile3->user_id = 0;
        $oHistoricFile3->id = '3-years-old-2';
        $oHistoricFile3->signature = '3-years-old-2';
        $oHistoricFile3->bHasThumbs = true;
        $oHistoricFile3->datetime = Carbon::now()->addYears(-3);
        $oHistoricFile3->save();


        $oHistoricFile4 = new PiciliFile;
        $oHistoricFile4->user_id = 0;
        $oHistoricFile4->id = '3-years-old-3';
        $oHistoricFile4->signature = '3-years-old-3';
        $oHistoricFile4->bHasThumbs = true;
        $oHistoricFile4->datetime = Carbon::now()->addYears(-3);
        $oHistoricFile4->save();


        $oHistoricFile5 = new PiciliFile;
        $oHistoricFile5->user_id = 0;
        $oHistoricFile5->id = '1-year-old';
        $oHistoricFile5->signature = '1-year-old';
        $oHistoricFile5->bHasThumbs = true;
        $oHistoricFile5->datetime = Carbon::now()->addYears(-1);
        $oHistoricFile5->save();


        $oHistoricFile6 = new PiciliFile;
        $oHistoricFile6->user_id = 0;
        $oHistoricFile6->id = '1-year-old-2';
        $oHistoricFile6->signature = '1-year-old-2';
        $oHistoricFile6->bHasThumbs = true;
        $oHistoricFile6->datetime = Carbon::now()->addYears(-1);
        $oHistoricFile6->save();
    }
}
