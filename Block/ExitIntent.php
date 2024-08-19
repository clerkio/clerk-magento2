<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Helper\Config as ConfigHelper;
use Clerk\Clerk\Model\Config;
use Magento\Framework\View\Element\Template;

class ExitIntent extends Template
{
    public function __construct(
        ConfigHelper     $configHelper,
        Template\Context $context,
        array            $data = []
    )
    {
        $this->configHelper = $configHelper;
        parent::__construct($context, $data);
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
