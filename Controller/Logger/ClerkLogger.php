<?php

namespace Clerk\Clerk\Controller\Logger;

use Clerk\Clerk\Model\Config;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Store\Model\Store;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Module\ModuleList;
use Magento\Store\Model\ScopeInterface;

use Magento\Framework\App\ProductMetadataInterface;

class ClerkLogger
{

    /**
     * @var ProductMetadataInterface
     */
    protected $_product_metadata;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var
     */
    protected $configWriter;
    /**
     * @var DirectoryList
     */
    protected $_dir;
    /**
     * @var string
     */
    private $Platform;
    /**
     * @var mixed
     */
    private $Key;
    /**
     * @var DateTime
     */
    private $Date;
    /**
     * @var
     */
    private $Enabled;
    /**
     * @var
     */
    private $Time;
    /**
     * @var mixed
     */
    private $Log_level;
    /**
     * @var mixed
     */
    private $Log_to;

    protected $moduleList;

    /**
     * ClerkLogger constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param DirectoryList $dir
     * @param LoggerInterface $logger
     * @param TimezoneInterface $date
     * @param ConfigInterface $configWriter
     * @param ProductMetadataInterface $product_metadata
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    function __construct(
        ScopeConfigInterface $scopeConfig,
        DirectoryList $dir,
        TimezoneInterface $date,
        ConfigInterface $configWriter,
        ModuleList $moduleList,
        ProductMetadataInterface $product_metadata
        )
    {

        $this->configWriter = $configWriter;
        $this->_dir = $dir;
        $this->scopeConfig = $scopeConfig;
        $this->Platform = 'Magento 2';
        $this->Key = $this->scopeConfig->getValue(Config::XML_PATH_PUBLIC_KEY, ScopeInterface::SCOPE_STORE);
        $this->Date = $date->date();
        $this->Time = $date->scopeTimeStamp();
        $this->Log_level = $this->scopeConfig->getValue(Config::XML_PATH_LOG_LEVEL, ScopeInterface::SCOPE_STORE);
        $this->Log_to = $this->scopeConfig->getValue(Config::XML_PATH_LOG_TO, ScopeInterface::SCOPE_STORE);
        $this->Enabled = $this->scopeConfig->getValue(Config::XML_PATH_LOG_ENABLED, ScopeInterface::SCOPE_STORE);
        $this->moduleList = $moduleList;
        $this->_product_metadata = $product_metadata;
        $this->InitializeSearchPowerstep();
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function InitializeSearchPowerstep()
    {

        if ($this->Enabled !== '1') {


        } else {

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

            //Realtime Updates Initialize
            if ($this->scopeConfig->getValue('clerk/product_synchronization/use_realtime_updates') == '1' && !$realtimeupdates_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/realtimeupdatesfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Realtime Updates initiated', ['' => '']);

            }

            if (!$this->scopeConfig->getValue('clerk/product_synchronization/use_realtime_updates') == '1' && $realtimeupdates_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/realtimeupdatesfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Realtime Updates uninitiated', ['' => '']);

            }

            //Collect Emails Initialize
            if ($this->scopeConfig->getValue('clerk/product_synchronization/collect_emails') == '1' && !$collectemails_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/collectemailsfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Collect Emails initiated', ['' => '']);

            }

            if (!$this->scopeConfig->getValue('clerk/product_synchronization/collect_emails') == '1' && $collectemails_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/collectemailsfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Collect Emails uninitiated', ['' => '']);

            }

            //Only Sync Saleable Products Initialize
            if ($this->scopeConfig->getValue('clerk/product_synchronization/saleable_only') == '1' && !$onlysynchronizesaleableproducts_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/onlysynchronizesaleableproductsfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Only Sync Saleable Products initiated', ['' => '']);

            }

            if (!$this->scopeConfig->getValue('clerk/product_synchronization/saleable_only') == '1' && $onlysynchronizesaleableproducts_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/onlysynchronizesaleableproductsfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Only Sync Saleable Products uninitiated', ['' => '']);

            }

            //Disable Order Synchronization Initialize
            if ($this->scopeConfig->getValue('clerk/product_synchronization/disable_order_synchronization') == '1' && !$disableordersynchronization_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/disableordersynchronizationfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Disable Order Synchronization initiated', ['' => '']);

            }

            if (!$this->scopeConfig->getValue('clerk/product_synchronization/disable_order_synchronization') == '1' && $disableordersynchronization_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/disableordersynchronizationfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Disable Order Synchronization uninitiated', ['' => '']);

            }

            //Faceted Search Settings Initialize
            if ($this->scopeConfig->getValue('clerk/faceted_search/enabled') == '1' && !$facetedsearchsettings_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/facetedsearchsettingsfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Faceted Search Settings initiated', ['' => '']);

            }

            if (!$this->scopeConfig->getValue('clerk/faceted_search/enabled') == '1' && $facetedsearchsettings_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/facetedsearchsettingsfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Faceted Search Settings uninitiated', ['' => '']);

            }

            //Category Settings Initialize
            if ($this->scopeConfig->getValue('clerk/category/enabled') == '1' && !$categorysettings_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/categorysettingsfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Category Settings initiated', ['' => '']);

            }

            if (!$this->scopeConfig->getValue('clerk/category/enabled') == '1' && $categorysettings_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/categorysettingsfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Category Settings uninitiated', ['' => '']);

            }

            //Product Settings Initialize
            if ($this->scopeConfig->getValue('clerk/product/enabled') == '1' && !$productsettings_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/productsettingsfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Product Settings initiated', ['' => '']);

            }

            if (!$this->scopeConfig->getValue('clerk/product/enabled') == '1' && $productsettings_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/productsettingsfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Product Settings uninitiated', ['' => '']);

            }

            //Cart Settings Initialize
            if ($this->scopeConfig->getValue('clerk/cart/enabled') == '1' && !$cartsettings_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/cartsettingsfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Cart Settings initiated', ['' => '']);

            }

            if (!$this->scopeConfig->getValue('clerk/cart/enabled') == '1' && $cartsettings_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/cartsettingsfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Cart Settings uninitiated', ['' => '']);

            }

            //Live Search Initialize
            if ($this->scopeConfig->getValue('clerk/livesearch/enabled') == '1' && !$livesearch_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/livesearchfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Live Search initiated', ['' => '']);

            }

            if (!$this->scopeConfig->getValue('clerk/livesearch/enabled') == '1' && $livesearch_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/livesearchfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Live Search uninitiated', ['' => '']);

            }

            //Search Initialize
            if ($this->scopeConfig->getValue('clerk/search/enabled') == '1' && !$search_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/searchfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Search initiated', ['' => '']);

            }

            if (!$this->scopeConfig->getValue('clerk/search/enabled') == '1' && $search_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/searchfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Search uninitiated', ['' => '']);

            }

            //Powerstep Initialize
            if ($this->scopeConfig->getValue('clerk/powerstep/enabled') == '1' && !$powerstep_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/powerstepfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Powerstep initiated', ['' => '']);

            }

            if (!$this->scopeConfig->getValue('clerk/powerstep/enabled') == '1' && $powerstep_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/powerstepfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Powerstep uninitiated', ['' => '']);

            }
        }
    }

    /**
     * @param $Message
     * @param $Metadata
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function log($Message, $Metadata)
    {
        $version = $this->_product_metadata->getVersion();
        header('User-Agent: ClerkExtensionBot Magento 2/v' . $version . ' clerk/v' . $this->moduleList->getOne('Clerk_Clerk')['setup_version'] . ' PHP/v' . phpversion());
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' && isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {

            $Metadata['uri'] = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        } elseif (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {

            $Metadata['uri'] = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        }

        if ($_GET) {

            $Metadata['params'] = $_GET;

        } elseif ($_POST) {

            $Metadata['params'] = $_POST;

        }

        $Type = 'log';

        if ($this->Enabled !== '1') {

        } else {


            if ($this->Log_level !== 'all') {

            } else {

                if ($this->Log_to == 'collect') {

                    $Endpoint = 'https://api.clerk.io/v2/log/debug';

                    $data_string = json_encode([
                        'key' =>$this->Key,
                        'source' => $this->Platform,
                        'time' => $this->Time,
                        'type' => $Type,
                        'message' => $Message,
                        'metadata' => $Metadata]);

                    $curl = curl_init();

                    curl_setopt($curl, CURLOPT_URL, $Endpoint);
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

                    $response = json_decode(curl_exec($curl));

                    if ($response->status == 'error') {

                        $this->LogToFile($Message, $Metadata);

                    }

                    curl_close($curl);

                } elseif ($this->Log_to == 'file') {

                    $this->LogToFile($Message, $Metadata);

                }
            }
        }
    }

    /**
     * @param $Message
     * @param $Metadata
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function error($Message, $Metadata)
    {
        $version = $this->_product_metadata->getVersion();
        header('User-Agent: ClerkExtensionBot Magento 2/v' . $version . ' clerk/v' . $this->moduleList->getOne('Clerk_Clerk')['setup_version'] . ' PHP/v' . phpversion());
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' && isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {

            $Metadata['uri'] = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        } elseif (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {

            $Metadata['uri'] = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        }

        if ($_GET) {

            $Metadata['params'] = $_GET;

        } elseif ($_POST) {

            $Metadata['params'] = $_POST;

        }

        $Type = 'error';

        if ($this->Enabled !== '1') {


        } else {

            if ($this->Log_to == 'collect') {

                $Endpoint = 'https://api.clerk.io/v2/log/debug';

                $data_string = json_encode([
                    'debug' => '1',
                    'key' =>$this->Key,
                    'source' => $this->Platform,
                    'time' => $this->Time,
                    'type' => $Type,
                    'message' => $Message,
                    'metadata' => $Metadata]);

                $curl = curl_init();

                curl_setopt($curl, CURLOPT_URL, $Endpoint);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

                $response = json_decode(curl_exec($curl));

                if ($response->status == 'error') {

                    $this->LogToFile($Message, $Metadata);

                }

                curl_close($curl);

            } elseif ($this->Log_to == 'file') {

                $this->LogToFile($Message, $Metadata);

            }
        }
    }

    /**
     * @param $Message
     * @param $Metadata
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function warn($Message, $Metadata)
    {
        $version = $this->_product_metadata->getVersion();
        header('User-Agent: ClerkExtensionBot Magento 2/v' . $version . ' clerk/v' . $this->moduleList->getOne('Clerk_Clerk')['setup_version'] . ' PHP/v' . phpversion());
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' && isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {

            $Metadata['uri'] = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        } elseif (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {

            $Metadata['uri'] = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        }

        if ($_GET) {

            $Metadata['params'] = $_GET;

        } elseif ($_POST) {

            $Metadata['params'] = $_POST;

        }

        $Type = 'warn';

        if ($this->Enabled !== '1') {


        } else {

            if ($this->Log_level == 'error') {


            } else {

                if ($this->Log_to == 'collect') {

                    $Endpoint = 'https://api.clerk.io/v2/log/debug';

                    $data_string = json_encode([
                        'debug' => '1',
                        'key' =>$this->Key,
                        'source' => $this->Platform,
                        'time' => $this->Time,
                        'type' => $Type,
                        'message' => $Message,
                        'metadata' => $Metadata]);

                    $curl = curl_init();

                    curl_setopt($curl, CURLOPT_URL, $Endpoint);
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

                    $response = json_decode(curl_exec($curl));

                    if ($response->status == 'error') {

                        $this->LogToFile($Message, $Metadata);

                    }

                    curl_close($curl);

                } elseif ($this->Log_to == 'file') {

                    $this->LogToFile($Message, $Metadata);

                }
            }
        }
    }

    public function LogToFile($Message, $Metadata)
    {

        $log = $this->Date->format('Y-m-d H:i:s') . ' MESSAGE: ' . $Message . ' METADATA: ' . json_encode($Metadata) . PHP_EOL .
            '-------------------------' . PHP_EOL;
        $path = $this->_dir->getPath('log') . '/clerk_log.log';

        fopen($path, "a+");
        file_put_contents($path, $log, FILE_APPEND);
    }
}
