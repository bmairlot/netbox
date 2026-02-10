<?php

namespace Ancalagon\Netbox\Tests;

use Ancalagon\Netbox\Device;
use Ancalagon\Netbox\Exception;
use Ancalagon\Netbox\NetboxClient;
use PHPUnit\Framework\TestCase;

class DeviceTest extends TestCase
{
    public function testDeviceCrudLifecycle(): void
    {
        $client = new NetboxClient();

        $manufacturerId = null;
        $deviceTypeId = null;
        $deviceRoleId = null;
        $siteId = null;
        $createdId = null;

        try {
            // Setup: create prerequisites
            $mfRes = $client->post('/dcim/manufacturers/', [
                'name' => 'test-mf-device-phpunit',
                'slug' => 'test-mf-device-phpunit',
            ]);
            $manufacturerId = (string)$mfRes['id'];

            $dtRes = $client->post('/dcim/device-types/', [
                'manufacturer' => (int)$manufacturerId,
                'model' => 'test-dt-device-phpunit',
                'slug' => 'test-dt-device-phpunit',
            ]);
            $deviceTypeId = (string)$dtRes['id'];

            $drRes = $client->post('/dcim/device-roles/', [
                'name' => 'test-dr-device-phpunit',
                'slug' => 'test-dr-device-phpunit',
            ]);
            $deviceRoleId = (string)$drRes['id'];

            $siteRes = $client->post('/dcim/sites/', [
                'name' => 'test-site-device-phpunit',
                'slug' => 'test-site-device-phpunit',
            ]);
            $siteId = (string)$siteRes['id'];

            // 1. Create
            $device = new Device();
            $device->setName('test-device-phpunit')
                ->setDeviceType($deviceTypeId)
                ->setRole($deviceRoleId)
                ->setSite($siteId)
                ->setStatus('active');

            $device->add();
            $createdId = $device->getId();
            $this->assertNotNull($createdId, 'Device should have an id after add()');
            $this->assertEquals('test-device-phpunit', $device->getName());
            $this->assertEquals('active', $device->getStatus());
            $this->assertEquals($deviceTypeId, $device->getDeviceType());
            $this->assertEquals($deviceRoleId, $device->getRole());
            $this->assertEquals($siteId, $device->getSite());

            // 2. Load by id
            $deviceById = new Device();
            $deviceById->setId($createdId);
            $deviceById->load();
            $this->assertEquals($createdId, $deviceById->getId());
            $this->assertEquals('test-device-phpunit', $deviceById->getName());

            // 3. Load by name
            $deviceByName = new Device();
            $deviceByName->setName('test-device-phpunit');
            $deviceByName->load();
            $this->assertEquals($createdId, $deviceByName->getId());

            // 4. Update (PATCH) — change description
            $device->setDescription('patched description');
            $device->update();
            $this->assertEquals('patched description', $device->getDescription());

            // Verify by reloading
            $deviceReload = new Device();
            $deviceReload->setId($createdId);
            $deviceReload->load();
            $this->assertEquals('patched description', $deviceReload->getDescription());

            // 5. Edit (PUT) — full replace
            $device->setName('test-device-phpunit-edited')
                ->setDescription('put description');
            $device->edit();
            $this->assertEquals('test-device-phpunit-edited', $device->getName());
            $this->assertEquals('put description', $device->getDescription());

            // 6. List — filter by name
            $listResult = $device->list(['name' => 'test-device-phpunit-edited']);
            $this->assertGreaterThanOrEqual(1, $listResult['count']);
            $ids = array_column($listResult['results'], 'id');
            $this->assertContains((int)$createdId, $ids);

            // 7. Delete
            $device->delete();
            $this->assertNull($device->getId());
            $createdId = null;

            // 8. Verify deleted
            $deviceDeleted = new Device();
            $deviceDeleted->setName('test-device-phpunit-edited');
            $this->expectException(Exception::class);
            $deviceDeleted->load();
        } finally {
            // Cleanup in reverse order
            if ($createdId !== null) {
                try {
                    $cleanup = new Device();
                    $cleanup->setId($createdId);
                    $cleanup->delete();
                } catch (Exception) {
                }
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
