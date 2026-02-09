<?php

namespace Ancalagon\Netbox\Tests;

use Ancalagon\Netbox\Exception;
use Ancalagon\Netbox\NetboxClient;
use Ancalagon\Netbox\VirtualMachine;
use Ancalagon\Netbox\VirtualMachineInterface;
use PHPUnit\Framework\TestCase;

class VirtualMachineInterfaceTest extends TestCase
{
    public function testVirtualMachineInterfaceCrudLifecycle(): void
    {
        $client = new NetboxClient();

        $clusterTypeId = null;
        $clusterId = null;
        $vmId = null;
        $ifaceId = null;

        try {
            // Setup: create cluster type + cluster + parent VM
            $ctRes = $client->post('/virtualization/cluster-types/', [
                'name' => 'test-ct-iface-phpunit',
                'slug' => 'test-ct-iface-phpunit',
            ]);
            $clusterTypeId = (string)$ctRes['id'];

            $clRes = $client->post('/virtualization/clusters/', [
                'name' => 'test-cluster-iface-phpunit',
                'type' => (int)$clusterTypeId,
            ]);
            $clusterId = (string)$clRes['id'];

            $vm = new VirtualMachine();
            $vm->setName('test-vm-iface-phpunit')
                ->setStatus('planned')
                ->setCluster($clusterId);
            $vm->add();
            $vmId = $vm->getId();
            $this->assertNotNull($vmId, 'Parent VM should have an id after add()');

            // 1. Create interface
            $iface = new VirtualMachineInterface();
            $iface->setVirtualMachine($vmId)
                ->setName('eth0');

            $iface->add();
            $ifaceId = $iface->getId();
            $this->assertNotNull($ifaceId, 'Interface should have an id after add()');
            $this->assertEquals('eth0', $iface->getName());
            $this->assertEquals($vmId, $iface->getVirtualMachine());

            // 2. Load by id
            $ifaceById = new VirtualMachineInterface();
            $ifaceById->setId($ifaceId);
            $ifaceById->load();
            $this->assertEquals($ifaceId, $ifaceById->getId());
            $this->assertEquals('eth0', $ifaceById->getName());
            $this->assertEquals($vmId, $ifaceById->getVirtualMachine());

            // 3. Load by VM + name
            $ifaceByVmName = new VirtualMachineInterface();
            $ifaceByVmName->setVirtualMachine($vmId)
                ->setName('eth0');
            $ifaceByVmName->load();
            $this->assertEquals($ifaceId, $ifaceByVmName->getId());

            // 4. Update (PATCH) — change description
            $iface->setDescription('patched description');
            $iface->update();
            $this->assertEquals('patched description', $iface->getDescription());

            // Verify by reloading
            $ifaceReload = new VirtualMachineInterface();
            $ifaceReload->setId($ifaceId);
            $ifaceReload->load();
            $this->assertEquals('patched description', $ifaceReload->getDescription());

            // 5. Edit (PUT) — full replace
            $iface->setName('eth0-edited')
                ->setDescription('put description');
            $iface->edit();
            $this->assertEquals('eth0-edited', $iface->getName());
            $this->assertEquals('put description', $iface->getDescription());

            // 6. List — filter by virtual_machine_id
            $listResult = $iface->list(['virtual_machine_id' => $vmId]);
            $this->assertGreaterThanOrEqual(1, $listResult['count']);
            $ids = array_column($listResult['results'], 'id');
            $this->assertContains((int)$ifaceId, $ids);

            // 7. Delete interface
            $iface->delete();
            $this->assertNull($iface->getId());
            $ifaceId = null; // prevent double-delete in finally

            // 8. Verify deleted — load by VM + name should throw
            $ifaceDeleted = new VirtualMachineInterface();
            $ifaceDeleted->setVirtualMachine($vmId)
                ->setName('eth0-edited');
            $this->expectException(Exception::class);
            $ifaceDeleted->load();
        } finally {
            // Cleanup: interface, then VM, then cluster, then cluster type
            if ($ifaceId !== null) {
                try {
                    $cleanupIface = new VirtualMachineInterface();
                    $cleanupIface->setId($ifaceId);
                    $cleanupIface->delete();
                } catch (Exception) {
                }
            }
            if ($vmId !== null) {
                try {
                    $cleanupVm = new VirtualMachine();
                    $cleanupVm->setId($vmId);
                    $cleanupVm->delete();
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
