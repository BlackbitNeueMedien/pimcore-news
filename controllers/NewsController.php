<?php

use Pimcore\Model\Object;

use News\Model\Configuration;
use News\Controller\Action;

class News_NewsController extends Action {

    public function init() {

        parent::init();
        $this->enableLayout();

    }

    public function detailAction()
    {
        $newsEntry = new \News\Model\Entry();

        $newsFragment = $this->getParam('news');
        $language = $this->getParam('lang');

        //because this is a virtual document made with static route, we append some document properties with settings, if set.
        $pageProperties = Configuration::get('news_detail_settings');

        if( !empty($pageProperties) )
        {
            foreach( $pageProperties as $pagePropertyName => $pagePropertyData )
            {
                $this->document->setProperty($pagePropertyName, $pagePropertyData['type'], $pagePropertyData['data'], false, false);
            }
        }

        /** @var Object\NewsEntry $solution */
        $news = Object\NewsEntry::getByLocalizedfields( 'detailUrl', $newsFragment, $language, ['limit' => 1] );

        //maybe we have an old url like "news-title-xy-12" => only if activated in settings!
        if ( !($news instanceof Object\NewsEntry) && Configuration::get('use_id_in_url_fallback') === TRUE )
        {
            preg_match('/[0-9]+$/', $newsFragment, $versionMatch);

            if( !empty( $versionMatch) && !empty( $versionMatch[0] ) )
            {
                $newsId = (int) $versionMatch[0];
                $news = $newsEntry->getById( $newsId );
            }
        }

        if ( !($news instanceof Object\NewsEntry) )
        {
            throw new Exception('News (' . $newsFragment . ') couldn\'t be found');
        }
        else
        {
            $this->view->assign('document', $this->getDocument());
            $this->view->assign('news', $news);
        }

        $this->_setSEOMeta( $news );

    }

    /**
     * @param string $paramName
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getRequestParam($paramName, $default = null)
    {
        $value = $this->getParam($paramName);
        if ((null === $value || '' === $value) && (null !== $default))
        {
            $value = $default;
        }

        return $value;
    }

    /**
     * @param \Pimcore\Model\Object\NewsEntry $news
     */
    private function _setSEOMeta( $news )
    {
        $href = $this->view->newsHelper()->getDetailUrl( $news );

        $mT = $news->getMetaTitle();
        $mD = $news->getMetaDescription();

        $title = !empty( $mT ) ? $mT : $news->getName();
        $description = !empty( $mD ) ? $mD : ( $news->getLead() ? $news->getLead() : $news->getDescription() );

        $description = trim( substr($description, 0, 160) );

        $ogTitle = $title;
        $ogDescription = $description;
        $ogUrl = $this->view->serverUrl() . $href;
        $ogType = 'article';

        $ogImage = NULL;

        if ($news->getImage() instanceof \Pimcore\Model\Asset\Image)
        {
            $ogImage = $this->view->serverUrl() . $news->getImage()->getThumbnail('contentImage');
        }

        $params = [
            'title'             => $title,
            'description'       => $description,
            'og:title'          => $ogTitle,
            'og:description'    => $ogDescription,
            'og:url'            => $ogDescription,
            'og:image'          => $ogImage
        ];

        $cmdEv = \Pimcore::getEventManager()->trigger('news.head.meta', NULL, $params);

        if ($cmdEv->stopped())
        {
            $customMeta = $cmdEv->last();

            if( is_array( $customMeta ) )
            {
                $title = $customMeta['title'];
                $description = $customMeta['description'];
                $ogTitle = $customMeta['og:title'];
                $ogDescription = $customMeta['og:description'];
                $ogUrl = $customMeta['og:url'];
                $ogImage = $customMeta['og:image'];
            }
        }

        $this->view->headTitle( $title );
        $this->view->headMeta()->setName('description', $description);

        if( !empty($ogTitle))
        {
            $this->view->headMeta()->appendName('og:title', $ogTitle);
        }

        if( !empty($ogDescription))
        {
            $this->view->headMeta()->appendName('og:description', $ogDescription);
        }

        if( !empty($ogUrl))
        {
            $this->view->headMeta()->appendName('og:url', $ogUrl);
        }

        if( !empty($ogType))
        {
            $this->view->headMeta()->appendName('og:type', $ogType);
        }

        if ( !is_null( $ogImage ) )
        {
            $this->view->headMeta()->appendName('og:image', $ogImage);
        }
    }

}
