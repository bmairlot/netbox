<?php

namespace Ancalagon\Netbox\Tests;

use Ancalagon\Netbox\ClusterType;
use Ancalagon\Netbox\Exception;
use PHPUnit\Framework\TestCase;

class ClusterTypeTest extends TestCase
{
    public function testClusterTypeCrudLifecycle(): void
    {
        $ct = new ClusterType();
        $ct->setName('test-ct-phpunit')
            ->setSlug('test-ct-phpunit');

        $createdId = null;

        try {
            // 1. Create
            $ct->add();
            $createdId = $ct->getId();
            $this->assertNotNull($createdId, 'ClusterType should have an id after add()');
            $this->assertEquals('test-ct-phpunit', $ct->getName());
            $this->assertEquals('test-ct-phpunit', $ct->getSlug());

            // 2. Load by id
            $ctById = new ClusterType();
            $ctById->setId($createdId);
            $ctById->load();
            $this->assertEquals($createdId, $ctById->getId());
            $this->assertEquals('test-ct-phpunit', $ctById->getName());
            $this->assertEquals('test-ct-phpunit', $ctById->getSlug());

            // 3. Load by name
            $ctByName = new ClusterType();
            $ctByName->setName('test-ct-phpunit');
            $ctByName->load();
            $this->assertEquals($createdId, $ctByName->getId());

            // 4. Update (PATCH) — change description
            $ct->setDescription('patched description');
            $ct->update();
            $this->assertEquals('patched description', $ct->getDescription());

            // Verify by reloading
            $ctReload = new ClusterType();
            $ctReload->setId($createdId);
            $ctReload->load();
            $this->assertEquals('patched description', $ctReload->getDescription());

            // 5. Edit (PUT) — full replace
            $ct->setName('test-ct-phpunit-edited')
                ->setSlug('test-ct-phpunit-edited')
                ->setDescription('put description');
            $ct->edit();
            $this->assertEquals('test-ct-phpunit-edited', $ct->getName());
            $this->assertEquals('put description', $ct->getDescription());

            // 6. List — filter by name
            $listResult = $ct->list(['name' => 'test-ct-phpunit-edited']);
            $this->assertGreaterThanOrEqual(1, $listResult['count']);
            $ids = array_column($listResult['results'], 'id');
            $this->assertContains((int)$createdId, $ids);

            // 7. Delete
            $ct->delete();
            $this->assertNull($ct->getId());
            $createdId = null; // prevent double-delete in finally

            // 8. Verify deleted — load by name should throw
            $ctDeleted = new ClusterType();
            $ctDeleted->setName('test-ct-phpunit-edited');
            $this->expectException(Exception::class);
            $ctDeleted->load();
        } finally {
            // Cleanup: ensure test ClusterType is deleted even if assertions fail
            if ($createdId !== null) {
                try {
                    $cleanup = new ClusterType();
                    $cleanup->setId($createdId);
                    $cleanup->delete();
                } catch (Exception) {
                    // ignore cleanup errors
                }
            }
        }
    }
}
