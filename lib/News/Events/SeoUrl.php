<?php

namespace News\Events;

use Pimcore\Model\Object;

class SeoUrl
{
    public static function setObjectFrontendUrl(\Zend_EventManager_Event $e)
    {
        /** @var \Pimcore\Model\Object\AbstractObject $target */
        $target = $e->getTarget();
        $className = (new \ReflectionClass($target))->getShortName();

        if( !in_array($className, array('NewsEntry', 'NewsCategory') ) )
        {
            return;
        }

        \Pimcore\Model\Version::disable();

        self::parseUrl($target, $className);

        \Pimcore\Model\Version::enable();

        \Pimcore\Cache::clearTag('object_' . $target->getId());
    }

    /**
     * @param \Pimcore\Model\Object\NewsEntry|\Pimcore\Model\Object\NewsCategory $object
     * @param string (NewsEntry|NewsCategory) $className
     *
     * @return bool
     * @throws \Exception
     * @throws \Pimcore\Model\Element\ValidationException
     */
    private static function parseUrl( $object, $className = '' )
    {
        $languages = \Pimcore\Tool::getValidLanguages();

        $needUpdate = FALSE;
        $fromCopy = FALSE;

        $objectClass = 'Object\\' . $className;

        foreach( $languages as $language )
        {
            if( self::isFromCopy($object, $objectClass, $language) === TRUE )
            {
                $fromCopy = TRUE;
                break;
            }
        }

        if( $fromCopy )
        {
            foreach( $languages as $language )
            {
                $object->setDetailUrl('', $language);
            }

            $object->save();
            return FALSE;
        }

        foreach( $languages as $language )
        {
            $realUrl = '';

            //always reset stored url if element just has been copied.
            $storedUrl = $object->getDetailUrl($language);
            $title = $object->getName($language);

            //skip all empty!
            if( empty( $title ) && empty( $storedUrl ) )
            {
                continue;
            }

            if( !empty($title) )
            {
                $title = preg_replace('/[^a-zA-Z0-9-äöüÄÜÖß ]/', '', $title);
                $realUrl = \Pimcore\File::getValidFilename($title);
                $realUrl = trim(preg_replace('/-+/', '-', $realUrl), '-');
            }

            $storedUrlWithoutVersion = substr( $storedUrl, 0, strlen($realUrl) );

            $versionUrl = $realUrl;

            if( $storedUrlWithoutVersion !== $realUrl )
            {
                $needUpdate = TRUE;

                $sameUrlObject = $objectClass::getByLocalizedfields(
                    'detailUrl', $realUrl, $language,
                    ['limit' => 1, 'condition' => ' AND ooo_id != ' . (int) $object->getId() . ' AND name <> ""']
                );

                if( count( $sameUrlObject ) === 1 )
                {
                    $nextPossibleVersion = $sameUrlObject;

                    $version = 1;
                    $versionUrl = $realUrl . '-' . ( $version );

                    while( $nextPossibleVersion instanceof $objectClass )
                    {
                        $versionUrl = $realUrl . '-' . ( $version );

                        $nextPossibleVersion = $objectClass::getByLocalizedfields(
                            'detailUrl',  $versionUrl, $language,
                            ['limit' => 1, 'condition' => ' AND ooo_id != ' . (int) $object->getId() . ' AND name <> ""']
                        );

                        $version++;
                    }

                }

                $object->setDetailUrl($versionUrl, $language);
            }

        }

        if( $needUpdate )
        {
            $object->save();
        }

    }

    /**
     * @param $obj
     * @param $objectClass
     * @param $language
     *
     * @return bool
     */
    private static function isFromCopy( $obj, $objectClass, $language )
    {
        //always check if there is double data!!!
        $url = $obj->getDetailUrl( $language );

        if( empty( $url ) )
        {
            return FALSE;
        }

        $duplicateUrlObjects = $objectClass::getByLocalizedfields(
            'detailUrl',  $url, $language,
            ['limit' => 1, 'condition' => ' AND ooo_id != ' . (int) $obj->getId()]
        );

        return count( $duplicateUrlObjects ) === 1;
    }
}