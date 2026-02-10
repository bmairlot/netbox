<?php

namespace Ancalagon\Netbox\Tests;

use Ancalagon\Netbox\Exception;
use Ancalagon\Netbox\MacAddress;
use PHPUnit\Framework\TestCase;

class MacAddressTest extends TestCase
{
    public function testMacAddressCrudLifecycle(): void
    {
        $mac = new MacAddress();
        $mac->setMacAddress('00:00:5E:00:53:01');

        $createdId = null;

        try {
            // 1. Create
            $mac->add();
            $createdId = $mac->getId();
            $this->assertNotNull($createdId, 'MacAddress should have an id after add()');
            $this->assertNotEmpty($mac->getMacAddress());

            // 2. Load by id
            $macById = new MacAddress();
            $macById->setId($createdId);
            $macById->load();
            $this->assertEquals($createdId, $macById->getId());

            // 3. Load by mac_address
            $macByAddr = new MacAddress();
            $macByAddr->setMacAddress('00:00:5E:00:53:01');
            $macByAddr->load();
            $this->assertEquals($createdId, $macByAddr->getId());

            // 4. Update (PATCH) — change description
            $mac->setDescription('patched description');
            $mac->update();
            $this->assertEquals('patched description', $mac->getDescription());

            // Verify by reloading
            $macReload = new MacAddress();
            $macReload->setId($createdId);
            $macReload->load();
            $this->assertEquals('patched description', $macReload->getDescription());

            // 5. Edit (PUT) — full replace
            $mac->setDescription('put description');
            $mac->edit();
            $this->assertEquals('put description', $mac->getDescription());

            // 6. List — filter by mac_address
            $listResult = $mac->list(['mac_address' => '00:00:5E:00:53:01']);
            $this->assertGreaterThanOrEqual(1, $listResult['count']);
            $ids = array_column($listResult['results'], 'id');
            $this->assertContains((int)$createdId, $ids);

            // 7. Delete
            $mac->delete();
            $this->assertNull($mac->getId());
            $createdId = null;

            // 8. Verify deleted
            $macDeleted = new MacAddress();
            $macDeleted->setMacAddress('00:00:5E:00:53:01');
            $this->expectException(Exception::class);
            $macDeleted->load();
        } finally {
            if ($createdId !== null) {
                try {
                    $cleanup = new MacAddress();
                    $cleanup->setId($createdId);
                    $cleanup->delete();
                } catch (Exception) {
                }
            }
        }
    }
}
