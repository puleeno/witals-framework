<?php

declare(strict_types=1);

namespace Witals\Framework\Console;

use Cycle\Database\DatabaseInterface;
use Throwable;

class DbSeedCommand extends Command
{
    protected string $name = 'db:seed';
    protected string $description = 'Seed database with sample data';

    public function handle(array $args): int
    {
        $this->info("🌱 Seeding Database...\n");

        try {
            $this->app->boot();
            $db = $this->app->make(DatabaseInterface::class);

            // This logic was in the original witals script
            $seeders = [
                'Customers' => function($db) {
                    $count = $db->select()->from('optilarity_customers')->count();
                    if ($count > 0) return "Already has data";
                    $db->insert('optilarity_customers')->values([
                        'first_name' => 'Alice', 'last_name' => 'Wonder', 'email' => 'alice@example.com', 'created_at' => date('Y-m-d H:i:s'), 'status' => 'active'
                    ])->run();
                    return "Inserted sample customer";
                },
                'Products' => function($db) {
                    $count = $db->select()->from('optilarity_software_products')->count();
                    if ($count > 0) return "Already has data";
                    $db->insert('optilarity_software_products')->values([
                        'type' => 'software', 'name' => 'Engine Core', 'slug' => 'engine-core', 'version' => '2.0.0', 'price' => 99, 'status' => 'active', 'created_at' => date('Y-m-d H:i:s')
                    ])->run();
                    return "Inserted sample product";
                },
                'Orders' => function($db) {
                    $count = $db->select()->from('optilarity_orders')->count();
                    if ($count > 0) return "Already has data";
                    $db->insert('optilarity_orders')->values([
                        'order_number' => 'ORD-1001', 'customer_email' => 'alice@example.com', 'total' => 99, 'currency' => 'USD', 'status' => 'completed', 'payment_status' => 'paid', 'created_at' => date('Y-m-d H:i:s')
                    ])->run();
                    return "Inserted sample order";
                },
                'Plans' => function($db) {
                    $count = $db->select()->from('optilarity_membership_plans')->count();
                    if ($count > 0) return "Already has data";
                    $db->insert('optilarity_membership_plans')->values([
                        'name' => 'Pro Plan', 'slug' => 'pro-plan', 'price' => 29, 'currency' => 'USD', 'billing_cycle' => 'monthly', 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s')
                    ])->run();
                    return "Inserted sample plan";
                }
            ];

            foreach ($seeders as $name => $seeder) {
                echo "  Seeding {$name}... ";
                $result = $seeder($db);
                $this->info("✅ {$result}");
            }

            $this->info("\n✨ Seeding complete!");
            return 0;
        } catch (Throwable $e) {
            $this->error("❌ Error during seeding: {$e->getMessage()}");
            return 1;
        }
    }
}
