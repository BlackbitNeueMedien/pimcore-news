<?php

namespace Pimcore\Model\Document\Tag\Area;

use News\Model\Configuration;
use News\Controller\WidgetHandler;
use News\Tool\NewsTypes;

use Pimcore\Model\Document;
use Pimcore\Model\Object;

class News extends Document\Tag\Area\AbstractArea
{
    /**
     *
     */
    public function action()
    {
        $view = $this->getView();
        $querySettings = [];

        //set category
        $category = NULL;
        $includeSubCategories = FALSE;
        if ($view->href('category')->getElement()) {
            $category = $view->href('category')->getElement();
            $includeSubCategories = (bool)$view->checkbox('includeSubCategories')->getData() === TRUE;
        }

        $querySettings['category'] = $category;
        $querySettings['includeSubCategories'] = $includeSubCategories;

        //set entry type
        $entryTypes = $view->multiselect('entryType')->getData() ?: ['all'];
        $querySettings['entryTypes'] = $entryTypes;

        //set limit
        $limit = (int)$view->numeric('limit')->getData();

        //set pagination
        $showPagination = FALSE;
        $itemPerPage = 10;
        if ((bool)$view->checkbox('showPagination')->getData() === TRUE) {

            $showPagination = TRUE;
            $itemsPerPage = (int)$view->numeric('itemsPerPage')->getData();

            if (empty($limit) || $itemsPerPage > $limit) {
                $itemPerPage = $itemsPerPage;
            } else if (!empty($limit)) {
                $itemPerPage = $limit;
            }
        } else if (!empty($limit)) {
            $itemPerPage = $limit;
        }

        $querySettings['itemsPerPage'] = $itemPerPage;

        //set paged
        $querySettings['page'] = (int)$this->getParam('page');

        //only latest
        if ((bool)$view->checkbox('latest')->getData() === TRUE) {
            $querySettings['where']['latest = ?'] = 1;
        }

        //set sort
        $querySettings['sort']['field'] = $view->select('sortby')->getData() ?: 'date';
        $querySettings['sort']['dir'] = $view->select('orderby')->getData() ?: 'desc';

        //set timeRange
        $querySettings['timeRange'] = $view->select('timeRange')->getData();

        //get request data
        $querySettings['request'] = [
            'POST' => $view->getRequest()->getPost(),
            'GET'  => $view->getRequest()->getQuery()
        ];

        $response = \Pimcore::getEventManager()->trigger('news.action.querySettings', $this, ['querySettings' => $querySettings]);

        if ($response->stopped()) {
            $querySettings = $response->last();
        }

        //load Query
        $newsObjects = Object\NewsEntry::getEntriesPaging($querySettings);

        //get Layout Name
        $layoutName = $view->select('layout')->getData();

        //load settings for edit.php in edit-mode
        $adminSettings = [];
        if ($view->editmode === TRUE) {

            $adminSettings['listSettings'] = Configuration::get('news_list_settings');
            foreach ($adminSettings['listSettings']['layouts']['items'] as $index => $item) {
                $adminSettings['listSettings']['layouts']['items'][$index] = [$item[0], $view->translateAdmin($item[1])];
            }

            $newsTypes = NewsTypes::getTypesFromConfig();
            $adminSettings['entryTypes']['store'] = [['all', $view->translateAdmin('all entry types')]];
            $adminSettings['entryTypes']['default'] = ['all'];
            foreach ($newsTypes as $typeKey => $typeData) {
                $adminSettings['entryTypes']['store'][] = [$typeKey, $view->translateAdmin($typeData['name'])];
            }
        }

        $mainClasses = [];

        $mainClasses[] = 'area';
        $mainClasses[] = 'news-' . $layoutName;

        foreach($entryTypes as $entryType) {
            if ( $entryType !== 'all' ) {
                $mainClasses[] = 'entry-type-' . str_replace(['_', ' '], ['-'], strtolower($entryType));
            }
        }

        //prepare WidgetSettings
        $widgetSettings = $querySettings;
        $widgetSettings['showPagination'] = $showPagination;
        $widgetSettings['entryTypes'] = $entryTypes;
        $widgetSettings['paginator'] = $newsObjects;
        $widgetSettings['layoutName'] = $layoutName;

        //initialize widget handler
        $widgetHandler = new WidgetHandler($widgetSettings);
        $widgetHandler->passHelperPaths($view->getHelperPaths());
        $widgetHandler->setEditMode($view->editmode);

        $view->assign([

            'mainClasses'    => implode(' ', $mainClasses),
            'category'       => $category,
            'showPagination' => $showPagination,
            'paginator'      => $newsObjects,
            'entryTypes'     => $entryTypes,
            'layoutName'     => $layoutName,
            'widgetHandler'  => $widgetHandler,

            //system/editmode related
            'editSettings'   => $adminSettings,
            'querySettings'  => $querySettings

        ]);
    }

    public function getBrickHtmlTagOpen($brick)
    {
        return '';
    }

    public function getBrickHtmlTagClose($brick)
    {
        return '';
    }
}