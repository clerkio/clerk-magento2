<?php

namespace Clerk\Clerk\Helper;

use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Exception;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Context
{
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;
    /**
     * @var RequestInterface
     */
    private RequestInterface $request;
    /**
     * @var ClerkLogger
     */
    private ClerkLogger $logger;

    public function __construct(
        RequestInterface      $request,
        StoreManagerInterface $storeManager,
        ClerkLogger           $logger
    ) {
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        $params = $this->request->getParams();
        if ($this->storeManager->isSingleStoreMode()) {
            return "default";
        } elseif (array_key_exists('scope', $params)) {
            return (string)$params['scope'];
        } else {
            return ScopeInterface::SCOPE_STORE;
        }
    }

    /**
     * @return int
     */
    public function getScopeId()
    {
        $params = $this->request->getParams();
        if ($this->storeManager->isSingleStoreMode()) {
            return 0;
        } elseif (array_key_exists('scope_id', $params)) {
            return (int)$params['scope_id'];
        } else {
            return $this->getStoreId();
        }
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        $store = $this->getStore();
        if (empty($store)) {
            return 0;
        }
        return $store->getId();
    }

    /**
     * @return StoreInterface|void
     */
    public function getStore()
    {
        try {
            $params = $this->request->getParams();
            if (array_key_exists('scope_id', $params)) {
                if (array_key_exists('scope', $params) && $params['scope'] === 'store') {
                    $store_id = $params['scope_id'];
                    return $this->storeManager->getStore($store_id);
                } elseif (array_key_exists('store', $params)) {
                    $store_id = $params['store'];
                    return $this->storeManager->getStore($store_id);
                } else {
                    return $this->storeManager->getStore();
                }
            } elseif (array_key_exists('store', $params)) {
                $store_id = $params['store'];
                return $this->storeManager->getStore($store_id);
            } else {
                return $this->storeManager->getStore();
            }
        } catch (Exception $error) {
            $this->logger->error("getStore Error", ['error' => $error->getMessage()]);
        }
    }

    /**
     * @return string
     */
    public function getScopeAdmin()
    {
        $_params = $this->request->getParams();
        $scope = 'default';
        if (array_key_exists('website', $_params)) {
            $scope = 'website';
        }
        if (array_key_exists('store', $_params)) {
            $scope = 'store';
        }
        return $scope;
    }

    /**
     * @return int
     */
    public function getScopeIdAdmin()
    {
        $_params = $this->request->getParams();
        $scope_id = 0;
        if (array_key_exists('website', $_params)) {
            $scope_id = (int) $_params['website'];
        }
        if (array_key_exists('store', $_params)) {
            $scope_id = (int) $_params['store'];
        }
        return $scope_id;
    }
}