<?php

namespace Ancalagon\Netbox\Tests;

use Ancalagon\Netbox\Exception;
use Ancalagon\Netbox\OwnerGroup;
use PHPUnit\Framework\TestCase;

class OwnerGroupTest extends TestCase
{
    public function testOwnerGroupCrudLifecycle(): void
    {
        $og = new OwnerGroup();
        $og->setName('test-og-phpunit');

        $createdId = null;

        try {
            // 1. Create
            $og->add();
            $createdId = $og->getId();
            $this->assertNotNull($createdId, 'OwnerGroup should have an id after add()');
            $this->assertEquals('test-og-phpunit', $og->getName());

            // 2. Load by id
            $ogById = new OwnerGroup();
            $ogById->setId($createdId);
            $ogById->load();
            $this->assertEquals($createdId, $ogById->getId());
            $this->assertEquals('test-og-phpunit', $ogById->getName());

            // 3. Load by name
            $ogByName = new OwnerGroup();
            $ogByName->setName('test-og-phpunit');
            $ogByName->load();
            $this->assertEquals($createdId, $ogByName->getId());

            // 4. Update (PATCH) — change description
            $og->setDescription('patched description');
            $og->update();
            $this->assertEquals('patched description', $og->getDescription());

            // Verify by reloading
            $ogReload = new OwnerGroup();
            $ogReload->setId($createdId);
            $ogReload->load();
            $this->assertEquals('patched description', $ogReload->getDescription());

            // 5. Edit (PUT) — full replace
            $og->setName('test-og-phpunit-edited')
                ->setDescription('put description');
            $og->edit();
            $this->assertEquals('test-og-phpunit-edited', $og->getName());
            $this->assertEquals('put description', $og->getDescription());

            // 6. List — filter by name
            $listResult = $og->list(['name' => 'test-og-phpunit-edited']);
            $this->assertGreaterThanOrEqual(1, $listResult['count']);
            $ids = array_column($listResult['results'], 'id');
            $this->assertContains((int)$createdId, $ids);

            // 7. Delete
            $og->delete();
            $this->assertNull($og->getId());
            $createdId = null;

            // 8. Verify deleted
            $ogDeleted = new OwnerGroup();
            $ogDeleted->setName('test-og-phpunit-edited');
            $this->expectException(Exception::class);
            $ogDeleted->load();
        } finally {
            if ($createdId !== null) {
                try {
                    $cleanup = new OwnerGroup();
                    $cleanup->setId($createdId);
                    $cleanup->delete();
                } catch (Exception) {
                }
            }
        }
    }
}
