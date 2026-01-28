# Witals Framework: Interceptor System

The Interceptor system provides a powerful way to wrap and extend core framework actions. It follows a pattern similar to Middlewares but is focused on the **Core Execution Layer**, allowing you to hook into any action dispatched through the framework.

## Core Concepts

### 1. CoreInterface
The `CoreInterface` defines the dispatching mechanism. It represents the "target" being called, such as a controller method or a service action.

```php
interface CoreInterface
{
    public function call(string $action, array $parameters = []): mixed;
}
```

### 2. InterceptorInterface
An Interceptor wraps the `Core` and can execute logic before and after the actual action. It can also modify parameters or the final result.

```php
interface InterceptorInterface
{
    public function intercept(string $action, array $parameters, CoreInterface $core): mixed;
}
```

## Implementation

### Creating an Interceptor

To create an interceptor, implement the `InterceptorInterface`. You must call `$core->call($action, $parameters)` to proceed to the next interceptor or the final action.

```php
namespace App\Interceptors;

use Witals\Framework\Contracts\Interceptor\InterceptorInterface;
use Witals\Framework\Contracts\Core\CoreInterface;

class PerformanceInterceptor implements InterceptorInterface
{
    public function intercept(string $action, array $parameters, CoreInterface $core): mixed
    {
        $start = microtime(true);

        // Execute next in chain
        $result = $core->call($action, $parameters);

        $end = microtime(true);
        // Log performance or add metadata to result
        
        return $result;
    }
}
```

### Using InterceptableCore

The `InterceptableCore` manages the chain of interceptors.

```php
use Witals\Framework\Core\Core;
use Witals\Framework\Core\InterceptableCore;

// 1. Base Core (resolves classes from container)
$baseCore = new Core($app);

// 2. Wrap it with Interceptable logic
$core = new InterceptableCore($baseCore);

// 3. Add your interceptors
$core->addInterceptor(new PerformanceInterceptor());
$core->addInterceptor(new LoggingInterceptor());

// 4. Dispatch action
$core->call('UserController@profile', ['id' => 1]);
```

## Use Cases for PrestoWorld

1.  **Logging**: Track every internal action execution time and status.
2.  **Access Control**: Verify user permissions before executing a specific controller action.
3.  **Data Transformation**: Automatically wrap or format results returned by controllers.
4.  **Circuit Breaker**: Prevent execution of failing external services.

## Registration

Usually, the Core is registered as a singleton in a Service Provider. In PrestoWorld, you can extend the `CoreInterface` in the container to add your custom interceptors globally.

```php
$this->app->singleton(CoreInterface::class, function($app) {
    $core = new InterceptableCore(new Core($app));
    // Load interceptors from config
    return $core;
});
```
