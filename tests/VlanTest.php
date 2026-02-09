<?php

namespace Ancalagon\Netbox\Tests;

use Ancalagon\Netbox\Exception;
use Ancalagon\Netbox\Vlan;
use PHPUnit\Framework\TestCase;

class VlanTest extends TestCase
{
    public function testVlanCrudLifecycle(): void
    {
        $vlan = new Vlan();
        $vlan->setVid(3999)
            ->setName('test-vlan-phpunit')
            ->setStatus('active');

        $createdId = null;

        try {
            // 1. Create
            $vlan->add();
            $createdId = $vlan->getId();
            $this->assertNotNull($createdId, 'VLAN should have an id after add()');
            $this->assertEquals(3999, $vlan->getVid());
            $this->assertEquals('test-vlan-phpunit', $vlan->getName());
            $this->assertEquals('active', $vlan->getStatus());

            // 2. Load by id
            $vlanById = new Vlan();
            $vlanById->setId($createdId);
            $vlanById->load();
            $this->assertEquals($createdId, $vlanById->getId());
            $this->assertEquals(3999, $vlanById->getVid());
            $this->assertEquals('test-vlan-phpunit', $vlanById->getName());

            // 3. Load by name
            $vlanByName = new Vlan();
            $vlanByName->setName('test-vlan-phpunit');
            $vlanByName->load();
            $this->assertEquals($createdId, $vlanByName->getId());

            // 4. Update (PATCH) — change description
            $vlan->setDescription('patched description');
            $vlan->update();
            $this->assertEquals('patched description', $vlan->getDescription());

            // Verify by reloading
            $vlanReload = new Vlan();
            $vlanReload->setId($createdId);
            $vlanReload->load();
            $this->assertEquals('patched description', $vlanReload->getDescription());

            // 5. Edit (PUT) — full replace
            $vlan->setName('test-vlan-phpunit-edited')
                ->setDescription('put description');
            $vlan->edit();
            $this->assertEquals('test-vlan-phpunit-edited', $vlan->getName());
            $this->assertEquals('put description', $vlan->getDescription());

            // 6. List — filter by vid
            $listResult = $vlan->list(['vid' => 3999]);
            $this->assertGreaterThanOrEqual(1, $listResult['count']);
            $ids = array_column($listResult['results'], 'id');
            $this->assertContains((int)$createdId, $ids);

            // 7. Delete
            $vlan->delete();
            $this->assertNull($vlan->getId());
            $createdId = null; // prevent double-delete in finally

            // 8. Verify deleted — load by old id should throw
            $vlanDeleted = new Vlan();
            $vlanDeleted->setId($vlan->getId() ?? '99999999');
            // We just deleted it so getId() is null; use a direct id approach
            // Instead, we stored the id before delete — but we set createdId to null.
            // Let's just try loading by the unique name that no longer exists.
            $vlanDeleted->setName('test-vlan-phpunit-edited');
            $this->expectException(Exception::class);
            $vlanDeleted->load();
        } finally {
            // Cleanup: ensure test VLAN is deleted even if assertions fail
            if ($createdId !== null) {
                try {
                    $cleanup = new Vlan();
                    $cleanup->setId($createdId);
                    $cleanup->delete();
                } catch (Exception) {
                    // ignore cleanup errors
                }
            }
        }
    }
}
