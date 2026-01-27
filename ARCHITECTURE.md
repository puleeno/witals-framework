# Architecture Diagram

## Runtime Support Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Application Layer                         │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │              Application (Container)                      │  │
│  │  - RuntimeType $runtime                                   │  │
│  │  - StateManager $stateManager                            │  │
│  │  - LifecycleManager $lifecycle                           │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ uses
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Runtime Detection                           │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                  RuntimeType (Enum)                       │  │
│  │                                                            │  │
│  │  - TRADITIONAL                                            │  │
│  │  - ROADRUNNER                                             │  │
│  │  - REACTPHP                                               │  │
│  │  - SWOOLE                                                 │  │
│  │  - OPENSWOOLE                                             │  │
│  │                                                            │  │
│  │  + detect(): RuntimeType                                  │  │
│  │  + isLongRunning(): bool                                  │  │
│  │  + isAsync(): bool                                        │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ creates
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Lifecycle Management                          │
│                                                                   │
│  ┌──────────────┬──────────────┬──────────────┬──────────────┐ │
│  │ Traditional  │ RoadRunner   │  ReactPHP    │   Swoole     │ │
│  │  Lifecycle   │  Lifecycle   │  Lifecycle   │  Lifecycle   │ │
│  └──────────────┴──────────────┴──────────────┴──────────────┘ │
│  ┌──────────────┐                                               │
│  │ OpenSwoole   │                                               │
│  │  Lifecycle   │                                               │
│  └──────────────┘                                               │
│                                                                   │
│  All implement: LifecycleManager interface                       │
│  - onBoot()                                                      │
│  - onRequestStart()                                              │
│  - onRequestEnd()                                                │
│  - onTerminate()                                                 │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ parallel with
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      State Management                            │
│                                                                   │
│  ┌──────────────────────┬──────────────────────────────────┐   │
│  │  StatelessManager    │    StatefulManager               │   │
│  │  (Traditional)       │    (Long-running runtimes)       │   │
│  │                      │                                   │   │
│  │  - No persistence    │    - Memory persistence          │   │
│  │  - Dies per request  │    - Request isolation           │   │
│  └──────────────────────┴──────────────────────────────────┘   │
│                                                                   │
│  Both implement: StateManager interface                          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ used by
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Server Adapters                             │
│                                                                   │
│  ┌──────────────┬──────────────┬──────────────────────────┐    │
│  │  ReactPHP    │   Swoole     │     OpenSwoole           │    │
│  │   Server     │   Server     │       Server             │    │
│  │              │              │                          │    │
│  │  - Event     │  - Coroutine │     - Coroutine          │    │
│  │    Loop      │  - Workers   │     - Workers            │    │
│  │  - PSR-7     │  - Native    │     - Native             │    │
│  └──────────────┴──────────────┴──────────────────────────┘    │
│                                                                   │
│  All servers:                                                    │
│  - Convert incoming requests to Witals Request                  │
│  - Call $app->handle($request)                                  │
│  - Call $app->afterRequest($request, $response)                 │
│  - Convert Witals Response to runtime-specific format           │
└─────────────────────────────────────────────────────────────────┘
```

## Request Flow

### Traditional Runtime (PHP-FPM)
```
┌──────────┐
│  Client  │
└────┬─────┘
     │ HTTP Request
     ▼
┌──────────────────┐
│   Web Server     │
│ (Apache/Nginx)   │
└────┬─────────────┘
     │ Spawn PHP Process
     ▼
┌──────────────────┐
│   Application    │
│   - Boot         │
│   - Handle       │
│   - Terminate    │
└────┬─────────────┘
     │ Response
     ▼
┌──────────────────┐
│   Process Dies   │
│  (Memory freed)  │
└──────────────────┘
```

### Long-Running Runtime (Swoole/OpenSwoole/ReactPHP/RoadRunner)
```
┌──────────────────┐
│  Server Starts   │
│   - Boot once    │
└────┬─────────────┘
     │
     ▼
