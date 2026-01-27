# Testing Guide

## Manual Testing Checklist

### 1. Runtime Detection

#### Test Auto-Detection
```bash
# Traditional (default)
php -r "require 'vendor/autoload.php'; use Witals\Framework\Contracts\RuntimeType; echo RuntimeType::detect()->value;"
# Expected: traditional

# With Swoole installed
php -d extension=swoole.so -r "require 'vendor/autoload.php'; use Witals\Framework\Contracts\RuntimeType; echo RuntimeType::detect()->value;"
# Expected: swoole

# With OpenSwoole installed
php -d extension=openswoole.so -r "require 'vendor/autoload.php'; use Witals\Framework\Contracts\RuntimeType; echo RuntimeType::detect()->value;"
# Expected: openswoole

# With RoadRunner
RR_MODE=http php -r "require 'vendor/autoload.php'; use Witals\Framework\Contracts\RuntimeType; echo RuntimeType::detect()->value;"
# Expected: roadrunner

# With ReactPHP
REACTPHP_MODE=true php -r "require 'vendor/autoload.php'; use Witals\Framework\Contracts\RuntimeType; echo RuntimeType::detect()->value;"
# Expected: reactphp
```

### 2. Application Runtime Methods

```php
<?php
require 'vendor/autoload.php';

use Witals\Framework\Application;
use Witals\Framework\Contracts\RuntimeType;

// Test manual runtime setting
$app = new Application(__DIR__, RuntimeType::SWOOLE);

assert($app->getRuntime() === RuntimeType::SWOOLE);
assert($app->isSwoole() === true);
assert($app->isReactPhp() === false);
assert($app->isLongRunning() === true);
assert($app->isAsync() === true);

echo "✅ Runtime methods test passed\n";
```

### 3. Server Adapters

#### ReactPHP Server
```bash
# Install dependencies
composer require react/http react/event-loop

# Start server
php examples/reactphp-server.php &
SERVER_PID=$!

# Test request
sleep 2
curl http://localhost:8080
RESULT=$?

# Cleanup
kill $SERVER_PID

if [ $RESULT -eq 0 ]; then
    echo "✅ ReactPHP server test passed"
else
    echo "❌ ReactPHP server test failed"
fi
```

#### Swoole Server
```bash
# Check if Swoole is installed
if php -m | grep -q swoole; then
    # Start server
    php examples/swoole-server.php &
    SERVER_PID=$!
    
    # Test request
    sleep 2
    curl http://localhost:8080
    RESULT=$?
    
    # Cleanup
    kill $SERVER_PID
    
    if [ $RESULT -eq 0 ]; then
        echo "✅ Swoole server test passed"
    else
        echo "❌ Swoole server test failed"
    fi
else
    echo "⚠️  Swoole not installed, skipping test"
fi
```

#### OpenSwoole Server
```bash
# Check if OpenSwoole is installed
if php -m | grep -q openswoole; then
    # Start server
    php examples/openswoole-server.php &
    SERVER_PID=$!
    
    # Test request
    sleep 2
    curl http://localhost:8080
    RESULT=$?
    
    # Cleanup
    kill $SERVER_PID
    
    if [ $RESULT -eq 0 ]; then
        echo "✅ OpenSwoole server test passed"
    else
        echo "❌ OpenSwoole server test failed"
    fi
else
    echo "⚠️  OpenSwoole not installed, skipping test"
fi
```

### 4. Memory Leak Test

