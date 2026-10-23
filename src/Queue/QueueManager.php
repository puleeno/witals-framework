<?php

declare(strict_types=1);

namespace Witals\Framework\Queue;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionNamedType;
use Throwable;
use Witals\Framework\Queue\Contracts\FailedJobProviderInterface;
use Witals\Framework\Queue\Contracts\QueueInterface;
use Witals\Framework\Queue\Contracts\QueueWorkerInterface;
use Witals\Framework\Queue\Drivers\DatabaseQueue;
use Witals\Framework\Queue\Drivers\NullQueue;
use Witals\Framework\Queue\Drivers\RedisQueue;
use Witals\Framework\Queue\Drivers\SyncQueue;
use Witals\Framework\Queue\FailedJob\DatabaseFailedJobProvider;

/**
 * Queue connection manager.
 *
 * Drivers are resolved through a registry of factories (Strategy + Factory).
 * Applications can register their own drivers without touching the framework:
 *
 *     $manager->extend('sqs', fn (array $config) => new SqsQueue(...));
 *
 * A connection may also point directly at a driver class:
 *
 *     'connections' => [
 *         'sqs' => ['driver' => App\Queue\SqsQueue::class, 'queue' => 'sync'],
 *     ],
 *
 * Driver classes are resolved from the container when bound, otherwise they
 * are instantiated from their config array, a static fromConfig() method, or
 * named constructor arguments.
 */
class QueueManager implements QueueInterface
{
    /**
     * Resolved connection instances, keyed by connection name.
     *
     * @var array<string, QueueInterface>
     */
    protected array $connections = [];

    /**
     * Driver factories, keyed by driver alias.
     *
     * @var array<string, callable(array<string, mixed>, self): QueueInterface>
     */
    protected array $driverFactories = [];

    /**
     * Failed job provider factories, keyed by driver alias.
     *
     * @var array<string, callable(array<string, mixed>, self): ?FailedJobProviderInterface>
     */
    protected array $failedJobProviderFactories = [];

    protected array $config = [];

    protected string $default;

    protected ?LoggerInterface $logger = null;

    protected ?QueueWorkerInterface $worker = null;

    protected ?FailedJobProviderInterface $failedJobProvider = null;

    protected mixed $container = null;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->default = $config['default'] ?? 'sync';

