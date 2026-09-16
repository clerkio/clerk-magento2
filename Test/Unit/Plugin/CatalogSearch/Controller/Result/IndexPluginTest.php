<?php

namespace Clerk\Clerk\Test\Unit\Plugin\CatalogSearch\Controller\Result;

use Clerk\Clerk\Model\Config;
use Clerk\Clerk\Plugin\CatalogSearch\Controller\Result\IndexPlugin;
use Magento\CatalogSearch\Controller\Result\Index;
use Magento\CatalogSearch\Helper\Data as CatalogSearchHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Search\Model\Query;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IndexPluginTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var PageFactory|MockObject
     */
    private $resultPageFactory;

    /**
     * @var CatalogSearchHelper|MockObject
     */
    private $catalogSearchHelper;

    /**
     * @var QueryFactory|MockObject
     */
    private $queryFactory;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var UrlInterface|MockObject
     */
    private $url;

    /**
     * @var IndexPlugin
     */
    private $plugin;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->resultPageFactory = $this->createMock(PageFactory::class);
        $this->catalogSearchHelper = $this->createMock(CatalogSearchHelper::class);
        $this->queryFactory = $this->createMock(QueryFactory::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->url = $this->createMock(UrlInterface::class);

        $this->plugin = new IndexPlugin(
            $this->scopeConfig,
            $this->resultPageFactory,
            $this->catalogSearchHelper,
            $this->queryFactory,
            $this->storeManager,
            $this->url
        );
    }

    public function testNativeControllerRunsWhenClerkSearchIsDisabled()
    {
        $expectedResult = new \stdClass();

        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(Config::XML_PATH_SEARCH_ENABLED, ScopeInterface::SCOPE_STORE)
            ->willReturn(false);
        $this->queryFactory->expects($this->never())->method('get');

        $actualResult = $this->plugin->aroundExecute(
            $this->createMock(Index::class),
            function () use ($expectedResult) {
                return $expectedResult;
            }
        );

        $this->assertSame($expectedResult, $actualResult);
    }

    public function testNativeControllerHandlesEmptyQueries()
    {
        $expectedResult = new \stdClass();
        $query = $this->createQuery('');

        $this->enableClerkSearch();
        $this->queryFactory->expects($this->once())->method('get')->willReturn($query);

        $actualResult = $this->plugin->aroundExecute(
            $this->createMock(Index::class),
            function () use ($expectedResult) {
                return $expectedResult;
            }
        );

        $this->assertSame($expectedResult, $actualResult);
    }

    public function testConfiguredQueryRedirectIsPreserved()
    {
        $redirectUrl = 'https://example.com/redirect';
        $query = $this->createQuery('search term');
        $response = $this->createMock(Http::class);
        $subject = $this->createMock(Index::class);

        $this->enableClerkSearch();
        $this->queryFactory->expects($this->once())->method('get')->willReturn($query);
        $this->catalogSearchHelper->expects($this->once())
            ->method('isMinQueryLength')
            ->willReturn(false);
        $query->redirect = $redirectUrl;
        $this->url->expects($this->once())
            ->method('getCurrentUrl')
            ->willReturn('https://example.com/catalogsearch/result/?q=search');
        $subject->expects($this->once())->method('getResponse')->willReturn($response);
        $response->expects($this->once())->method('setRedirect')->with($redirectUrl);
        $this->resultPageFactory->expects($this->never())->method('create');

        $actualResult = $this->plugin->aroundExecute(
            $subject,
            function () {
                $this->fail('The native controller should not run for Clerk search redirects.');
            }
        );

        $this->assertNull($actualResult);
        $this->assertSame(1, $query->redirectCalls);
    }

    /**
     * @dataProvider facetedSearchProvider
     *
     * @param bool $facetedSearchEnabled
     */
    public function testClerkSearchUsesLightweightPage($facetedSearchEnabled)
    {
        $query = $this->createQuery('search term');
        $response = $this->createMock(Http::class);
        $subject = $this->createMock(Index::class);
        $resultPage = $this->createMock(Page::class);
        $pageConfig = $this->createMock(PageConfig::class);

        $this->enableClerkSearch($facetedSearchEnabled);
        $this->queryFactory->expects($this->once())->method('get')->willReturn($query);
        $this->catalogSearchHelper->expects($this->once())
            ->method('isMinQueryLength')
            ->willReturn(true);
        $this->catalogSearchHelper->expects($this->once())->method('checkNotes');
        $this->resultPageFactory->expects($this->once())
            ->method('create')
            ->willReturn($resultPage);
        $resultPage->expects($this->once())
            ->method('addHandle')
            ->with(IndexPlugin::CLERK_SEARCH_LAYOUT_HANDLE)
            ->willReturnSelf();
        $subject->expects($this->once())->method('getResponse')->willReturn($response);
        $response->expects($this->once())->method('setNoCacheHeaders');

        if ($facetedSearchEnabled) {
            $resultPage->expects($this->never())->method('getConfig');
        } else {
            $resultPage->expects($this->once())->method('getConfig')->willReturn($pageConfig);
            $pageConfig->expects($this->once())
                ->method('setPageLayout')
                ->with(IndexPlugin::ONE_COLUMN_PAGE_LAYOUT);
        }

        $actualResult = $this->plugin->aroundExecute(
            $subject,
            function () {
                $this->fail('The native controller should not run when Clerk search is enabled.');
            }
        );

        $this->assertSame($resultPage, $actualResult);
        $this->assertSame(1, $query->storeId);
    }

    public function facetedSearchProvider()
    {
        return [
            'faceted search enabled' => [true],
            'faceted search disabled' => [false],
        ];
    }

    /**
     * @param string $queryText
     * @return TestQuery
     */
    private function createQuery($queryText)
    {
        $storeId = 1;
        $query = new TestQuery($queryText);
        $store = $this->createMock(StoreInterface::class);

        $this->storeManager->expects($this->once())->method('getStore')->willReturn($store);
        $store->expects($this->once())->method('getId')->willReturn($storeId);

        return $query;
    }

    /**
     * @param bool|null $facetedSearchEnabled
     * @return void
     */
    private function enableClerkSearch($facetedSearchEnabled = null)
    {
        $this->scopeConfig->method('isSetFlag')
            ->willReturnCallback(
                function ($path, $scope) use ($facetedSearchEnabled) {
                    $this->assertSame(ScopeInterface::SCOPE_STORE, $scope);

                    if ($path === Config::XML_PATH_SEARCH_ENABLED) {
                        return true;
                    }

                    $this->assertSame(Config::XML_PATH_FACETED_SEARCH_ENABLED, $path);
                    return $facetedSearchEnabled;
                }
            );
    }
}

class TestQuery extends Query
{
    /**
     * @var string
     */
    private $queryText;

    /**
     * @var string|null
     */
    public $redirect;

    /**
     * @var int
     */
    public $redirectCalls = 0;

    /**
     * @var int|null
     */
    public $storeId;

    /**
     * @param string $queryText
     */
    public function __construct($queryText)
    {
        $this->queryText = $queryText;
    }

    /**
     * @param int $storeId
     * @return void
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * @return string
     */
    public function getQueryText()
    {
        return $this->queryText;
    }

    /**
     * @return string|null
     */
    public function getRedirect()
    {
        $this->redirectCalls++;
        return $this->redirect;
    }
}