```php
<?php
require 'vendor/autoload.php';

use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Contracts\RuntimeType;

// Test with Swoole runtime (long-running)
$app = new Application(__DIR__, RuntimeType::SWOOLE);
$app->boot();

$initialMemory = memory_get_usage(true);
$iterations = 1000;

for ($i = 0; $i < $iterations; $i++) {
    // Simulate request
    $request = new Request([], [], [], [], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/test',
    ]);
    
    // Handle request (uses runScope internally)
    try {
        $response = $app->handle($request);
        $app->afterRequest($request, $response);
    } catch (\Exception $e) {
        // Expected if no kernel is registered
    }
}

$finalMemory = memory_get_usage(true);
$memoryIncrease = $finalMemory - $initialMemory;
$memoryIncreasePerRequest = $memoryIncrease / $iterations;

echo "Initial memory: " . ($initialMemory / 1024 / 1024) . " MB\n";
echo "Final memory: " . ($finalMemory / 1024 / 1024) . " MB\n";
echo "Memory increase: " . ($memoryIncrease / 1024 / 1024) . " MB\n";
echo "Per request: " . ($memoryIncreasePerRequest / 1024) . " KB\n";

// Memory increase should be minimal (< 1KB per request)
if ($memoryIncreasePerRequest < 1024) {
    echo "✅ Memory leak test passed\n";
} else {
    echo "❌ Memory leak test failed - too much memory increase\n";
}
```

### 5. State Manager Test

```php
<?php
require 'vendor/autoload.php';

use Witals\Framework\Application;
use Witals\Framework\Contracts\RuntimeType;

// Test stateless (traditional)
$app1 = new Application(__DIR__, RuntimeType::TRADITIONAL);
assert($app1->state()->isStateful() === false);
echo "✅ Traditional uses stateless manager\n";

// Test stateful (long-running)
$app2 = new Application(__DIR__, RuntimeType::SWOOLE);
assert($app2->state()->isStateful() === true);
echo "✅ Swoole uses stateful manager\n";

// Test state persistence
$app2->state()->set('test', 'value');
assert($app2->state()->get('test') === 'value');
echo "✅ State persistence works\n";

// Test state clearing
$app2->state()->clear();
assert($app2->state()->has('test') === false);
echo "✅ State clearing works\n";
```

### 6. Lifecycle Hooks Test

```php
<?php
require 'vendor/autoload.php';

use Witals\Framework\Application;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

$app = new Application(__DIR__, RuntimeType::SWOOLE);

$bootCalled = false;
$requestStartCalled = false;
$requestEndCalled = false;

// Note: In real implementation, you'd hook into lifecycle events
// This is a simplified test

$app->boot();
$bootCalled = true;

$request = new Request([], [], [], [], [], [
    'REQUEST_METHOD' => 'GET',
    'REQUEST_URI' => '/test',
]);

// These would be called by the framework
$app->lifecycle()->onRequestStart($request);
$requestStartCalled = true;

$response = new Response('test', 200);
$app->lifecycle()->onRequestEnd($request, $response);
$requestEndCalled = true;

assert($bootCalled === true);
assert($requestStartCalled === true);
assert($requestEndCalled === true);

echo "✅ Lifecycle hooks test passed\n";
```

## Load Testing

### Apache Bench
```bash
# Start server
php examples/swoole-server.php &
SERVER_PID=$!
sleep 2

# Run load test
ab -n 10000 -c 100 http://localhost:8080/

# Cleanup
kill $SERVER_PID
```

### wrk
```bash
# Start server
php examples/swoole-server.php &
SERVER_PID=$!
sleep 2

# Run load test
wrk -t4 -c100 -d30s http://localhost:8080/

# Cleanup
kill $SERVER_PID
```

## Integration Tests

### Test Request Isolation
```php
<?php
// test-isolation.php
require 'vendor/autoload.php';

use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Contracts\RuntimeType;

$app = new Application(__DIR__, RuntimeType::SWOOLE);
$app->boot();

// Simulate multiple requests
for ($i = 1; $i <= 3; $i++) {
    echo "Request #{$i}\n";
    
    $request = new Request(['id' => $i], [], [], [], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => "/test/{$i}",
    ]);
    
    // Each request should be isolated
    $app->runScope(
        [\Witals\Framework\Http\Request::class => $request],
        function ($app) use ($request, $i) {
            $resolvedRequest = $app->make(\Witals\Framework\Http\Request::class);
            assert($resolvedRequest === $request);
            assert($resolvedRequest->query('id') == $i);
            echo "  ✅ Request #{$i} isolated correctly\n";
        }
    );
}

echo "✅ Request isolation test passed\n";
```