        $this->registerDefaultDrivers();
        $this->registerDefaultFailedJobProviders();
    }

    /**
     * Register (or replace) a driver factory.
     *
     * The factory receives the connection config and this manager, and must
     * return a QueueInterface implementation.
     */
    public function extend(string $driver, callable $factory): static
    {
        $this->driverFactories[$driver] = $factory;

        return $this;
    }

    /**
     * Alias for extend().
     */
    public function registerDriver(string $driver, callable $factory): static
    {
        return $this->extend($driver, $factory);
    }

    /**
     * Remove a registered driver factory. Built-in drivers are also removed
     * from the registry; pass a driver alias to restore it by re-registering.
     */
    public function forgetDriver(string $driver): static
    {
        unset($this->driverFactories[$driver]);

        return $this;
    }

    /**
     * @return array<string, callable>
     */
    public function getDriverFactories(): array
    {
        return $this->driverFactories;
    }

    /**
     * Register (or replace) a failed job provider factory.
     */
    public function extendFailedJobProvider(string $driver, callable $factory): static
    {
        $this->failedJobProviderFactories[$driver] = $factory;

        return $this;
    }

    /**
     * @return array<string, callable>
     */
    public function getFailedJobProviderFactories(): array
    {
        return $this->failedJobProviderFactories;
    }

    /**
     * Provide the application container so driver classes can be resolved
     * with their dependencies.
     */
    public function setContainer(mixed $container): void
    {
        $this->container = $container;
    }

    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setFailedJobProvider(?FailedJobProviderInterface $provider): void
    {
        $this->failedJobProvider = $provider;
    }

    public function getFailedJobProvider(): ?FailedJobProviderInterface
    {
        return $this->failedJobProvider;
    }

    /**
     * Build a failed job provider from a config block.
     *
     * Accepts a driver alias, a driver class, a config array, or null to
     * disable failed job persistence.
     */
    public function createFailedJobProvider(mixed $config): ?FailedJobProviderInterface
    {
        if ($config === null || $config === false) {
            return null;
        }

        if (is_string($config)) {
            $config = ['driver' => $config];
        }

        if (!is_array($config)) {
            throw new InvalidArgumentException('Invalid failed job provider configuration.');
        }

        $driver = $config['driver'] ?? 'database';

        if (isset($config['factory']) && is_callable($config['factory'])) {
            $provider = ($config['factory'])($config, $this);
            if (!$provider instanceof FailedJobProviderInterface) {
                throw new InvalidArgumentException('Failed job provider factory must return FailedJobProviderInterface.');
            }

            return $provider;
        }

        if (isset($this->failedJobProviderFactories[$driver])) {
            return ($this->failedJobProviderFactories[$driver])($config, $this);
        }

        if (!is_string($driver) || !class_exists($driver)) {
            throw new InvalidArgumentException(
                "Failed job provider [{$driver}] is not registered. Register it with QueueManager::extendFailedJobProvider()."
            );
        }

        $provider = $this->instantiate($driver, $config);
        if (!$provider instanceof FailedJobProviderInterface) {
            throw new InvalidArgumentException("Failed job provider [{$driver}] must implement FailedJobProviderInterface.");
        }

        return $provider;
    }

    public function logFailedJob(string $connection, string $queue, object $job, \Throwable $e): void
    {
        if ($this->failedJobProvider === null) {
            return;
        }

        $payload = serialize([
            'displayName' => method_exists($job, 'displayName') ? $job->displayName() : get_class($job),
            'job' => serialize($job),
        ]);

        $this->failedJobProvider->log($connection, $queue, $payload, $e);
    }

    public function connection(?string $name = null): QueueInterface
    {
        $name ??= $this->default;

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->resolve($name);
        }

        return $this->connections[$name];
    }

    /**
     * Forget a resolved connection, e.g. after changing config at runtime.
     */
    public function purge(?string $name = null): static
    {
        if ($name === null) {
            $this->connections = [];
        } else {
            unset($this->connections[$name]);
        }

        return $this;
    }

    protected function resolve(string $name): QueueInterface
    {
        $config = $this->config['connections'][$name] ?? ['driver' => $name];
        $driver = $config['driver'] ?? $name;

        return $this->resolveDriver($driver, $config);
    }

    /**
     * Resolve a driver from an alias, a QueueInterface instance, a callable,
     * or a fully-qualified class name.
     */
    public function resolveDriver(mixed $driver, array $config = []): QueueInterface
    {
        if ($driver instanceof QueueInterface) {
            return $driver;
        }

        if (isset($config['factory']) && is_callable($config['factory'])) {
            $connection = ($config['factory'])($config, $this);
            if (!$connection instanceof QueueInterface) {
                throw new InvalidArgumentException('Queue driver factory must return QueueInterface.');
            }

            return $connection;
        }

        if (is_string($driver) && isset($this->driverFactories[$driver])) {
            $connection = ($this->driverFactories[$driver])($config, $this);
            if (!$connection instanceof QueueInterface) {
                throw new InvalidArgumentException("Queue driver factory [{$driver}] must return QueueInterface.");
            }

            return $connection;
        }

        if (is_callable($driver)) {
            $connection = $driver($config, $this);
            if (!$connection instanceof QueueInterface) {
                throw new InvalidArgumentException('Queue driver callable must return QueueInterface.');
            }

            return $connection;
        }

        if (is_string($driver) && class_exists($driver)) {
            $connection = $this->instantiate($driver, $config);
            if (!$connection instanceof QueueInterface) {
                throw new InvalidArgumentException("Queue driver [{$driver}] must implement QueueInterface.");
            }

            return $connection;
        }

        throw new InvalidArgumentException(
            "Queue driver [{$driver}] is not registered. Register it with QueueManager::extend()."
        );
    }

    /**
     * Built-in drivers are registered through the same registry used by
     * custom drivers, so any of them can be replaced with extend().
     */
    protected function registerDefaultDrivers(): void
    {
        $this->driverFactories = [
            'sync' => static fn (array $config): QueueInterface => new SyncQueue(),
            'null' => static fn (array $config): QueueInterface => new NullQueue(),
            'database' => fn (array $config): QueueInterface => $this->createDatabaseDriver($config),
            'redis' => fn (array $config): QueueInterface => $this->createRedisDriver($config),
        ];
    }

    protected function registerDefaultFailedJobProviders(): void
    {
        $this->failedJobProviderFactories = [
            'database' => static fn (array $config): FailedJobProviderInterface => new DatabaseFailedJobProvider(
                connection: $config['connection'] ?? 'default',
                table: $config['table'] ?? 'failed_jobs',
            ),
            'null' => static fn (array $config): ?FailedJobProviderInterface => null,
        ];
    }

    /**
     * Resolve from the container when bound, then from the driver class.
     */
    protected function instantiate(string $class, array $config): object
    {
        if ($this->container !== null && $this->containerHas($class)) {
            return $this->containerMake($class);
        }

        if (method_exists($class, 'fromConfig')) {
            return $class::fromConfig($config, $this);
        }

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            return $reflection->newInstance();
        }

        $first = $constructor->getParameters()[0];
        $type = $first->getType();

        if ($type instanceof ReflectionNamedType && $type->getName() === 'array') {
            return $reflection->newInstance($config);
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $config)) {
                $arguments[$name] = $config[$name];
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[$name] = $parameter->getDefaultValue();
                continue;
            }

            if ($parameter->allowsNull()) {
                $arguments[$name] = null;
                continue;
            }

            throw new InvalidArgumentException(
                "Queue driver [{$class}] requires config key [{$name}] or a fromConfig() factory."
            );
        }

        return $reflection->newInstance(...$arguments);
    }

    protected function containerHas(string $abstract): bool
    {
        try {
            if (method_exists($this->container, 'bound')) {
                return (bool) $this->container->bound($abstract);
            }

            if (method_exists($this->container, 'has')) {
                return (bool) $this->container->has($abstract);
            }
        } catch (Throwable) {
            return false;
        }

        return false;
    }

    protected function containerMake(string $abstract): object
    {
        if (method_exists($this->container, 'make')) {
            return $this->container->make($abstract);
        }

        return $this->container->get($abstract);
    }

    protected function createDatabaseDriver(array $config): DatabaseQueue
    {
        return new DatabaseQueue(
            connection: $config['connection'] ?? 'default',
            table: $config['table'] ?? 'jobs',
            queue: $config['queue'] ?? 'default',
            retryAfter: $config['retry_after'] ?? 90,
            afterCommit: $config['after_commit'] ?? false,
        );
    }

    protected function createRedisDriver(array $config): RedisQueue
    {
        return new RedisQueue(
            client: $this->resolveRedisClient($config),
            queue: $config['queue'] ?? 'default',
            retryAfter: $config['retry_after'] ?? 90,
            afterCommit: $config['after_commit'] ?? false,
            blockFor: $config['block_for'] ?? 5,
        );
    }

    /**
     * Redis is intentionally not tied to one client library. Provide an
     * existing client, a callable factory, a client class, or let the manager
     * use ext-redis / predis when available.
     */
    protected function resolveRedisClient(array $config): object
    {
        $client = $config['client'] ?? null;

        if (is_object($client)) {
            return $client;
        }

        if (is_string($client) && class_exists($client)) {
            if (is_a($client, \Redis::class, true)) {
                return $this->createExtRedisClient($config, $client);
            }

            $client = new $client($config['client_options'] ?? []);
        } elseif (is_callable($client)) {
            $client = $client($config, $this);
        }

        if (is_object($client)) {
            return $client;
        }

        if (class_exists(\Redis::class)) {
            return $this->createExtRedisClient($config);
        }

        if (class_exists(\Predis\Client::class)) {
            return new \Predis\Client(
                $config['parameters'] ?? $config['client_options'] ?? [],
                $config['options'] ?? [],
            );
        }

        throw new InvalidArgumentException(
            'Redis queue requires a client instance, client factory, ext-redis, or predis/predis.'
        );
    }

    protected function createExtRedisClient(array $config, string $class = \Redis::class): object
    {
        $redis = new $class();
        $host = $config['host'] ?? '127.0.0.1';
        $port = (int) ($config['port'] ?? 6379);
        $timeout = (float) ($config['timeout'] ?? 0.0);
        $persistent = (bool) ($config['persistent'] ?? false);

        $connected = $persistent
            ? $redis->pconnect($host, $port, $timeout)
            : $redis->connect($host, $port, $timeout);

        if (!$connected) {
            throw new \RuntimeException("Unable to connect to Redis at {$host}:{$port}.");
        }

        if (!empty($config['password'])) {
            $redis->auth($config['password']);
        }

        if (isset($config['database'])) {
            $redis->select((int) $config['database']);
        }

        return $redis;
    }

    public function setWorker(QueueWorkerInterface $worker): void
    {
        $this->worker = $worker;
    }

    public function getWorker(): QueueWorkerInterface
    {
        if ($this->worker === null) {
            $this->worker = new Worker($this);
        }

        return $this->worker;
    }

    public function push(object $job, ?string $queue = null): string
    {
        return $this->connection($job->connection ?? null)->push($job, $queue ?? $job->queue ?? null);
    }

    public function pushRaw(string $payload, ?string $queue = null): string
    {
        return $this->connection()->pushRaw($payload, $queue);
    }

    public function later(\DateTimeInterface|\DateInterval|int $delay, object $job, ?string $queue = null): string
    {
        return $this->connection($job->connection ?? null)->later($delay, $job, $queue ?? $job->queue ?? null);
    }

    public function pop(?string $queue = null): ?\Witals\Framework\Queue\Contracts\JobInterface
    {
        return $this->connection()->pop($queue);
    }

    public function bulk(array $jobs, ?string $queue = null): array
    {
        return $this->connection()->bulk($jobs, $queue);
    }

    public function getConnectionName(): string
    {
        return $this->default;
    }

    public function setConnectionName(string $name): void
    {
        $this->default = $name;
    }

    /**
     * The queue name configured for a connection. Used by workers when no
     * explicit --queue option is given, so switching drivers does not require
     * changing the worker command.
     */
    public function getQueueName(?string $connection = null): string
    {
        $connection ??= $this->default;

        return (string) ($this->config['connections'][$connection]['queue'] ?? 'default');
    }
}
