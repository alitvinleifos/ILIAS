<?php

declare(strict_types=1);

namespace ILIAS\Repository\Breadcrumbs;

use PHPUnit\Framework\TestCase;
use ILIAS\Repository\InternalDomainService;
use ILIAS\Repository\InternalGUIService;
use ILIAS\UI\Component\Link\Standard as StandardLink;
use ILIAS\UI\Component\Breadcrumbs\Breadcrumbs;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Component\Link\Factory as LinkFactory;
use ILIAS\DI\UIServices;

class BuilderTest extends TestCase
{
    protected function getBuilder(
        int $start_ref_id,
        int $end_ref_id,
        $domain_service,
        $gui_service,
        $wrapper
    ): Builder {
        return new Builder(
            $start_ref_id,
            $end_ref_id,
            $domain_service,
            $gui_service,
            $wrapper
        );
    }

    public function testImmutability(): void
    {
        $domain_service = $this->createMock(InternalDomainService::class);
        $gui_service = $this->createMock(InternalGUIService::class);
        $wrapper = $this->createMock(PresentationWrapperInterface::class);
        $link = $this->createMock(StandardLink::class);

        $builder = $this->getBuilder(1, 10, $domain_service, $gui_service, $wrapper);

        $this->assertNotSame($builder, $builder->withLeadCrumb($link));
        $this->assertNotSame($builder, $builder->withTrailCrumb($link));
        $this->assertNotSame($builder, $builder->withStartIncluded(false));
        $this->assertNotSame($builder, $builder->withEndIncluded(false));
    }

    public function testStartEndInclusion(): void
    {
        $domain_service = $this->createMock(InternalDomainService::class);
        $gui_service = $this->createMock(InternalGUIService::class);
        $wrapper = $this->createMock(PresentationWrapperInterface::class);

        $tree = $this->createMock(\ilTree::class);
        $tree->method('getPathId')->willReturn([1, 2, 3, 4, 5]);
        $domain_service->method('repositoryTree')->willReturn($tree);

        $builder = $this->getBuilder(1, 5, $domain_service, $gui_service, $wrapper);
        $reflector = new \ReflectionClass(Builder::class);
        $method = $reflector->getMethod('getPathIds');
        $method->setAccessible(true);

        $this->assertEquals([1, 2, 3, 4, 5], $method->invoke($builder));

        $builder2 = $builder->withStartIncluded(false);
        $this->assertEquals([2, 3, 4, 5], $method->invoke($builder2));

        $builder3 = $builder->withEndIncluded(false);
        $this->assertEquals([1, 2, 3, 4], $method->invoke($builder3));

        $builder4 = $builder->withStartIncluded(false)->withEndIncluded(false);
        $this->assertEquals([2, 3, 4], $method->invoke($builder4));
    }

    public function testYieldPathElements(): void
    {
        $domain_service = $this->createMock(InternalDomainService::class);
        $gui_service = $this->createMock(InternalGUIService::class);
        $wrapper = $this->createMock(PresentationWrapperInterface::class);

        $tree = $this->createMock(\ilTree::class);
        $tree->method('getPathId')->willReturn([1, 2, 3]);
        $domain_service->method('repositoryTree')->willReturn($tree);

        $access = $this->createMock(\ilAccessHandler::class);
        $access->method('checkAccess')->willReturn(true);
        $domain_service->method('access')->willReturn($access);

        $wrapper->method('lookupObjId')->willReturnCallback(fn($id) => $id + 100);
        $wrapper->method('lookupType')->willReturn('type');
        $wrapper->method('buildTitle')->willReturnCallback(fn($id) => "Title $id");
        $wrapper->method('buildLink')->willReturnCallback(fn($id) => "Link $id");

        $ui_factory = $this->createMock(UIFactory::class);
        $link_factory = $this->createMock(LinkFactory::class);
        $ui_factory->method('link')->willReturn($link_factory);

        $ui_services = $this->createMock(UIServices::class);
        $ui_services->method('factory')->willReturn($ui_factory);
        $gui_service->method('ui')->willReturn($ui_services);

        $link_mock = $this->createMock(StandardLink::class);
        $link_factory->method('standard')->willReturn($link_mock);
        $link_mock->method('withDisabled')->willReturn($link_mock);

        $builder = $this->getBuilder(1, 3, $domain_service, $gui_service, $wrapper);

        $reflector = new \ReflectionClass(Builder::class);
        $method = $reflector->getMethod('yieldPathElements');
        $method->setAccessible(true);

        $elements = iterator_to_array($method->invoke($builder));
        $this->assertCount(3, $elements);
    }

