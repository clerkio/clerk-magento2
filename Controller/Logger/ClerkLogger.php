<?php

namespace Clerk\Clerk\Controller\Logger;

use Clerk\Clerk\Model\Config;
use DateTime;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

class ClerkLogger
{
    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var ConfigInterface
     */
    protected $configWriter;
    /**
     * @var DirectoryList
     */
    protected $directory;
    /**
     * @var ModuleList
     */
    protected $moduleList;
    /**
     * @var string
     */
    private $platform;
    /**
     * @var mixed
     */
    private $publicKey;
    /**
     * @var DateTime
     */
    private $currentDate;
    /**
     * @var mixed
     */
    private $loggingEnabled;
    /**
     * @var int
     */
    private $currentTime;
    /**
     * @var mixed
     */
    private $loggingLevel;
    /**
     * @var mixed
     */
    private $loggingMethod;
    /**
     * @var string
     */
    private $endpoint;

    /**
     * ClerkLogger constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param DirectoryList $dir
     * @param TimezoneInterface $date
     * @param ConfigInterface $configWriter
     * @param ModuleList $moduleList
     * @param ProductMetadataInterface $product_metadata
     */
    function __construct(
        ScopeConfigInterface     $scopeConfig,
        DirectoryList            $dir,
        TimezoneInterface        $date,
        ConfigInterface          $configWriter,
        ModuleList               $moduleList,
        ProductMetadataInterface $product_metadata
    )
    {

        $this->configWriter = $configWriter;
        $this->directory = $dir;
        $this->scopeConfig = $scopeConfig;
        $this->productMetadata = $product_metadata;
        $this->moduleList = $moduleList;
        $this->platform = 'Magento 2';
        $this->endpoint = 'https://api.clerk.io/v2/log/debug';
        $this->currentDate = $date->date();
        $this->currentTime = $date->scopeTimeStamp();
        $this->publicKey = $this->scopeConfig->getValue(Config::XML_PATH_PUBLIC_KEY, ScopeInterface::SCOPE_STORE);
        $this->loggingLevel = $this->scopeConfig->getValue(Config::XML_PATH_LOG_LEVEL, ScopeInterface::SCOPE_STORE);
        $this->loggingMethod = $this->scopeConfig->getValue(Config::XML_PATH_LOG_TO, ScopeInterface::SCOPE_STORE);
        $this->loggingEnabled = $this->scopeConfig->getValue(Config::XML_PATH_LOG_ENABLED, ScopeInterface::SCOPE_STORE);
        $this->setDefaultConfig();
    }

