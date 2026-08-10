<?php

namespace Clerk\Clerk\Controller\Order;

use Clerk\Clerk\Model\Api;
use Clerk\Clerk\Controller\AbstractAction;
use Clerk\Clerk\Model\Config;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Module\ModuleList;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Webapi\Rest\Request as RequestApi;

class Index extends AbstractAction
{
    /**
     * @var ClerkLogger
     */
    protected $clerk_logger;

    /**
     * @var array
     */
    protected $fieldMap = [
        'increment_id' => 'id',
    ];

    /**
     * @var string
     */
    protected $eventPrefix = 'clerk_order';

    /**
     * @var ModuleList
     */
    protected $moduleList;

    /**
     * Order controller constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $orderCollectionFactory
     * @param ProductMetadataInterface $product_metadata
     * @param RequestApi $request_api
     * @param Api $api
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $orderCollectionFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        ModuleList $moduleList,
        ClerkLogger $clerk_logger,
        ProductMetadataInterface $product_metadata,
        RequestApi $request_api,
        Api $api
    ) {
        $this->collectionFactory = $orderCollectionFactory;
        $this->clerk_logger = $clerk_logger;
        $this->moduleList = $moduleList;
        $this->addFieldHandlers();

        parent::__construct(
            $context,
            $storeManager,
            $scopeConfig,
            $logger,
            $moduleList,
            $clerk_logger,
            $product_metadata,
            $request_api,
            $api
        );
    }

    /**
     * Add field handlers
     */
    protected function addFieldHandlers()
    {

        try {

            //Add time fieldhandler
            $this->addFieldHandler('time', function ($item) {
                return strtotime($item->getCreatedAt());
            });

            //Add email fieldhandler
            $this->addFieldHandler('email', function ($item) {
                if ($this->scopeConfig->isSetFlag(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS, $this->scope, $this->scopeid)) {
                    return $item->getCustomerEmail();
                }

                return null;
            });

            //Add customer fieldhandler
            $this->addFieldHandler('customer', function ($item) {
                return $item->getCustomerId();
            });

            //Add products fieldhandler
            $this->addFieldHandler('products', function ($item) {
                $products = [];
                foreach ($item->getAllVisibleItems() as $productItem) {
                    // Calculate net price per unit considering discounts and tax
                    $netPrice = $this->calculateNetProductPrice($productItem);
                    
                    $products[] = [
                        'id' => $productItem->getProductId(),
                        'quantity' => (int) $productItem->getQtyOrdered(),
                        'price' => (float) $netPrice,
                    ];
                }
                return $products;
            });

            //Add total net value fieldhandler
            $this->addFieldHandler('total', function ($item) {
                return (float) $this->calculateOrderNetTotal($item);
            });

            //Add discount amount fieldhandler
            $this->addFieldHandler('discount_amount', function ($item) {
                return (float) abs($item->getDiscountAmount());
            });

            //Add shipping amount fieldhandler
            $this->addFieldHandler('shipping_amount', function ($item) {
                return (float) $item->getShippingAmount();
            });

            //Add tax amount fieldhandler
            $this->addFieldHandler('tax_amount', function ($item) {
                return (float) $item->getTaxAmount();
            });

            //Add refunded amount fieldhandler
            $this->addFieldHandler('refunded_amount', function ($item) {
                return (float) $item->getTotalRefunded();
            });

        } catch (\Exception $e) {

            $this->clerk_logger->error('Order addFieldHandlers ERROR', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Execute request
     */
    public function execute()
    {
        try {

            $disabled = $this->scopeConfig->isSetFlag(
                Config::XML_PATH_PRODUCT_SYNCHRONIZATION_DISABLE_ORDER_SYNCHRONIZATION,
                $this->scope,
                $this->scopeid
            );

            if ($disabled) {
                $this->getResponse()
                    ->setHttpResponseCode(200)
                    ->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode([]));

                $this->clerk_logger->log('Order Sync Disabled', ['response' => '']);

                return;
            }

            parent::execute();

        } catch (\Exception $e) {

            $this->clerk_logger->error('Order execute ERROR', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Parse request arguments
     */
    protected function getArguments(RequestInterface $request)
    {
        try {
            parent::getArguments($request);

            //Use increment id instead of entity_id
            $this->fields = str_replace('entity_id', 'increment_id', $this->fields);

        } catch (\Exception $e) {

            $this->clerk_logger->error('Order getArguments ERROR', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Calculate net price per product unit considering discounts and tax
     *
     * @param \Magento\Sales\Model\Order\Item $productItem
     * @return float
     */
    protected function calculateNetProductPrice($productItem)
    {
        try {
            // Get the row total (price * quantity) after discounts
            $rowTotal = $productItem->getRowTotal();
            $discountAmount = abs($productItem->getDiscountAmount());
            $taxAmount = $productItem->getTaxAmount();
            $quantity = $productItem->getQtyOrdered();

            if ($quantity <= 0) {
                return 0.0;
            }

            // Calculate net row total: base price - discounts + tax (if tax-inclusive store)
            $netRowTotal = $rowTotal - $discountAmount;
            
            // For tax-inclusive stores, we need to include tax in the net price
            // For tax-exclusive stores, the net price should exclude tax
            $order = $productItem->getOrder();
            $store = $order->getStore();
            
            // Check if prices include tax in the store configuration
            $pricesIncludeTax = $this->scopeConfig->getValue(
                'tax/calculation/price_includes_tax',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store->getId()
            );

            if (!$pricesIncludeTax) {
                // For tax-exclusive stores, add tax to get the final customer-paid amount
                $netRowTotal += $taxAmount;
            }

            // Return net price per unit
            return $netRowTotal / $quantity;

        } catch (\Exception $e) {
            $this->clerk_logger->error('calculateNetProductPrice ERROR', [
                'error' => $e->getMessage(),
                'product_id' => $productItem->getProductId()
            ]);
            
            // Fallback to original price if calculation fails
            return (float) $productItem->getPrice();
        }
    }

    /**
     * Calculate the true net order total that reflects what customer actually paid
     *
     * @param \Magento\Sales\Model\Order $order
     * @return float
     */
    protected function calculateOrderNetTotal($order)
    {
        try {
            // Start with the grand total (what customer actually paid)
            $netTotal = $order->getGrandTotal();
            
            // Subtract any refunded amounts to get current net value
            $refundedAmount = $order->getTotalRefunded();
            if ($refundedAmount > 0) {
                $netTotal -= $refundedAmount;
            }

            return $netTotal;

        } catch (\Exception $e) {
            $this->clerk_logger->error('calculateOrderNetTotal ERROR', [
                'error' => $e->getMessage(),
                'order_id' => $order->getIncrementId()
            ]);
            
            // Fallback to grand total if calculation fails
            return (float) $order->getGrandTotal();
        }
    }
}