    public function testGapHandling(): void
    {
        $domain_service = $this->createMock(InternalDomainService::class);
        $gui_service = $this->createMock(InternalGUIService::class);
        $wrapper = $this->createMock(PresentationWrapperInterface::class);

        $tree = $this->createMock(\ilTree::class);
        $tree->method('getPathId')->willReturn([1, 2, 3, 4]);
        $domain_service->method('repositoryTree')->willReturn($tree);

        $access = $this->createMock(\ilAccessHandler::class);

        $access->method('checkAccess')->willReturnCallback(function($op, $p, $id) {
            if ($op === 'visible') {
                return in_array($id, [1, 4]);
            }
            return true;
        });
        $domain_service->method('access')->willReturn($access);

        $wrapper->method('lookupObjId')->willReturn(100);
        $wrapper->method('lookupType')->willReturn('type');
        $wrapper->method('buildTitle')->willReturn('Title');
        $wrapper->method('buildLink')->willReturn('Link');

        $ui_factory = $this->createMock(UIFactory::class);
        $link_factory = $this->createMock(LinkFactory::class);
        $ui_factory->method('link')->willReturn($link_factory);
        $ui_services = $this->createMock(UIServices::class);
        $ui_services->method('factory')->willReturn($ui_factory);
        $gui_service->method('ui')->willReturn($ui_services);

        $link_factory->method('standard')->willReturnCallback(function($title, $action) {
            $link = $this->createMock(StandardLink::class);
            $link->method('getLabel')->willReturn($title);
            $link->method('withDisabled')->willReturn($link);
            return $link;
        });

        $builder = $this->getBuilder(1, 4, $domain_service, $gui_service, $wrapper);

        $reflector = new \ReflectionClass(Builder::class);
        $method = $reflector->getMethod('yieldPathElements');
        $method->setAccessible(true);

        $elements = iterator_to_array($method->invoke($builder));

        // Expected: Title (1), ... (gap for 2,3), Title (4)
        $this->assertCount(3, $elements);
        $this->assertEquals('Title', $elements[0]->getLabel());
        $this->assertEquals('...', $elements[1]->getLabel());
        $this->assertEquals('Title', $elements[2]->getLabel());
    }

    public function testTrailingGap(): void
    {
        $domain_service = $this->createMock(InternalDomainService::class);
        $gui_service = $this->createMock(InternalGUIService::class);
        $wrapper = $this->createMock(PresentationWrapperInterface::class);

        $tree = $this->createMock(\ilTree::class);
        $tree->method('getPathId')->willReturn([1, 2]);
        $domain_service->method('repositoryTree')->willReturn($tree);

        $access = $this->createMock(\ilAccessHandler::class);
        // 1: visible, 2: NOT visible
        $access->method('checkAccess')->willReturnCallback(function($op, $p, $id) {
            return $id === 1;
        });
        $domain_service->method('access')->willReturn($access);

        $wrapper->method('lookupObjId')->willReturn(100);
        $wrapper->method('lookupType')->willReturn('type');
        $wrapper->method('buildTitle')->willReturn('Title');
        $wrapper->method('buildLink')->willReturn('Link');

        $ui_factory = $this->createMock(UIFactory::class);
        $link_factory = $this->createMock(LinkFactory::class);
        $ui_factory->method('link')->willReturn($link_factory);
        $ui_services = $this->createMock(UIServices::class);
        $ui_services->method('factory')->willReturn($ui_factory);
        $gui_service->method('ui')->willReturn($ui_services);

        $link_factory->method('standard')->willReturnCallback(function($title, $action) {
            $link = $this->createMock(StandardLink::class);
            $link->method('getLabel')->willReturn($title);
            $link->method('withDisabled')->willReturn($link);
            return $link;
        });

        $builder = $this->getBuilder(1, 2, $domain_service, $gui_service, $wrapper);

        $reflector = new \ReflectionClass(Builder::class);
        $method = $reflector->getMethod('yieldPathElements');
        $method->setAccessible(true);

        $elements = iterator_to_array($method->invoke($builder));

        // Expected: Title (1), ... (gap for 2)
        $this->assertCount(2, $elements);
        $this->assertEquals('Title', $elements[0]->getLabel());
        $this->assertEquals('...', $elements[1]->getLabel());
    }

