<?php

namespace Clerk\Clerk\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;
    private Context $context;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Context              $context
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->context = $context;
    }

    /**
     * @param $key
     * @return bool
     */
    public function getFlag($key)
    {
        return $this->scopeConfig->isSetFlag(
            $key,
            $this->context->getScope(),
            $this->context->getScopeId()
        );
    }

    /**
     * @param $key
     * @return array|string[]
     */
    public function getTemplates($key)
    {
        $templates = $this->scopeConfig->getValue($key, $this->context->getScope(), $this->context->getScopeId());
        if (empty($templates)) {
            return [""];
        }
        return array_map('trim', explode(',', $templates));
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getValue($key)
    {
        return $this->scopeConfig->getValue(
            $key,
            $this->context->getScope(),
            $this->context->getScopeId()
        );
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getValueAdmin($key)
    {
        return $this->scopeConfig->getValue(
            $key,
            $this->context->getScopeAdmin(),
            $this->context->getScopeIdAdmin()
        );
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        $store = $this->context->getStore();
        return $store ? $store->getBaseUrl() : "";
    }
}
