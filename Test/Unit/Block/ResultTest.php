<?php

namespace Clerk\Clerk\Test\Unit\Block;

use Clerk\Clerk\Block\Result;
use Magento\CatalogSearch\Helper\Data as CatalogSearchHelper;
use Magento\Framework\View\Element\Template;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    public function testResultBlockDoesNotDependOnNativeSearchResultBlock()
    {
        $this->assertSame(Template::class, get_parent_class(Result::class));
    }

    public function testSearchQueryIsEscapedByMagentoSearchHelper()
    {
        $escapedQuery = 'escaped search query';
        $catalogSearchData = $this->createMock(CatalogSearchHelper::class);
        $catalogSearchData->expects($this->once())
            ->method('getEscapedQueryText')
            ->willReturn($escapedQuery);

        $reflection = new \ReflectionClass(Result::class);
        $result = $reflection->newInstanceWithoutConstructor();
        $catalogSearchDataProperty = $reflection->getProperty('catalogSearchData');
        $catalogSearchDataProperty->setAccessible(true);
        $catalogSearchDataProperty->setValue($result, $catalogSearchData);

        $this->assertSame($escapedQuery, $result->getSearchQuery());
    }

    public function testSearchNoteMessagesComeFromMagentoSearchHelper()
    {
        $messages = ['Search query was truncated.'];
        $catalogSearchData = $this->createMock(CatalogSearchHelper::class);
        $catalogSearchData->expects($this->once())
            ->method('getNoteMessages')
            ->willReturn($messages);

        $reflection = new \ReflectionClass(Result::class);
        $result = $reflection->newInstanceWithoutConstructor();
        $catalogSearchDataProperty = $reflection->getProperty('catalogSearchData');
        $catalogSearchDataProperty->setAccessible(true);
        $catalogSearchDataProperty->setValue($result, $catalogSearchData);

        $this->assertSame($messages, $result->getNoteMessages());
    }
}
