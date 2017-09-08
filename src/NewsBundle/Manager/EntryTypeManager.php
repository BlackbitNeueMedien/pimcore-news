<?php

namespace NewsBundle\Manager;

use NewsBundle\Configuration\Configuration;
use Pimcore\Model\Site;
use Pimcore\Model\Staticroute;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;

class EntryTypeManager
{
    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var array
     */
    protected $routeData = [];

    /**
     * EntryTypeManager constructor.
     *
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param null $object
     *
     * @return array|mixed|null
     */
    public function getTypes($object = NULL)
    {
        $entryTypes = $this->getTypesFromConfig();

        $validLayouts = NULL;

        if (!is_null($object)) {
            $validLayouts = DataObject\Service::getValidLayouts($object);
        }

        foreach ($entryTypes as $typeId => &$type) {

            if($type['custom_layout_id'] === 0) {
                $type['custom_layout_id'] = NULL;
            }

            $customLayoutId = $type['custom_layout_id'];

            //if string (name) is given, get layout via listing
            if (is_string($customLayoutId)) {
                $list = new ClassDefinition\CustomLayout\Listing();
                $list->setLimit(1);
                $list->setCondition('name = ?', $type['custom_layout_id']);
                $list = $list->load();
                if (isset($list[0]) && $list[0] instanceof DataObject\ClassDefinition\CustomLayout) {
                    $customLayoutId = (int)$list[0]->getId();
                } else {
                    $customLayoutId = NULL; //reset field -> custom layout is not available!
                }
            }

            //remove types if user is not allowed to use it!
            $allowMasterLayout = isset($validLayouts[0]);

            if ((!$allowMasterLayout || !is_null($customLayoutId)) && !is_null($validLayouts) && !isset($validLayouts[$customLayoutId])) {
                unset($entryTypes[$typeId]);
            } else {
                $type['custom_layout_id'] = $customLayoutId;
            }
        }

        return $entryTypes;
    }

    /**
     * Get Default Entry Type
     * @return mixed
     */
    public function getDefaultType()
    {
        $entryTypeConfig = $this->configuration->getConfig('entry_types');
        return $entryTypeConfig['default'];
    }

    /**
     * @return array|mixed|null
     */
    public function getTypesFromConfig()
    {
        $entryTypeConfig = $this->configuration->getConfig('entry_types');

        $types = $entryTypeConfig['items'];

        //cannot be empty - at least "news" is required.
        if (empty($types)) {
            $types = [
                'news' => [
                    'name'           => 'news.entry_type.news',
                    'route'          => '',
                    'customLayoutId' => 0
                ]
            ];
        }

        return $types;
    }

    /**
     * @param $entryType
     *
     * @return array|mixed
     * @throws \Exception
     */
    public function getRouteInfo($entryType)
    {
        //use cache.
        if (isset($this->routeData[$entryType])) {
            return $this->routeData[$entryType];
        }

        $routeData = ['name' => 'news_detail', 'urlParams' => []];
        $types = $this->getTypesFromConfig();

        if (isset($types[$entryType]) && !empty($types[$entryType]['route'])) {
            $routeData['name'] = $types[$entryType]['route'];
        }

        $siteId = NULL;
        if (Site::isSiteRequest()) {
            $siteId = Site::getCurrentSite()->getId();
        }

        $route = Staticroute::getByName($routeData['name'], $siteId);

        if(empty($route)) {
            throw new \Exception(sprintf('"%s" route is not available. please add it to your static routes', $routeData['name']));
        }
        $variables = explode(',', $route->getVariables());

        //remove default one
        $defaults = ['news'];
        $variables = array_diff($variables, $defaults);

        $routeData['urlParams'] = array_merge($routeData['urlParams'], $variables);
        $this->routeData[$entryType] = $routeData;

        return $routeData;
    }
}