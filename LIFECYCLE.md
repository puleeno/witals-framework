# Lifecycle & IoC Scopes

Witals Framework follows a strict **Dependency Injection First** philosophy, designed to support dual runtimes: **Traditional Web Server** (Apache/Nginx/FPM) and **Long-Running Process** (RoadRunner).

## 1. Application Lifecycle

The framework acts as a bridge between the server environment and your application logic. It handles the complexity of managing state across different runtimes.

### Traditional Runtime (PHP-FPM)
In a traditional setup, the lifecycle is simple:
1. **Boot**: The app is created and containers are configured.
2. **Handle**: The request is processed ~> Response sent.
3. **Terminate**: The process dies, freeing all memory.

### RoadRunner Runtime (Long-Running)
In RoadRunner, the application boots **once** and handles **many requests**. This requires careful management of memory and state.
1. **Boot**: The app boots once. Service Providers are registered. Singletons are created.
2. **Worker Loop**:
    - **Request Start**: `runScope` is initiated.
    - **Handle**: The request is processed.
    - **Request End**: `runScope` finishes. The container **automatically destroys** any singleton created *during* this scope.
    - **After Request**: Garbage collection runs. Persistent state (if any) is kept.

## 2. IoC Container Scopes

To safely manage dependency injection in a long-running process, Witals introduces **IoC Scopes**.

### The `runScope` Mechanism
The core `Container` implements `runScope(array $bindings, callable $callback)`. This method creates a temporary "sandbox" for dependencies.

```php
$app->runScope(
    [Request::class => $request], // Bindings valid ONLY for this scope
    function ($app) {
        // ... build controller ...
        // ... handle request ...
    }
);
```

### Automatic Cleanup (The Safety Net)
Any service resolved as a singleton *inside* `runScope` is treated as **Request Scoped**.
- **Global Singletons**: Services created during boot (e.g., `Database`, `Logger`) persist forever.
- **Scoped Singletons**: Services created during the request (e.g., `CartService`, `AuthUser`) are **automatically destroyed** when the scope ends.

This ensures that User A's `CartService` never leaks to User B, even if the worker stays alive for days.

### Developer Best Practices

#### 1. Always Inject Request
Never access `$_GET` or `$_POST` directly. Always inject `Witals\Framework\Http\Request`.
```php
class UserController {
    public function __construct(protected Request $request) {}
}
```

#### 2. Avoid State in Global Singletons
If you bind a singleton in a `ServiceProvider`'s `boot` method, it will survive forever. **Do not** store user data there.
```php
// BAD: Shared across all users
$this->app->singleton(UserService::class); 

// GOOD: Resolved lazily inside the request scope
// The framework will create it new for each request automatically
// provided you don't resolve it in the boot() phase.
```

#### 3. Use `afterRequest` for External Resources
If your service opens a resource (file handle, socket) that strictly needs closing, you can listen to the `afterRequest` logic or rely on the destructor, as the instance will be destructed at the end of the scope.

---
**Core Principle**: "Everything resolved during a request dies with the request."
