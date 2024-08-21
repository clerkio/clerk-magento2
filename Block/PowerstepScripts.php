<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Helper\Config as ConfigHelper;
use Clerk\Clerk\Model\Config;
use Clerk\Clerk\Model\Config\Source\PowerstepType;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class PowerstepScripts extends Template
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
     * Determine if we should show scripts
     *
     * @return bool
     */
    public function shouldShow()
    {
        return $this->configHelper->getValue(Config::XML_PATH_POWERSTEP_TYPE) == PowerstepType::TYPE_POPUP;
    }
}
