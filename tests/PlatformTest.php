<?php

namespace Ancalagon\Netbox\Tests;

use Ancalagon\Netbox\Exception;
use Ancalagon\Netbox\NetboxClient;
use Ancalagon\Netbox\Platform;
use PHPUnit\Framework\TestCase;

class PlatformTest extends TestCase
{
    public function testPlatformCrudLifecycle(): void
    {
        $client = new NetboxClient();

        $manufacturerId = null;
        $createdId = null;

        try {
            // Setup: create a Manufacturer (Platform optionally belongs to one)
            $mfRes = $client->post('/dcim/manufacturers/', [
                'name' => 'test-mf-platform-phpunit',
                'slug' => 'test-mf-platform-phpunit',
            ]);
            $manufacturerId = (string)$mfRes['id'];

            // 1. Create
            $platform = new Platform();
            $platform->setName('test-platform-phpunit')
                ->setSlug('test-platform-phpunit')
                ->setManufacturer($manufacturerId);

            $platform->add();
            $createdId = $platform->getId();
            $this->assertNotNull($createdId, 'Platform should have an id after add()');
            $this->assertEquals('test-platform-phpunit', $platform->getName());
            $this->assertEquals('test-platform-phpunit', $platform->getSlug());
            $this->assertEquals($manufacturerId, $platform->getManufacturer());

            // 2. Load by id
            $platformById = new Platform();
            $platformById->setId($createdId);
            $platformById->load();
            $this->assertEquals($createdId, $platformById->getId());
            $this->assertEquals('test-platform-phpunit', $platformById->getName());

            // 3. Load by name
            $platformByName = new Platform();
            $platformByName->setName('test-platform-phpunit');
            $platformByName->load();
            $this->assertEquals($createdId, $platformByName->getId());

            // 4. Load by slug
            $platformBySlug = new Platform();
            $platformBySlug->setSlug('test-platform-phpunit');
            $platformBySlug->load();
            $this->assertEquals($createdId, $platformBySlug->getId());

            // 5. Update (PATCH) — change description
            $platform->setDescription('patched description');
            $platform->update();
            $this->assertEquals('patched description', $platform->getDescription());

            // Verify by reloading
            $platformReload = new Platform();
            $platformReload->setId($createdId);
            $platformReload->load();
            $this->assertEquals('patched description', $platformReload->getDescription());

            // 6. Edit (PUT) — full replace
            $platform->setName('test-platform-phpunit-edited')
                ->setSlug('test-platform-phpunit-edited')
                ->setDescription('put description');
            $platform->edit();
            $this->assertEquals('test-platform-phpunit-edited', $platform->getName());
            $this->assertEquals('test-platform-phpunit-edited', $platform->getSlug());
            $this->assertEquals('put description', $platform->getDescription());

            // 7. List — filter by name
            $listResult = $platform->list(['name' => 'test-platform-phpunit-edited']);
            $this->assertGreaterThanOrEqual(1, $listResult['count']);
            $ids = array_column($listResult['results'], 'id');
            $this->assertContains((int)$createdId, $ids);

            // 8. Delete
            $platform->delete();
            $this->assertNull($platform->getId());
            $createdId = null;

            // 9. Verify deleted
            $platformDeleted = new Platform();
            $platformDeleted->setName('test-platform-phpunit-edited');
            $this->expectException(Exception::class);
            $platformDeleted->load();
        } finally {
            if ($createdId !== null) {
                try {
                    $cleanup = new Platform();
                    $cleanup->setId($createdId);
                    $cleanup->delete();
                } catch (Exception) {
                }
            }
            if ($manufacturerId !== null) {
                try { $client->delete("/dcim/manufacturers/{$manufacturerId}/"); } catch (Exception) {}
            }
        }
    }
}
