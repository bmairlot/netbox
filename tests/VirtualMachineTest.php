<?php

namespace Ancalagon\Netbox\Tests;

use Ancalagon\Netbox\Exception;
use Ancalagon\Netbox\NetboxClient;
use Ancalagon\Netbox\VirtualMachine;
use PHPUnit\Framework\TestCase;

class VirtualMachineTest extends TestCase
{
    public function testVirtualMachineCrudLifecycle(): void
    {
        $client = new NetboxClient();

        // Setup: create a cluster type + cluster (VMs require a site or cluster)
        $clusterTypeId = null;
        $clusterId = null;
        $createdId = null;

        try {
            $ctRes = $client->post('/virtualization/cluster-types/', [
                'name' => 'test-ct-vm-phpunit',
                'slug' => 'test-ct-vm-phpunit',
            ]);
            $clusterTypeId = (string)$ctRes['id'];

            $clRes = $client->post('/virtualization/clusters/', [
                'name' => 'test-cluster-vm-phpunit',
                'type' => (int)$clusterTypeId,
            ]);
            $clusterId = (string)$clRes['id'];

            // 1. Create
            $vm = new VirtualMachine();
            $vm->setName('test-vm-phpunit')
                ->setStatus('planned')
                ->setCluster($clusterId);

            $vm->add();
            $createdId = $vm->getId();
            $this->assertNotNull($createdId, 'VM should have an id after add()');
            $this->assertEquals('test-vm-phpunit', $vm->getName());
            $this->assertEquals('planned', $vm->getStatus());

            // 2. Load by id
            $vmById = new VirtualMachine();
            $vmById->setId($createdId);
            $vmById->load();
            $this->assertEquals($createdId, $vmById->getId());
            $this->assertEquals('test-vm-phpunit', $vmById->getName());
            $this->assertEquals('planned', $vmById->getStatus());

            // 3. Load by name
            $vmByName = new VirtualMachine();
            $vmByName->setName('test-vm-phpunit');
            $vmByName->load();
            $this->assertEquals($createdId, $vmByName->getId());

            // 4. Update (PATCH) — change description
            $vm->setDescription('patched description');
            $vm->update();
            $this->assertEquals('patched description', $vm->getDescription());

            // Verify by reloading
            $vmReload = new VirtualMachine();
            $vmReload->setId($createdId);
            $vmReload->load();
            $this->assertEquals('patched description', $vmReload->getDescription());

            // 5. Edit (PUT) — full replace with new name
            $vm->setName('test-vm-phpunit-edited')
                ->setDescription('put description');
            $vm->edit();
            $this->assertEquals('test-vm-phpunit-edited', $vm->getName());
            $this->assertEquals('put description', $vm->getDescription());

            // 6. List — filter by name
            $listResult = $vm->list(['name' => 'test-vm-phpunit-edited']);
            $this->assertGreaterThanOrEqual(1, $listResult['count']);
            $ids = array_column($listResult['results'], 'id');
            $this->assertContains((int)$createdId, $ids);

            // 7. Delete
            $vm->delete();
            $this->assertNull($vm->getId());
            $createdId = null; // prevent double-delete in finally

            // 8. Verify deleted — load by name should throw
            $vmDeleted = new VirtualMachine();
            $vmDeleted->setName('test-vm-phpunit-edited');
            $this->expectException(Exception::class);
            $vmDeleted->load();
        } finally {
            // Cleanup: VM first, then cluster, then cluster type
            if ($createdId !== null) {
                try {
                    $cleanup = new VirtualMachine();
                    $cleanup->setId($createdId);
                    $cleanup->delete();
                } catch (Exception) {
                }
            }
            if ($clusterId !== null) {
                try { $client->delete("/virtualization/clusters/{$clusterId}/"); } catch (Exception) {}
            }
            if ($clusterTypeId !== null) {
                try { $client->delete("/virtualization/cluster-types/{$clusterTypeId}/"); } catch (Exception) {}
            }
        }
    }
}