    /**
     * @return void
     */
    public function setDefaultConfig()
    {

        if (empty($this->loggingEnabled)) {
            return;
        }

        $realtimeupdates_initiated = $this->scopeConfig->getValue('clerk/log/realtimeupdatesfirst');
        $collectemails_initiated = $this->scopeConfig->getValue('clerk/log/collectemailsfirst');
        $onlysynchronizesaleableproducts_initiated = $this->scopeConfig->getValue('clerk/log/onlysynchronizesaleableproductsfirst');
        $disableordersynchronization_initiated = $this->scopeConfig->getValue('clerk/log/disableordersynchronizationfirst');
        $facetedsearchsettings_initiated = $this->scopeConfig->getValue('clerk/log/facetedsearchsettingsfirst');
        $categorysettings_initiated = $this->scopeConfig->getValue('clerk/log/categorysettingsfirst');
        $productsettings_initiated = $this->scopeConfig->getValue('clerk/log/productsettingsfirst');
        $cartsettings_initiated = $this->scopeConfig->getValue('clerk/log/cartsettingsfirst');
        $livesearch_initiated = $this->scopeConfig->getValue('clerk/log/livesearchfirst');
        $search_initiated = $this->scopeConfig->getValue('clerk/log/searchfirst');
        $powerstep_initiated = $this->scopeConfig->getValue('clerk/log/powerstepfirst');

        if ($this->scopeConfig->getValue('clerk/product_synchronization/use_realtime_updates') == '1' && !$realtimeupdates_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/realtimeupdatesfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Realtime Updates initiated', ['' => '']);
        }

        if (!$this->scopeConfig->getValue('clerk/product_synchronization/use_realtime_updates') == '1' && $realtimeupdates_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/realtimeupdatesfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Realtime Updates uninitiated', ['' => '']);
        }

        if ($this->scopeConfig->getValue('clerk/product_synchronization/collect_emails') == '1' && !$collectemails_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/collectemailsfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Collect Emails initiated', ['' => '']);
        }

        if (!$this->scopeConfig->getValue('clerk/product_synchronization/collect_emails') == '1' && $collectemails_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/collectemailsfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Collect Emails uninitiated', ['' => '']);
        }

        if ($this->scopeConfig->getValue('clerk/product_synchronization/saleable_only') == '1' && !$onlysynchronizesaleableproducts_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/onlysynchronizesaleableproductsfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Only Sync Saleable Products initiated', ['' => '']);
        }

        if (!$this->scopeConfig->getValue('clerk/product_synchronization/saleable_only') == '1' && $onlysynchronizesaleableproducts_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/onlysynchronizesaleableproductsfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Only Sync Saleable Products uninitiated', ['' => '']);
        }

        if ($this->scopeConfig->getValue('clerk/product_synchronization/disable_order_synchronization') == '1' && !$disableordersynchronization_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/disableordersynchronizationfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Disable Order Synchronization initiated', ['' => '']);
        }

        if (!$this->scopeConfig->getValue('clerk/product_synchronization/disable_order_synchronization') == '1' && $disableordersynchronization_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/disableordersynchronizationfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Disable Order Synchronization uninitiated', ['' => '']);
        }

        if ($this->scopeConfig->getValue('clerk/faceted_search/enabled') == '1' && !$facetedsearchsettings_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/facetedsearchsettingsfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Faceted Search Settings initiated', ['' => '']);
        }

        if (!$this->scopeConfig->getValue('clerk/faceted_search/enabled') == '1' && $facetedsearchsettings_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/facetedsearchsettingsfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Faceted Search Settings uninitiated', ['' => '']);
        }

        if ($this->scopeConfig->getValue('clerk/category/enabled') == '1' && !$categorysettings_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/categorysettingsfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Category Settings initiated', ['' => '']);
        }

        if (!$this->scopeConfig->getValue('clerk/category/enabled') == '1' && $categorysettings_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/categorysettingsfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Category Settings uninitiated', ['' => '']);
        }

        if ($this->scopeConfig->getValue('clerk/product/enabled') == '1' && !$productsettings_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/productsettingsfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Product Settings initiated', ['' => '']);
        }

        if (!$this->scopeConfig->getValue('clerk/product/enabled') == '1' && $productsettings_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/productsettingsfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Product Settings uninitiated', ['' => '']);
        }

        if ($this->scopeConfig->getValue('clerk/cart/enabled') == '1' && !$cartsettings_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/cartsettingsfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Cart Settings initiated', ['' => '']);
        }

        if (!$this->scopeConfig->getValue('clerk/cart/enabled') == '1' && $cartsettings_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/cartsettingsfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Cart Settings uninitiated', ['' => '']);
        }

        if ($this->scopeConfig->getValue('clerk/livesearch/enabled') == '1' && !$livesearch_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/livesearchfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Live Search initiated', ['' => '']);
        }

        if (!$this->scopeConfig->getValue('clerk/livesearch/enabled') == '1' && $livesearch_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/livesearchfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Live Search uninitiated', ['' => '']);
        }

        if ($this->scopeConfig->getValue('clerk/search/enabled') == '1' && !$search_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/searchfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Search initiated', ['' => '']);
        }

        if (!$this->scopeConfig->getValue('clerk/search/enabled') == '1' && $search_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/searchfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Search uninitiated', ['' => '']);
        }

        if ($this->scopeConfig->getValue('clerk/powerstep/enabled') == '1' && !$powerstep_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/powerstepfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Powerstep initiated', ['' => '']);
        }

        if (!$this->scopeConfig->getValue('clerk/powerstep/enabled') == '1' && $powerstep_initiated == 1) {
            $this->configWriter->saveConfig('clerk/log/powerstepfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->log('Powerstep uninitiated', ['' => '']);
        }
    }

    /**
     * @param $message
     * @param $metadata
     */
    public function log($message, $metadata)
    {
        $metadata = $this->getMetadata($metadata);
        $message_type = 'log';

        if (empty($this->loggingEnabled)) {
            return;
        }
        if ($this->loggingLevel !== 'all') {
            return;
        }
        if ($this->loggingMethod == 'collect') {
            $this->sendTraces($message_type, $message, $metadata);
        } elseif ($this->loggingMethod == 'file') {
            $this->logToFile($message, $metadata);
        }
    }

    /**
     * @param $metadata
     * @return mixed
     */
    public function getMetadata($metadata)
    {
        $version = $this->productMetadata->getVersion();
        header('User-Agent: ClerkExtensionBot Magento 2/v' . $version . ' clerk/v' . $this->moduleList->getOne('Clerk_Clerk')['setup_version'] . ' PHP/v' . phpversion());
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' && isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {
            $metadata['uri'] = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        } elseif (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {
            $metadata['uri'] = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }

        if ($_GET) {
            $metadata['params'] = $_GET;
        } elseif ($_POST) {
            $metadata['params'] = $_POST;
        }
        return $metadata;
    }

    public function sendTraces($messageType, $message, $metadata)
    {

        $data_string = json_encode([
            'key' => $this->publicKey,
            'source' => $this->platform,
            'time' => $this->currentTime,
            'type' => $messageType,
            'message' => $message,
            'metadata' => $metadata
        ]);

        if (!function_exists('curl_init') || !function_exists('curl_setopt') || !function_exists('curl_exec')) {
            $this->logToFile($message, $metadata);
        } else {
            $connection = curl_init();

            curl_setopt($connection, CURLOPT_URL, $this->endpoint);
            curl_setopt($connection, CURLOPT_POST, true);
            curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($connection, CURLOPT_POSTFIELDS, $data_string);

            $response = json_decode(curl_exec($connection));

            if ($response->status == 'error') {
                $this->logToFile($message, $metadata);
            }

            curl_close($connection);
        }
    }

    /**
     * @param $message
     * @param $metadata
     * @return void
     */
    public function logToFile($message, $metadata)
    {
        try {
            $log_message = $this->currentDate->format('Y-m-d H:i:s') . ' MESSAGE: ' . $message . ' METADATA: ' . json_encode($metadata) . PHP_EOL .
                '-------------------------' . PHP_EOL;
            $log_path = $this->directory->getPath('log') . '/clerk_log.log';

            fopen($log_path, "a+");
            file_put_contents($log_path, $log_message, FILE_APPEND);
        } catch (FileSystemException) {
            // Drop error if file system exception is found
        }
    }

    /**
     * @param $message
     * @param $metadata
     */
    public function error($message, $metadata)
    {
        $metadata = $this->getMetadata($metadata);
        $message_type = 'error';

        if (empty($this->loggingEnabled)) {
            return;
        }
        if ($this->loggingMethod == 'collect') {
            $this->sendTraces($message_type, $message, $metadata);
        } elseif ($this->loggingMethod == 'file') {
            $this->logToFile($message, $metadata);
        }
    }

    /**
     * @param $message
     * @param $metadata
     */
    public function warn($message, $metadata)
    {
        $metadata = $this->getMetadata($metadata);
        $message_type = 'warn';

        if (empty($this->loggingEnabled)) {
            return;
        }
        if ($this->loggingLevel == 'error') {
            return;
        }
        if ($this->loggingMethod == 'collect') {
            $this->sendTraces($message_type, $message, $metadata);
        } elseif ($this->loggingMethod == 'file') {
            $this->logToFile($message, $metadata);
        }

    }
}
