<?php

namespace Clerk\Clerk\Controller\Logger;

use Clerk\Clerk\Model\Config;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class ClerkLogger
{
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

    /**
     * ClerkLogger constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param DirectoryList $dir
     * @param LoggerInterface $logger
     * @param TimezoneInterface $date
     * @param ConfigInterface $configWriter
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    function __construct(ScopeConfigInterface $scopeConfig, DirectoryList $dir, LoggerInterface $logger, TimezoneInterface $date, ConfigInterface $configWriter)
    {

        $this->configWriter = $configWriter;
        $this->_dir = $dir;
        $this->scopeConfig = $scopeConfig;
        $this->Platform = 'Magento 2';
        $this->Key = $this->scopeConfig->getValue(Config::XML_PATH_PUBLIC_KEY);
        $this->Date = $date->date();
        $this->Time = $date->scopeTimeStamp();
        $this->Log_level = $this->scopeConfig->getValue(Config::XML_PATH_LOG_LEVEL);
        $this->Log_to = $this->scopeConfig->getValue(Config::XML_PATH_LOG_TO);
        $this->Enabled = $this->scopeConfig->getValue(Config::XML_PATH_LOG_ENABLED);
        $this->InitializeSearchPowerstep();

    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function InitializeSearchPowerstep()
    {

        if ($this->Enabled !== '1') {


        } else {

            if ($this->scopeConfig->getValue('clerk/log/livesearchfirst') !== false) {

            } else {

                $this->configWriter->saveConfig('clerk/log/livesearchfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);

            }

            if ($this->scopeConfig->getValue('clerk/log/searchfirst') !== false) {

            } else {

                $this->configWriter->saveConfig('clerk/log/searchfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);

            }

            if ($this->scopeConfig->getValue('clerk/log/powerstepfirst') !== false) {

            } else {

                $this->configWriter->saveConfig('clerk/log/powerstepfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);

            }

            $livesearch_initiated = $this->scopeConfig->getValue('clerk/log/livesearchfirst');

            $search_initiated = $this->scopeConfig->getValue('clerk/log/searchfirst');

            $powerstep_initiated = $this->scopeConfig->getValue('clerk/log/powerstepfirst');

            if ($this->scopeConfig->getValue('clerk/livesearch/enabled') == 1 && !$livesearch_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/livesearchfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Live Search initiated', []);

            }

            if (!$this->scopeConfig->getValue('clerk/livesearch/enabled') == 1 && $livesearch_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/livesearchfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Live Search uninitiated', []);

            }

            if ($this->scopeConfig->getValue('clerk/search/enabled') == 1 && !$search_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/searchfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Search initiated', []);

            }

            if (!$this->scopeConfig->getValue('clerk/search/enabled') == 1 && $search_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/searchfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Search uninitiated', []);

            }

            if ($this->scopeConfig->getValue('clerk/powerstep/enabled') == 1 && !$powerstep_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/powerstepfirst', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Powerstep initiated', []);

            }

            if (!$this->scopeConfig->getValue('clerk/powerstep/enabled') == 1 && $powerstep_initiated == 1) {

                $this->configWriter->saveConfig('clerk/log/powerstepfirst', '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
                $this->log('Powerstep uninitiated', []);

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
        //print_r($this->Enabled);
        //exit;

        $JSON_Metadata_Encode = json_encode($Metadata);
        $Type = 'log';

        if ($this->Enabled !== '1') {

        } else {


            if ($this->Log_level !== 'all') {

            } else {

                if ($this->Log_to == 'collect') {

                    if ($this->Log_level == 'all') {

                        $Endpoint = 'api.clerk.io/v2/log/debug?debug=1&key=' . $this->Key . '&source=' . $this->Platform . '&time=' . $this->Time . '&type=' . $Type . '&message=' . $Message . '&metadata=' . urlencode($JSON_Metadata_Encode);

                    } else {

                        $Endpoint = 'api.clerk.io/v2/log/debug?key=' . $this->Key . '&source=' . $this->Platform . '&time=' . $this->Time . '&type=' . $Type . '&message=' . $Message . '&metadata=' . urlencode($JSON_Metadata_Encode);

                    }

                    $curl = curl_init();

                    curl_setopt($curl, CURLOPT_URL, $Endpoint);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_exec($curl);
                    curl_close($curl);

                } elseif ($this->Log_to == 'file') {

                    $log = $this->Date->format('Y-m-d H:i:s') . ' MESSAGE: ' . $Message . ' METADATA: ' . $JSON_Metadata_Encode . PHP_EOL .
                        '-------------------------' . PHP_EOL;
                    $path = $this->_dir->getPath('log') . '/clerk_log.log';

                    fopen($path, "a+");
                    file_put_contents($path, $log, FILE_APPEND);

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

        //Customize $Platform and the function for getting the public key.
        $JSON_Metadata_Encode = json_encode($Metadata);
        $Type = 'error';

        if ($this->Enabled !== '1') {


        } else {

            if ($this->Log_to == 'collect') {

                if ($this->Log_level == 'all') {

                    $Endpoint = 'api.clerk.io/v2/log/debug?debug=1&key=' . $this->Key . '&source=' . $this->Platform . '&time=' . $this->Time . '&type=' . $Type . '&message=' . $Message . '&metadata=' . urlencode($JSON_Metadata_Encode);

                } else {

                    $Endpoint = 'api.clerk.io/v2/log/debug?key=' . $this->Key . '&source=' . $this->Platform . '&time=' . $this->Time . '&type=' . $Type . '&message=' . $Message . '&metadata=' . urlencode($JSON_Metadata_Encode);

                }

                $curl = curl_init();

                curl_setopt($curl, CURLOPT_URL, $Endpoint);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_exec($curl);
                curl_close($curl);

            } elseif ($this->Log_to == 'file') {

                $log = $this->Date->format('Y-m-d H:i:s') . ' MESSAGE: ' . $Message . ' METADATA: ' . $JSON_Metadata_Encode . PHP_EOL .
                    '-------------------------' . PHP_EOL;
                $path = $this->_dir->getPath('log') . '/clerk_log.log';

                fopen($path, "a+");
                file_put_contents($path, $log, FILE_APPEND);

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

        //Customize $Platform and the function for getting the public key.
        $JSON_Metadata_Encode = json_encode($Metadata);
        $Type = 'warn';

        if ($this->Enabled !== '1') {


        } else {

            if ($this->Log_level == 'error') {


            } else {

                if ($this->Log_to == 'collect') {

                    if ($this->Log_level == 'all') {

                        $Endpoint = 'api.clerk.io/v2/log/debug?debug=1&key=' . $this->Key . '&source=' . $this->Platform . '&time=' . $this->Time . '&type=' . $Type . '&message=' . $Message . '&metadata=' . urlencode($JSON_Metadata_Encode);

                    } else {

                        $Endpoint = 'api.clerk.io/v2/log/debug?key=' . $this->Key . '&source=' . $this->Platform . '&time=' . $this->Time . '&type=' . $Type . '&message=' . $Message . '&metadata=' . urlencode($JSON_Metadata_Encode);

                    }

                    $curl = curl_init();

                    curl_setopt($curl, CURLOPT_URL, $Endpoint);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_exec($curl);
                    curl_close($curl);

                } elseif ($this->Log_to == 'file') {

                    $log = $this->Date->format('Y-m-d H:i:s') . ' MESSAGE: ' . $Message . ' METADATA: ' . $JSON_Metadata_Encode . PHP_EOL .
                        '-------------------------' . PHP_EOL;
                    $path = $this->_dir->getPath('log') . '/clerk_log.log';

                    fopen($path, "a+");
                    file_put_contents($path, $log, FILE_APPEND);

                }
            }
        }
    }
}