    public function testGetAsString(): void
    {
        $domain_service = $this->createMock(InternalDomainService::class);
        $gui_service = $this->createMock(InternalGUIService::class);
        $wrapper = $this->createMock(PresentationWrapperInterface::class);

        $tree = $this->createMock(\ilTree::class);
        $tree->method('getPathId')->willReturn([1, 2]);
        $domain_service->method('repositoryTree')->willReturn($tree);

        $access = $this->createMock(\ilAccessHandler::class);
        $access->method('checkAccess')->willReturn(true);
        $domain_service->method('access')->willReturn($access);

        $wrapper->method('lookupObjId')->willReturn(100);
        $wrapper->method('lookupType')->willReturn('type');
        $wrapper->method('buildTitle')->willReturnCallback(fn($id) => "T$id");
        $wrapper->method('buildLink')->willReturn('Link');

        $ui_factory = $this->createMock(UIFactory::class);
        $link_factory = $this->createMock(LinkFactory::class);
        $ui_factory->method('link')->willReturn($link_factory);
        $ui_services = $this->createMock(UIServices::class);
        $ui_services->method('factory')->willReturn($ui_factory);
        $gui_service->method('ui')->willReturn($ui_services);

        $link_mock = $this->createMock(StandardLink::class);
        $link_factory->method('standard')->willReturnCallback(function($title) use ($link_mock) {
            $link = $this->createMock(StandardLink::class);
            $link->method('getLabel')->willReturn($title);
            return $link;
        });

        $builder = $this->getBuilder(1, 2, $domain_service, $gui_service, $wrapper);

        $this->assertEquals('T1 > T2', $builder->getAsString());
    }
    public function testGet(): void
    {
        $domain_service = $this->createMock(InternalDomainService::class);
        $gui_service = $this->createMock(InternalGUIService::class);
        $wrapper = $this->createMock(PresentationWrapperInterface::class);

        $tree = $this->createMock(\ilTree::class);
        $tree->method('getPathId')->willReturn([1]);
        $domain_service->method('repositoryTree')->willReturn($tree);

        $access = $this->createMock(\ilAccessHandler::class);
        $access->method('checkAccess')->willReturn(true);
        $domain_service->method('access')->willReturn($access);

        $wrapper->method('lookupObjId')->willReturn(101);
        $wrapper->method('lookupType')->willReturn('type');
        $wrapper->method('buildTitle')->willReturn('Title');
        $wrapper->method('buildLink')->willReturn('Link');

        $ui_factory = $this->createMock(UIFactory::class);
        $link_factory = $this->createMock(LinkFactory::class);
        $ui_factory->method('link')->willReturn($link_factory);
        $ui_services = $this->createMock(UIServices::class);
        $ui_services->method('factory')->willReturn($ui_factory);
        $gui_service->method('ui')->willReturn($ui_services);

        $link_mock = $this->createMock(StandardLink::class);
        $link_factory->method('standard')->willReturn($link_mock);

        $breadcrumbs_mock = $this->createMock(Breadcrumbs::class);
        $ui_factory->expects($this->once())
            ->method('breadcrumbs')
            ->with($this->callback(function($crumbs) {
                return count($crumbs) === 3; // lead + path + trail
            }))
            ->willReturn($breadcrumbs_mock);

        $builder = $this->getBuilder(1, 1, $domain_service, $gui_service, $wrapper)
            ->withLeadCrumb($link_mock)
            ->withTrailCrumb($link_mock);

        $result = $builder->get();
        $this->assertSame($breadcrumbs_mock, $result);
    }
}