┌──────────────────────────────────────┐
│         Worker Loop (Forever)         │
│                                       │
│  ┌────────────────────────────────┐  │
│  │  1. Wait for request           │  │
│  └────────────┬───────────────────┘  │
│               │                       │
│               ▼                       │
│  ┌────────────────────────────────┐  │
│  │  2. runScope {                 │  │
│  │       - Bind Request            │  │
│  │       - Handle request          │  │
│  │       - Auto cleanup            │  │
│  │     }                           │  │
│  └────────────┬───────────────────┘  │
│               │                       │
│               ▼                       │
│  ┌────────────────────────────────┐  │
│  │  3. afterRequest()             │  │
│  │     - Clear state              │  │
│  │     - Garbage collection       │  │
│  └────────────┬───────────────────┘  │
│               │                       │
│               └───────────────────────┤
│                  Loop continues       │
└──────────────────────────────────────┘
```

## Memory Management

### Request Scoping
```
┌─────────────────────────────────────────┐
│         Application Boot                 │
│  (Happens once per worker)               │
│                                          │
│  Global Singletons:                      │
│  ┌────────────────────────────────────┐ │
│  │ - Database Connection Pool         │ │
│  │ - Logger                            │ │
│  │ - Config                            │ │
│  │ - Router                            │ │
│  └────────────────────────────────────┘ │
│                                          │
│  ⚠️  These persist FOREVER              │
└─────────────────────────────────────────┘
                   │
                   │ For each request
                   ▼
┌─────────────────────────────────────────┐
│      runScope() - Request Handling       │
│                                          │
│  Request-Scoped Singletons:              │
│  ┌────────────────────────────────────┐ │
│  │ - Request                           │ │
│  │ - User (from auth)                  │ │
│  │ - CartService                       │ │
│  │ - SessionManager                    │ │
│  └────────────────────────────────────┘ │
│                                          │
│  ✅ These are DESTROYED after request   │
└─────────────────────────────────────────┘
                   │
                   │ After request
                   ▼
┌─────────────────────────────────────────┐
│         Cleanup Phase                    │
│                                          │
│  1. Exit runScope()                      │
│     → Destroy request-scoped instances   │
│                                          │
│  2. afterRequest()                       │
│     → Clear state manager                │
│                                          │
│  3. gc_collect_cycles()                  │
│     → Free unused memory                 │
└─────────────────────────────────────────┘
```

## Runtime Comparison Matrix

```
┌──────────────┬────────────┬────────────┬──────────┬─────────┬─────────────┐
│   Feature    │ Traditional│ RoadRunner │ ReactPHP │ Swoole  │ OpenSwoole  │
├──────────────┼────────────┼────────────┼──────────┼─────────┼─────────────┤
│ Long-running │     ❌     │     ✅     │    ✅    │   ✅    │     ✅      │
├──────────────┼────────────┼────────────┼──────────┼─────────┼─────────────┤
│ Async I/O    │     ❌     │     ❌     │    ✅    │   ✅    │     ✅      │
├──────────────┼────────────┼────────────┼──────────┼─────────┼─────────────┤
│ Coroutines   │     ❌     │     ❌     │    ❌    │   ✅    │     ✅      │
├──────────────┼────────────┼────────────┼──────────┼─────────┼─────────────┤
│ Workers      │     ❌     │     ✅     │    ❌    │   ✅    │     ✅      │
├──────────────┼────────────┼────────────┼──────────┼─────────┼─────────────┤
│ WebSocket    │     ❌     │     ✅     │    ✅    │   ✅    │     ✅      │
├──────────────┼────────────┼────────────┼──────────┼─────────┼─────────────┤
│ gRPC         │     ❌     │     ✅     │    ❌    │   ✅    │     ✅      │
├──────────────┼────────────┼────────────┼──────────┼─────────┼─────────────┤
│ Performance  │     1x     │    10x     │    8x    │   15x   │     15x     │
├──────────────┼────────────┼────────────┼──────────┼─────────┼─────────────┤
│ Memory       │    Low     │   Medium   │  Medium  │  Medium │   Medium    │
├──────────────┼────────────┼────────────┼──────────┼─────────┼─────────────┤
│ Setup        │    Easy    │   Medium   │  Medium  │  Hard   │    Hard     │
└──────────────┴────────────┴────────────┴──────────┴─────────┴─────────────┘
```

## Use Case Recommendations

```
┌─────────────────────────────────────────────────────────────┐
│                    Shared Hosting                            │
│                    Simple Websites                           │
│                    Legacy Applications                       │
│                           ↓                                  │
│                    TRADITIONAL                               │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                    REST APIs                                 │
│                    Production Websites                       │
│                    Medium Traffic                            │
│                           ↓                                  │
│                    ROADRUNNER                                │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                    Real-time Applications                    │
│                    WebSocket Servers                         │
│                    Event-driven Systems                      │
│                           ↓                                  │
│                    REACTPHP                                  │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                    High-performance APIs                     │
│                    Microservices                             │
│                    High Traffic (100k+ req/s)                │
│                    Database-heavy Apps                       │
│                           ↓                                  │
│                SWOOLE / OPENSWOOLE                           │
└─────────────────────────────────────────────────────────────┘
```
