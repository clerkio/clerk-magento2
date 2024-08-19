<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Helper\Config as ConfigHelper;
use Clerk\Clerk\Model\Config;
use Magento\Framework\View\Element\Template;

class PowerstepScripts extends Template
{
    public function __construct(
        ConfigHelper     $configHelper,
        Template\Context $context,
        array            $data = []
    )
    {
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
        return $this->configHelper->getValue(Config::XML_PATH_POWERSTEP_TYPE) == Config\Source\PowerstepType::TYPE_POPUP;
    }
}
