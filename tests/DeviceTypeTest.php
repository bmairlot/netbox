<?php

namespace Ancalagon\Netbox\Tests;

use Ancalagon\Netbox\DeviceType;
use Ancalagon\Netbox\Exception;
use Ancalagon\Netbox\NetboxClient;
use PHPUnit\Framework\TestCase;

class DeviceTypeTest extends TestCase
{
    public function testDeviceTypeCrudLifecycle(): void
    {
        $client = new NetboxClient();

        $manufacturerId = null;
        $createdId = null;

        try {
            // Setup: create Manufacturer
            $mfRes = $client->post('/dcim/manufacturers/', [
                'name' => 'test-mf-dt-phpunit',
                'slug' => 'test-mf-dt-phpunit',
            ]);
            $manufacturerId = (string)$mfRes['id'];

            // 1. Create
            $dt = new DeviceType();
            $dt->setManufacturer($manufacturerId)
                ->setModel('test-dt-phpunit')
                ->setSlug('test-dt-phpunit');

            $dt->add();
            $createdId = $dt->getId();
            $this->assertNotNull($createdId, 'DeviceType should have an id after add()');
            $this->assertEquals('test-dt-phpunit', $dt->getModel());
            $this->assertEquals('test-dt-phpunit', $dt->getSlug());
            $this->assertEquals($manufacturerId, $dt->getManufacturer());

            // 2. Load by id
            $dtById = new DeviceType();
            $dtById->setId($createdId);
            $dtById->load();
            $this->assertEquals($createdId, $dtById->getId());
            $this->assertEquals('test-dt-phpunit', $dtById->getModel());

            // 3. Load by model
            $dtByModel = new DeviceType();
            $dtByModel->setModel('test-dt-phpunit');
            $dtByModel->load();
            $this->assertEquals($createdId, $dtByModel->getId());

            // 4. Update (PATCH) — change description
            $dt->setDescription('patched description');
            $dt->update();
            $this->assertEquals('patched description', $dt->getDescription());

            // Verify by reloading
            $dtReload = new DeviceType();
            $dtReload->setId($createdId);
            $dtReload->load();
            $this->assertEquals('patched description', $dtReload->getDescription());

            // 5. Edit (PUT) — full replace
            $dt->setModel('test-dt-phpunit-edited')
                ->setSlug('test-dt-phpunit-edited')
                ->setDescription('put description');
            $dt->edit();
            $this->assertEquals('test-dt-phpunit-edited', $dt->getModel());
            $this->assertEquals('put description', $dt->getDescription());

            // 6. List — filter by model
            $listResult = $dt->list(['model' => 'test-dt-phpunit-edited']);
            $this->assertGreaterThanOrEqual(1, $listResult['count']);
            $ids = array_column($listResult['results'], 'id');
            $this->assertContains((int)$createdId, $ids);

            // 7. Delete
            $dt->delete();
            $this->assertNull($dt->getId());
            $createdId = null;

            // 8. Verify deleted
            $dtDeleted = new DeviceType();
            $dtDeleted->setModel('test-dt-phpunit-edited');
            $this->expectException(Exception::class);
            $dtDeleted->load();
        } finally {
            // Cleanup: device type first, then manufacturer
            if ($createdId !== null) {
                try {
                    $cleanup = new DeviceType();
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
