<?php

namespace RTippin\MessengerFaker\Tests;

use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Models\Messenger as MessengerModel;

class FeatureTestCase extends MessengerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/Fixtures/migrations');

        $this->artisan('migrate', [
            '--database' => 'testbench',
        ])->run();

        $this->storeBaseUsers();
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $config = $app->get('config');

        $config->set('database.default', 'testbench');

        $config->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    private function storeBaseUsers(): void
    {
        $tippin = $this->getModelUser()::create([
            'name' => 'Richard Tippin',
            'email' => 'richard.tippin@gmail.com',
            'password' => 'secret',
        ]);

        $doe = $this->getModelUser()::create([
            'name' => 'John Doe',
            'email' => 'doe@example.net',
            'password' => 'secret',
        ]);

        MessengerModel::create([
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
        ]);

        MessengerModel::create([
            'owner_id' => $doe->getKey(),
            'owner_type' => get_class($doe),
        ]);
    }
}
