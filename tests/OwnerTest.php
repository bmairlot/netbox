<?php

namespace Ancalagon\Netbox\Tests;

use Ancalagon\Netbox\Exception;
use Ancalagon\Netbox\NetboxClient;
use Ancalagon\Netbox\Owner;
use PHPUnit\Framework\TestCase;

class OwnerTest extends TestCase
{
    public function testOwnerCrudLifecycle(): void
    {
        $client = new NetboxClient();

        $ownerGroupId = null;
        $createdId = null;

        try {
            // Setup: create an OwnerGroup (Owner requires group)
            $ogRes = $client->post('/users/owner-groups/', [
                'name' => 'test-og-owner-phpunit',
            ]);
            $ownerGroupId = (string)$ogRes['id'];

            // 1. Create
            $owner = new Owner();
            $owner->setName('test-owner-phpunit')
                ->setGroup($ownerGroupId);

            $owner->add();
            $createdId = $owner->getId();
            $this->assertNotNull($createdId, 'Owner should have an id after add()');
            $this->assertEquals('test-owner-phpunit', $owner->getName());
            $this->assertEquals($ownerGroupId, $owner->getGroup());

            // 2. Load by id
            $ownerById = new Owner();
            $ownerById->setId($createdId);
            $ownerById->load();
            $this->assertEquals($createdId, $ownerById->getId());
            $this->assertEquals('test-owner-phpunit', $ownerById->getName());

            // 3. Load by name
            $ownerByName = new Owner();
            $ownerByName->setName('test-owner-phpunit');
            $ownerByName->load();
            $this->assertEquals($createdId, $ownerByName->getId());

            // 4. Update (PATCH) — change description
            $owner->setDescription('patched description');
            $owner->update();
            $this->assertEquals('patched description', $owner->getDescription());

            // Verify by reloading
            $ownerReload = new Owner();
            $ownerReload->setId($createdId);
            $ownerReload->load();
            $this->assertEquals('patched description', $ownerReload->getDescription());

            // 5. Edit (PUT) — full replace
            $owner->setName('test-owner-phpunit-edited')
                ->setDescription('put description');
            $owner->edit();
            $this->assertEquals('test-owner-phpunit-edited', $owner->getName());
            $this->assertEquals('put description', $owner->getDescription());

            // 6. List — filter by name
            $listResult = $owner->list(['name' => 'test-owner-phpunit-edited']);
            $this->assertGreaterThanOrEqual(1, $listResult['count']);
            $ids = array_column($listResult['results'], 'id');
            $this->assertContains((int)$createdId, $ids);

            // 7. Delete
            $owner->delete();
            $this->assertNull($owner->getId());
            $createdId = null;

            // 8. Verify deleted
            $ownerDeleted = new Owner();
            $ownerDeleted->setName('test-owner-phpunit-edited');
            $this->expectException(Exception::class);
            $ownerDeleted->load();
        } finally {
            // Cleanup: owner first, then owner group
            if ($createdId !== null) {
                try {
                    $cleanup = new Owner();
                    $cleanup->setId($createdId);
                    $cleanup->delete();
                } catch (Exception) {
                }
            }
            if ($ownerGroupId !== null) {
                try { $client->delete("/users/owner-groups/{$ownerGroupId}/"); } catch (Exception) {}
            }
        }
    }
}
