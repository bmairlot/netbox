<?php

namespace Ancalagon\Netbox\Tests;

use Ancalagon\Netbox\Exception;
use Ancalagon\Netbox\NetboxClient;
use Ancalagon\Netbox\NetworkInterface;
use PHPUnit\Framework\TestCase;

class NetworkInterfaceTest extends TestCase
{
    public function testNetworkInterfaceCrudLifecycle(): void
    {
        $client = new NetboxClient();

        $manufacturerId = null;
        $deviceTypeId = null;
        $deviceRoleId = null;
        $siteId = null;
        $deviceId = null;
        $createdId = null;

        try {
            // Setup: create full device chain
            $mfRes = $client->post('/dcim/manufacturers/', [
                'name' => 'test-mf-nic-phpunit',
                'slug' => 'test-mf-nic-phpunit',
            ]);
            $manufacturerId = (string)$mfRes['id'];

            $dtRes = $client->post('/dcim/device-types/', [
                'manufacturer' => (int)$manufacturerId,
                'model' => 'test-dt-nic-phpunit',
                'slug' => 'test-dt-nic-phpunit',
            ]);
            $deviceTypeId = (string)$dtRes['id'];

            $drRes = $client->post('/dcim/device-roles/', [
                'name' => 'test-dr-nic-phpunit',
                'slug' => 'test-dr-nic-phpunit',
            ]);
            $deviceRoleId = (string)$drRes['id'];

            $siteRes = $client->post('/dcim/sites/', [
                'name' => 'test-site-nic-phpunit',
                'slug' => 'test-site-nic-phpunit',
            ]);
            $siteId = (string)$siteRes['id'];

            $devRes = $client->post('/dcim/devices/', [
                'name' => 'test-dev-nic-phpunit',
                'device_type' => (int)$deviceTypeId,
                'role' => (int)$deviceRoleId,
                'site' => (int)$siteId,
            ]);
            $deviceId = (string)$devRes['id'];

            // 1. Create
            $nic = new NetworkInterface();
            $nic->setDevice($deviceId)
                ->setName('test-nic-phpunit')
                ->setType(['value' => 'virtual', 'label' => 'Virtual']);

            $nic->add();
            $createdId = $nic->getId();
            $this->assertNotNull($createdId, 'NetworkInterface should have an id after add()');
            $this->assertEquals('test-nic-phpunit', $nic->getName());
            $this->assertEquals($deviceId, $nic->getDevice());

            // 2. Load by id
            $nicById = new NetworkInterface();
            $nicById->setId($createdId);
            $nicById->load();
            $this->assertEquals($createdId, $nicById->getId());
            $this->assertEquals('test-nic-phpunit', $nicById->getName());

            // 3. Load by device + name
            $nicByDevName = new NetworkInterface();
            $nicByDevName->setDevice($deviceId)->setName('test-nic-phpunit');
            $nicByDevName->load();
            $this->assertEquals($createdId, $nicByDevName->getId());

            // 4. Update (PATCH) — change description
            $nic->setDescription('patched description');
            $nic->update();
            $this->assertEquals('patched description', $nic->getDescription());

            // Verify by reloading
            $nicReload = new NetworkInterface();
            $nicReload->setId($createdId);
            $nicReload->load();
            $this->assertEquals('patched description', $nicReload->getDescription());

            // 5. Edit (PUT) — full replace
            $nic->setName('test-nic-phpunit-edited')
                ->setDescription('put description');
            $nic->edit();
            $this->assertEquals('test-nic-phpunit-edited', $nic->getName());
            $this->assertEquals('put description', $nic->getDescription());

            // 6. List — filter by device_id
            $listResult = $nic->list(['device_id' => $deviceId]);
            $this->assertGreaterThanOrEqual(1, $listResult['count']);
            $ids = array_column($listResult['results'], 'id');
            $this->assertContains((int)$createdId, $ids);

            // 7. Delete
            $nic->delete();
            $this->assertNull($nic->getId());
            $createdId = null;

            // 8. Verify deleted
            $nicDeleted = new NetworkInterface();
            $nicDeleted->setDevice($deviceId)->setName('test-nic-phpunit-edited');
            $this->expectException(Exception::class);
            $nicDeleted->load();
        } finally {
            // Cleanup in reverse order
            if ($createdId !== null) {
                try {
                    $cleanup = new NetworkInterface();
                    $cleanup->setId($createdId);
                    $cleanup->delete();
                } catch (Exception) {
                }
            }
            if ($deviceId !== null) {
                try { $client->delete("/dcim/devices/{$deviceId}/"); } catch (Exception) {}
            }
            if ($siteId !== null) {
                try { $client->delete("/dcim/sites/{$siteId}/"); } catch (Exception) {}
            }
            if ($deviceRoleId !== null) {
                try { $client->delete("/dcim/device-roles/{$deviceRoleId}/"); } catch (Exception) {}
            }
            if ($deviceTypeId !== null) {
                try { $client->delete("/dcim/device-types/{$deviceTypeId}/"); } catch (Exception) {}
            }
            if ($manufacturerId !== null) {
                try { $client->delete("/dcim/manufacturers/{$manufacturerId}/"); } catch (Exception) {}
            }
        }
    }
}
