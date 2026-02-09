<?php

namespace Ancalagon\Netbox\Tests;

use Ancalagon\Netbox\Cluster;
use Ancalagon\Netbox\Exception;
use Ancalagon\Netbox\NetboxClient;
use PHPUnit\Framework\TestCase;

class ClusterTest extends TestCase
{
    public function testClusterCrudLifecycle(): void
    {
        $client = new NetboxClient();

        // Setup: create a cluster type (clusters require a type)
        $clusterTypeId = null;
        $createdId = null;

        try {
            $ctRes = $client->post('/virtualization/cluster-types/', [
                'name' => 'test-ct-cluster-phpunit',
                'slug' => 'test-ct-cluster-phpunit',
            ]);
            $clusterTypeId = (string)$ctRes['id'];

            // 1. Create
            $cluster = new Cluster();
            $cluster->setName('test-cluster-phpunit')
                ->setType($clusterTypeId)
                ->setStatus('active');

            $cluster->add();
            $createdId = $cluster->getId();
            $this->assertNotNull($createdId, 'Cluster should have an id after add()');
            $this->assertEquals('test-cluster-phpunit', $cluster->getName());
            $this->assertEquals('active', $cluster->getStatus());
            $this->assertEquals($clusterTypeId, $cluster->getType());

            // 2. Load by id
            $clusterById = new Cluster();
            $clusterById->setId($createdId);
            $clusterById->load();
            $this->assertEquals($createdId, $clusterById->getId());
            $this->assertEquals('test-cluster-phpunit', $clusterById->getName());
            $this->assertEquals('active', $clusterById->getStatus());

            // 3. Load by name
            $clusterByName = new Cluster();
            $clusterByName->setName('test-cluster-phpunit');
            $clusterByName->load();
            $this->assertEquals($createdId, $clusterByName->getId());

            // 4. Update (PATCH) — change description
            $cluster->setDescription('patched description');
            $cluster->update();
            $this->assertEquals('patched description', $cluster->getDescription());

            // Verify by reloading
            $clusterReload = new Cluster();
            $clusterReload->setId($createdId);
            $clusterReload->load();
            $this->assertEquals('patched description', $clusterReload->getDescription());

            // 5. Edit (PUT) — full replace
            $cluster->setName('test-cluster-phpunit-edited')
                ->setDescription('put description');
            $cluster->edit();
            $this->assertEquals('test-cluster-phpunit-edited', $cluster->getName());
            $this->assertEquals('put description', $cluster->getDescription());

            // 6. List — filter by name
            $listResult = $cluster->list(['name' => 'test-cluster-phpunit-edited']);
            $this->assertGreaterThanOrEqual(1, $listResult['count']);
            $ids = array_column($listResult['results'], 'id');
            $this->assertContains((int)$createdId, $ids);

            // 7. Delete
            $cluster->delete();
            $this->assertNull($cluster->getId());
            $createdId = null; // prevent double-delete in finally

            // 8. Verify deleted — load by name should throw
            $clusterDeleted = new Cluster();
            $clusterDeleted->setName('test-cluster-phpunit-edited');
            $this->expectException(Exception::class);
            $clusterDeleted->load();
        } finally {
            // Cleanup: cluster first, then cluster type
            if ($createdId !== null) {
                try {
                    $cleanup = new Cluster();
                    $cleanup->setId($createdId);
                    $cleanup->delete();
                } catch (Exception) {
                }
            }
            if ($clusterTypeId !== null) {
                try { $client->delete("/virtualization/cluster-types/{$clusterTypeId}/"); } catch (Exception) {}
            }
        }
    }
}
