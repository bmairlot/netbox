<?php

namespace Ancalagon\Netbox\Tests;

use Ancalagon\Netbox\ClusterGroup;
use Ancalagon\Netbox\Exception;
use PHPUnit\Framework\TestCase;

class ClusterGroupTest extends TestCase
{
    public function testClusterGroupCrudLifecycle(): void
    {
        $cg = new ClusterGroup();
        $cg->setName('test-cg-phpunit')
            ->setSlug('test-cg-phpunit');

        $createdId = null;

        try {
            // 1. Create
            $cg->add();
            $createdId = $cg->getId();
            $this->assertNotNull($createdId, 'ClusterGroup should have an id after add()');
            $this->assertEquals('test-cg-phpunit', $cg->getName());
            $this->assertEquals('test-cg-phpunit', $cg->getSlug());

            // 2. Load by id
            $cgById = new ClusterGroup();
            $cgById->setId($createdId);
            $cgById->load();
            $this->assertEquals($createdId, $cgById->getId());
            $this->assertEquals('test-cg-phpunit', $cgById->getName());
            $this->assertEquals('test-cg-phpunit', $cgById->getSlug());

            // 3. Load by name
            $cgByName = new ClusterGroup();
            $cgByName->setName('test-cg-phpunit');
            $cgByName->load();
            $this->assertEquals($createdId, $cgByName->getId());

            // 4. Update (PATCH) — change description
            $cg->setDescription('patched description');
            $cg->update();
            $this->assertEquals('patched description', $cg->getDescription());

            // Verify by reloading
            $cgReload = new ClusterGroup();
            $cgReload->setId($createdId);
            $cgReload->load();
            $this->assertEquals('patched description', $cgReload->getDescription());

            // 5. Edit (PUT) — full replace
            $cg->setName('test-cg-phpunit-edited')
                ->setSlug('test-cg-phpunit-edited')
                ->setDescription('put description');
            $cg->edit();
            $this->assertEquals('test-cg-phpunit-edited', $cg->getName());
            $this->assertEquals('put description', $cg->getDescription());

            // 6. List — filter by name
            $listResult = $cg->list(['name' => 'test-cg-phpunit-edited']);
            $this->assertGreaterThanOrEqual(1, $listResult['count']);
            $ids = array_column($listResult['results'], 'id');
            $this->assertContains((int)$createdId, $ids);

            // 7. Delete
            $cg->delete();
            $this->assertNull($cg->getId());
            $createdId = null; // prevent double-delete in finally

            // 8. Verify deleted — load by name should throw
            $cgDeleted = new ClusterGroup();
            $cgDeleted->setName('test-cg-phpunit-edited');
            $this->expectException(Exception::class);
            $cgDeleted->load();
        } finally {
            // Cleanup: ensure test ClusterGroup is deleted even if assertions fail
            if ($createdId !== null) {
                try {
                    $cleanup = new ClusterGroup();
                    $cleanup->setId($createdId);
                    $cleanup->delete();
                } catch (Exception) {
                    // ignore cleanup errors
                }
            }
        }
    }
}
