<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Helper\Config as ConfigHelper;
use Clerk\Clerk\Model\Config;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class ExitIntent extends Template
{
    /**
     * @param ConfigHelper $configHelper
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        ConfigHelper     $configHelper,
        Template\Context $context,
        array            $data = []
    ) {
        parent::__construct($context, $data);
        $this->configHelper = $configHelper;
    }

    /**
     * Get exit intent template
     *
     * @return string[]
     */
    public function getExitIntentTemplate()
    {
        return $this->configHelper->getTemplates(Config::XML_PATH_EXIT_INTENT_TEMPLATE);
    }
}
