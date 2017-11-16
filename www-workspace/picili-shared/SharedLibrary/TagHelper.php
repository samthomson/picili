<?php

namespace SharedLibrary;


use Share\PiciliFile;
use Share\Tag;

class TagHelper {

    //
    // tagging
    //
    public static function setTagsToFile($oPiciliFile, $aaTags)
    {
        if(count($aaTags) > 0)
        {
            $aTagsToSet = [];

            foreach($aaTags as $aTag) {
                array_push(
                    $aTagsToSet, 
                    new Tag([
                        'type' => $aTag['type'],
                        'subtype' => isset($aTag['subtype']) ? $aTag['subtype'] : null,
                        'value' => $aTag['value'],
                        'confidence' => $aTag['confidence'],
                        'file_id' => $oPiciliFile->id
                    ])
                );
            }

            $oPiciliFile->addTags($aTagsToSet);
        }
    }
    public static function removeTagsOfType($oPiciliFile, $sType)
    {
		Tag::where('file_id', $oPiciliFile->id)->where('type', $sType)->delete();
    }
    public static function getColourTagsFromColours($aaColours, $iPiciliFileId)
    {
        $aaColourTags = [];

        if (isset($aaColours['best']))
        {
            $r = $aaColours['best']['r'];
            $g = $aaColours['best']['g'];
            $b = $aaColours['best']['b'];

            array_push(
                $aaColourTags,
                new Tag([
                    'type' => 'colour',
                    'subtype' => 'best',
                    'value' => "$r.$g.$b",
                    'confidence' => 80,
                    'file_id' => $iPiciliFileId
                ])
            );
        }

        if (isset($aaColours['pallette']))
        {
            foreach($aaColours['pallette'] as $aColour)
            {
                $r = $aColour['r'];
                $g = $aColour['g'];
                $b = $aColour['b'];

                array_push(
                    $aaColourTags,
                    new Tag([
                        'type' => 'colour',
                        'subtype' => 'pallette',
                        'value' => "$r.$g.$b",
                        'confidence' => 60,
                        'file_id' => $iPiciliFileId
                    ])
                );
            }            
        }

        return $aaColourTags;
    }
}
