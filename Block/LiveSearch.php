<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Model\Config;
use Magento\Framework\View\Element\Template;

class LiveSearch extends Template
{
    /**
     * Get live search template
     *
     * @return mixed
     */
    public function getLiveSearchTemplate()
    {
        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_TEMPLATE);
    }
}