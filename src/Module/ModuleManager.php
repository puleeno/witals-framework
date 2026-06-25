<?php

declare(strict_types=1);

namespace Witals\Framework\Module;

use Witals\Framework\Application;
use Psr\Log\LoggerInterface;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Witals\Framework\Module\Contracts\ModuleInterface;
use App\Http\Routing\Contracts\RouteRegistryInterface;

class ModuleManager implements Contracts\ModuleManagerInterface
{
    public function __construct(
        protected Application $app,
        protected ModuleDiscoveryService $discoveryService,
        protected ModuleRouter $router,
        protected ModuleLifecycleManager $lifecycle,
        protected LoggerInterface $logger,
    ) {}

    public function addModulePath(string $path): void
    {
        $this->discoveryService->addModulePath($path);
    }

    public function discover(): void
    {
        $this->discoveryService->discover();
    }

    public function all(): array
    {
        $this->discover();
        return $this->discoveryService->getMetadataMap();
    }

    public function clearDiscoveryCache(): void
    {
        $this->discoveryService->clearDiscoveryCache();
    }

    public function buildRouteIndex(): array
    {
        return $this->router->buildRouteIndex();
    }

    public function matchRoute(string $method, string $path): ?string
    {
        return $this->router->matchRoute($method, $path);
    }

    public function registerModuleRoutes(RouteRegistryInterface $registry): void
    {
        $this->router->registerModuleRoutes($registry);
    }

    public function dispatch(Request $request): ?Response
    {
        return $this->router->dispatch($request);
    }

    public function load(string $name): ?ModuleInterface
    {
        return $this->lifecycle->load($name);
    }

    public function loadSupportModules(): void
    {
        $this->discover();
        $this->lifecycle->loadSupportModules();
    }

    public function loadFunction(string $fullName): ?ModuleInterface
    {
        return $this->lifecycle->loadFunction($fullName);
    }

    public function isLoaded(string $name): bool
    {
        return $this->lifecycle->isLoaded($name);
    }

    public function getLoaded(): array
    {
        return $this->lifecycle->getLoaded();
    }

    public function resetLifecycle(): void
    {
        $this->lifecycle->reset();
    }
}
