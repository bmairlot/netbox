<?php

namespace Ancalagon\Netbox\Tests;

use Ancalagon\Netbox\DeviceRole;
use Ancalagon\Netbox\Exception;
use PHPUnit\Framework\TestCase;

class DeviceRoleTest extends TestCase
{
    public function testDeviceRoleCrudLifecycle(): void
    {
        $dr = new DeviceRole();
        $dr->setName('test-dr-phpunit')
            ->setSlug('test-dr-phpunit');

        $createdId = null;

        try {
            // 1. Create
            $dr->add();
            $createdId = $dr->getId();
            $this->assertNotNull($createdId, 'DeviceRole should have an id after add()');
            $this->assertEquals('test-dr-phpunit', $dr->getName());
            $this->assertEquals('test-dr-phpunit', $dr->getSlug());

            // 2. Load by id
            $drById = new DeviceRole();
            $drById->setId($createdId);
            $drById->load();
            $this->assertEquals($createdId, $drById->getId());
            $this->assertEquals('test-dr-phpunit', $drById->getName());

            // 3. Load by name
            $drByName = new DeviceRole();
            $drByName->setName('test-dr-phpunit');
            $drByName->load();
            $this->assertEquals($createdId, $drByName->getId());

            // 4. Update (PATCH) — change description
            $dr->setDescription('patched description');
            $dr->update();
            $this->assertEquals('patched description', $dr->getDescription());

            // Verify by reloading
            $drReload = new DeviceRole();
            $drReload->setId($createdId);
            $drReload->load();
            $this->assertEquals('patched description', $drReload->getDescription());

            // 5. Edit (PUT) — full replace
            $dr->setName('test-dr-phpunit-edited')
                ->setSlug('test-dr-phpunit-edited')
                ->setDescription('put description');
            $dr->edit();
            $this->assertEquals('test-dr-phpunit-edited', $dr->getName());
            $this->assertEquals('put description', $dr->getDescription());

            // 6. List — filter by name
            $listResult = $dr->list(['name' => 'test-dr-phpunit-edited']);
            $this->assertGreaterThanOrEqual(1, $listResult['count']);
            $ids = array_column($listResult['results'], 'id');
            $this->assertContains((int)$createdId, $ids);

            // 7. Delete
            $dr->delete();
            $this->assertNull($dr->getId());
            $createdId = null;

            // 8. Verify deleted
            $drDeleted = new DeviceRole();
            $drDeleted->setName('test-dr-phpunit-edited');
            $this->expectException(Exception::class);
            $drDeleted->load();
        } finally {
            if ($createdId !== null) {
                try {
                    $cleanup = new DeviceRole();
                    $cleanup->setId($createdId);
                    $cleanup->delete();
                } catch (Exception) {
                }
            }
        }
    }
}
