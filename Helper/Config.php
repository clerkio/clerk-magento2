<?php

namespace Clerk\Clerk\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var Context
     */
    private $context;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Context $context
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Context              $context
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->context = $context;
    }

    /**
     * Get config flag in frontend context
     *
     * @param string $key
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
     * Get config templates array in frontend context
     *
     * @param string $key
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
     * Get config value in frontend context
     *
     * @param string $key
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
     * Get config value in admin context
     *
     * @param string $key
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
     * Get base url in frontend context
     *
     * @return string
     */
    public function getBaseUrl()
    {
        $store = $this->context->getStore();
        return $store ? $store->getBaseUrl() : "";
    }
}