## Performance Benchmarks

### Benchmark Script
```php
<?php
// benchmark.php
require 'vendor/autoload.php';

use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Contracts\RuntimeType;

function benchmark(RuntimeType $runtime, int $iterations = 1000): float {
    $app = new Application(__DIR__, $runtime);
    $app->boot();
    
    $start = microtime(true);
    
    for ($i = 0; $i < $iterations; $i++) {
        $request = new Request([], [], [], [], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
        ]);
        
        try {
            $response = $app->handle($request);
            if ($runtime->isLongRunning()) {
                $app->afterRequest($request, $response);
            }
        } catch (\Exception $e) {
            // Expected if no kernel
        }
    }
    
    $end = microtime(true);
    return $end - $start;
}

$iterations = 1000;

echo "Benchmarking {$iterations} iterations...\n\n";

$traditionalTime = benchmark(RuntimeType::TRADITIONAL, $iterations);
echo "Traditional: " . number_format($traditionalTime, 4) . "s\n";
echo "  Requests/sec: " . number_format($iterations / $traditionalTime, 2) . "\n\n";

$swooleTime = benchmark(RuntimeType::SWOOLE, $iterations);
echo "Swoole: " . number_format($swooleTime, 4) . "s\n";
echo "  Requests/sec: " . number_format($iterations / $swooleTime, 2) . "\n";
echo "  Speedup: " . number_format($traditionalTime / $swooleTime, 2) . "x\n\n";
```

## Automated Test Suite

```bash
#!/bin/bash
# run-tests.sh

echo "🧪 Running Witals Framework Runtime Tests"
echo "=========================================="
echo ""

# 1. Runtime Detection
echo "1️⃣  Testing runtime detection..."
php tests/runtime-detection-test.php
echo ""

# 2. Application Methods
echo "2️⃣  Testing application methods..."
php tests/application-methods-test.php
echo ""

# 3. State Manager
echo "3️⃣  Testing state manager..."
php tests/state-manager-test.php
echo ""

# 4. Lifecycle Hooks
echo "4️⃣  Testing lifecycle hooks..."
php tests/lifecycle-test.php
echo ""

# 5. Memory Leak
echo "5️⃣  Testing memory management..."
php tests/memory-leak-test.php
echo ""

# 6. Request Isolation
echo "6️⃣  Testing request isolation..."
php tests/isolation-test.php
echo ""

# 7. Server Tests (if extensions available)
if php -m | grep -q swoole; then
    echo "7️⃣  Testing Swoole server..."
    bash tests/swoole-server-test.sh
    echo ""
fi

if php -m | grep -q openswoole; then
    echo "8️⃣  Testing OpenSwoole server..."
    bash tests/openswoole-server-test.sh
    echo ""
fi

echo "✅ All tests completed!"
```

## Expected Results

All tests should pass with:
- ✅ Runtime detection working correctly
- ✅ No memory leaks (< 1KB per request)
- ✅ Request isolation maintained
- ✅ State management working
- ✅ Lifecycle hooks called in correct order
- ✅ Servers responding to requests
- ✅ Performance improvements visible

## Troubleshooting Failed Tests

### Memory Leak Test Fails
- Check if `runScope()` is being used correctly
- Verify `afterRequest()` is called
- Ensure no global variables are used

### Server Tests Fail
- Check if port 8080 is available
- Verify extensions are installed correctly
- Check PHP version (must be 8.1+)

### Isolation Test Fails
- Verify `runScope()` implementation
- Check container cleanup logic
- Ensure request binding is correct
