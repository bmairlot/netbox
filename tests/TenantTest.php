<?php

namespace Ancalagon\Netbox\Tests;

use Ancalagon\Netbox\Exception;
use Ancalagon\Netbox\NetboxClient;
use Ancalagon\Netbox\Tenant;
use PHPUnit\Framework\TestCase;

class TenantTest extends TestCase
{
    public function testTenantCrudLifecycle(): void
    {
        $client = new NetboxClient();

        $tenantGroupId = null;
        $createdId = null;

        try {
            // Setup: create a TenantGroup (Tenant optionally belongs to a group)
            $tgRes = $client->post('/tenancy/tenant-groups/', [
                'name' => 'test-tg-tenant-phpunit',
                'slug' => 'test-tg-tenant-phpunit',
            ]);
            $tenantGroupId = (string)$tgRes['id'];

            // 1. Create
            $tenant = new Tenant();
            $tenant->setName('test-tenant-phpunit')
                ->setSlug('test-tenant-phpunit')
                ->setGroup($tenantGroupId);

            $tenant->add();
            $createdId = $tenant->getId();
            $this->assertNotNull($createdId, 'Tenant should have an id after add()');
            $this->assertEquals('test-tenant-phpunit', $tenant->getName());
            $this->assertEquals('test-tenant-phpunit', $tenant->getSlug());
            $this->assertEquals($tenantGroupId, $tenant->getGroup());

            // 2. Load by id
            $tenantById = new Tenant();
            $tenantById->setId($createdId);
            $tenantById->load();
            $this->assertEquals($createdId, $tenantById->getId());
            $this->assertEquals('test-tenant-phpunit', $tenantById->getName());

            // 3. Load by name
            $tenantByName = new Tenant();
            $tenantByName->setName('test-tenant-phpunit');
            $tenantByName->load();
            $this->assertEquals($createdId, $tenantByName->getId());

            // 4. Load by slug
            $tenantBySlug = new Tenant();
            $tenantBySlug->setSlug('test-tenant-phpunit');
            $tenantBySlug->load();
            $this->assertEquals($createdId, $tenantBySlug->getId());

            // 5. Update (PATCH) — change description
            $tenant->setDescription('patched description');
            $tenant->update();
            $this->assertEquals('patched description', $tenant->getDescription());

            // Verify by reloading
            $tenantReload = new Tenant();
            $tenantReload->setId($createdId);
            $tenantReload->load();
            $this->assertEquals('patched description', $tenantReload->getDescription());

            // 6. Edit (PUT) — full replace
            $tenant->setName('test-tenant-phpunit-edited')
                ->setSlug('test-tenant-phpunit-edited')
                ->setDescription('put description');
            $tenant->edit();
            $this->assertEquals('test-tenant-phpunit-edited', $tenant->getName());
            $this->assertEquals('test-tenant-phpunit-edited', $tenant->getSlug());
            $this->assertEquals('put description', $tenant->getDescription());

            // 7. List — filter by name
            $listResult = $tenant->list(['name' => 'test-tenant-phpunit-edited']);
            $this->assertGreaterThanOrEqual(1, $listResult['count']);
            $ids = array_column($listResult['results'], 'id');
            $this->assertContains((int)$createdId, $ids);

            // 8. listByGroup helper
            $groupResult = $tenant->listByGroup($tenantGroupId);
            $this->assertGreaterThanOrEqual(1, $groupResult['count']);

            // 9. Delete
            $tenant->delete();
            $this->assertNull($tenant->getId());
            $createdId = null;

            // 10. Verify deleted
            $tenantDeleted = new Tenant();
            $tenantDeleted->setName('test-tenant-phpunit-edited');
            $this->expectException(Exception::class);
            $tenantDeleted->load();
        } finally {
            // Cleanup: tenant first, then tenant group
            if ($createdId !== null) {
                try {
                    $cleanup = new Tenant();
                    $cleanup->setId($createdId);
                    $cleanup->delete();
                } catch (Exception) {
                }
            }
            if ($tenantGroupId !== null) {
                try { $client->delete("/tenancy/tenant-groups/{$tenantGroupId}/"); } catch (Exception) {}
            }
        }
    }
}
