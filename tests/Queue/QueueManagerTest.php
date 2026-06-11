<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Queue;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Queue\QueueManager;
use Witals\Framework\Queue\Worker;
use Witals\Framework\Queue\Contracts\FailedJobProviderInterface;
use Witals\Framework\Queue\Contracts\QueueInterface;
use Witals\Framework\Queue\Contracts\QueueWorkerInterface;

class QueueManagerTest extends TestCase
{
    public function test_default_connection_is_sync(): void
    {
        $manager = new QueueManager();

        $this->assertSame('sync', $manager->getConnectionName());
    }

    public function test_custom_default_connection(): void
    {
        $manager = new QueueManager(['default' => 'null']);

        $this->assertSame('null', $manager->getConnectionName());
    }

    public function test_set_connection_name(): void
    {
        $manager = new QueueManager();
        $manager->setConnectionName('null');

        $this->assertSame('null', $manager->getConnectionName());
    }

    public function test_connection_returns_sync_driver(): void
    {
        $manager = new QueueManager();

        $conn = $manager->connection('sync');

        $this->assertInstanceOf(QueueInterface::class, $conn);
    }

    public function test_connection_returns_null_driver(): void
    {
        $manager = new QueueManager();

        $conn = $manager->connection('null');

        $this->assertInstanceOf(QueueInterface::class, $conn);
    }

    public function test_connection_caches_driver(): void
    {
        $manager = new QueueManager();

        $conn1 = $manager->connection('sync');
        $conn2 = $manager->connection('sync');

        $this->assertSame($conn1, $conn2);
    }

    public function test_connection_throws_for_unknown_driver(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $manager = new QueueManager();
        $manager->connection('invalid_driver');
    }

    public function test_push_with_connection_config_in_job(): void
    {
        $manager = new QueueManager(['default' => 'null']);

        $job = new \stdClass();
        $job->connection = 'sync';

        $executed = false;
        $job->handle = function () use (&$executed) {
            $executed = true;
        };

        $id = $manager->push($job);

        $this->assertStringStartsWith('sync_', $id);
    }

    public function test_push_raw(): void
    {
        $manager = new QueueManager(['default' => 'sync']);

        $job = new \Witals\Framework\Tests\Queue\TestJob();
        $manager->pushRaw(serialize($job));

        $this->assertStringStartsWith('sync_', $manager->pushRaw(serialize($job)));
    }

    public function test_later(): void
    {
        $manager = new QueueManager(['default' => 'sync']);

        $job = new \Witals\Framework\Tests\Queue\TestJob();
        $manager->later(60, $job);

        $this->assertTrue($job->executed);
    }

    public function test_pop(): void
    {
        $manager = new QueueManager();

        $this->assertNull($manager->pop('default'));
    }

    public function test_bulk(): void
    {
        $manager = new QueueManager(['default' => 'null']);

        $ids = $manager->bulk([new \stdClass(), new \stdClass(), new \stdClass()]);

        $this->assertCount(3, $ids);
    }

    public function test_get_worker_creates_default(): void
    {
        $manager = new QueueManager();

        $worker = $manager->getWorker();

        $this->assertInstanceOf(QueueWorkerInterface::class, $worker);
        $this->assertInstanceOf(Worker::class, $worker);
    }

    public function test_set_worker(): void
    {
        $manager = new QueueManager();
        $worker = $this->createMock(QueueWorkerInterface::class);

        $manager->setWorker($worker);

        $this->assertSame($worker, $manager->getWorker());
    }

    public function test_database_driver_requires_config(): void
    {
        $manager = new QueueManager([
            'connections' => [
                'database' => [
                    'driver' => 'database',
                ],
            ],
        ]);

        $conn = $manager->connection('database');

        $this->assertInstanceOf(QueueInterface::class, $conn);
    }

    public function test_redis_driver_requires_client(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $manager = new QueueManager([
            'connections' => [
                'redis' => [
                    'driver' => 'redis',
                ],
            ],
        ]);

        $manager->connection('redis');
    }

    public function test_set_logger(): void
    {
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $manager = new QueueManager();

        $manager->setLogger($logger);

        $this->addToAssertionCount(1);
    }

    public function test_failed_job_provider_default_null(): void
    {
        $manager = new QueueManager();

        $this->assertNull($manager->getFailedJobProvider());
    }

    public function test_set_failed_job_provider(): void
    {
        $provider = $this->createMock(FailedJobProviderInterface::class);
        $manager = new QueueManager();
        $manager->setFailedJobProvider($provider);

        $this->assertSame($provider, $manager->getFailedJobProvider());
    }

    public function test_log_failed_job_without_provider_does_nothing(): void
    {
        $manager = new QueueManager();
        $job = new \stdClass();

        $manager->logFailedJob('sync', 'default', $job, new \RuntimeException('fail'));

        $this->assertNull($manager->getFailedJobProvider());
    }

    public function test_log_failed_job_with_provider(): void
    {
        $provider = $this->createMock(FailedJobProviderInterface::class);
        $provider->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('sync'),
                $this->equalTo('default'),
                $this->stringContains('stdClass'),
                $this->isInstanceOf(\RuntimeException::class),
            );

        $manager = new QueueManager();
        $manager->setFailedJobProvider($provider);
        $job = new \stdClass();

        $manager->logFailedJob('sync', 'default', $job, new \RuntimeException('fail'));
    }

    public function test_log_failed_job_serializes_payload(): void
    {
        $provider = $this->createMock(FailedJobProviderInterface::class);
        $provider->expects($this->once())
            ->method('log')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function (string $payload) {
                    $data = @unserialize($payload);
                    return is_array($data)
                        && isset($data['displayName'])
                        && isset($data['job']);
                }),
                $this->anything(),
            );

        $manager = new QueueManager();
        $manager->setFailedJobProvider($provider);

        $job = new \stdClass();
        $manager->logFailedJob('sync', 'default', $job, new \RuntimeException('fail'));
    }
}
