<?php

namespace AnimaID\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use AnimaID\Services\WikiService;
use AnimaID\Repositories\WikiRepository;
use AnimaID\Config\ConfigManager;

class WikiServiceTest extends TestCase
{
    private $wikiRepository;
    private $config;
    private $wikiService;

    protected function setUp(): void
    {
        $this->wikiRepository = $this->createMock(WikiRepository::class);
        $this->config = $this->createMock(ConfigManager::class);

        $this->wikiService = new WikiService(
            $this->wikiRepository,
            $this->config
        );
    }

    public function testGetPages()
    {
        $mockPages = [
            ['id' => 1, 'title' => 'Test Page']
        ];
        
        $this->wikiRepository->expects($this->once())
            ->method('findPagesWithFilters')
            ->willReturn($mockPages);
            
        $this->wikiRepository->expects($this->once())
            ->method('countPagesWithFilters')
            ->willReturn(1);

        $result = $this->wikiService->getPages(1, 10, []);
        
        $this->assertEquals($mockPages, $result['pages']);
        $this->assertEquals(1, $result['pagination']['total']);
    }

    public function testCreatePage()
    {
        $pageData = [
            'title' => 'Test Page',
            'content' => 'Some content'
        ];
        
        $userId = 1;
        $createdId = 5;
        
        $this->wikiRepository->method('findBySlug')->willReturn(null);
        
        $this->wikiRepository->expects($this->once())
            ->method('insert')
            ->willReturn($createdId);
            
        $this->wikiRepository->expects($this->once())
            ->method('findById')
            ->with($createdId)
            ->willReturn(array_merge($pageData, ['id' => $createdId, 'slug' => 'test-page']));

        $result = $this->wikiService->createPage($pageData, $userId);
        
        $this->assertEquals($createdId, $result['id']);
        $this->assertEquals('Test Page', $result['title']);
    }

    public function testGetPageBySlug()
    {
        $slug = 'test-page';
        $mockPage = ['id' => 1, 'title' => 'Test Page', 'slug' => $slug];
        
        $this->wikiRepository->expects($this->once())
            ->method('findBySlug')
            ->with($slug)
            ->willReturn($mockPage);
            
        $this->wikiRepository->expects($this->once())
            ->method('incrementViewCount')
            ->with(1);

        $result = $this->wikiService->getPageBySlug($slug);
        
        $this->assertEquals($mockPage, $result);
    }
}
