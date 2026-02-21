<?php

namespace Ancalagon\Netbox\Tests;

use Ancalagon\Netbox\Exception;
use Ancalagon\Netbox\TenantGroup;
use PHPUnit\Framework\TestCase;

class TenantGroupTest extends TestCase
{
    public function testTenantGroupCrudLifecycle(): void
    {
        $tg = new TenantGroup();
        $tg->setName('test-tg-phpunit')
            ->setSlug('test-tg-phpunit');

        $createdId = null;

        try {
            // 1. Create
            $tg->add();
            $createdId = $tg->getId();
            $this->assertNotNull($createdId, 'TenantGroup should have an id after add()');
            $this->assertEquals('test-tg-phpunit', $tg->getName());
            $this->assertEquals('test-tg-phpunit', $tg->getSlug());

            // 2. Load by id
            $tgById = new TenantGroup();
            $tgById->setId($createdId);
            $tgById->load();
            $this->assertEquals($createdId, $tgById->getId());
            $this->assertEquals('test-tg-phpunit', $tgById->getName());

            // 3. Load by name
            $tgByName = new TenantGroup();
            $tgByName->setName('test-tg-phpunit');
            $tgByName->load();
            $this->assertEquals($createdId, $tgByName->getId());

            // 4. Load by slug
            $tgBySlug = new TenantGroup();
            $tgBySlug->setSlug('test-tg-phpunit');
            $tgBySlug->load();
            $this->assertEquals($createdId, $tgBySlug->getId());

            // 5. Update (PATCH) — change description
            $tg->setDescription('patched description');
            $tg->update();
            $this->assertEquals('patched description', $tg->getDescription());

            // Verify by reloading
            $tgReload = new TenantGroup();
            $tgReload->setId($createdId);
            $tgReload->load();
            $this->assertEquals('patched description', $tgReload->getDescription());

            // 6. Edit (PUT) — full replace
            $tg->setName('test-tg-phpunit-edited')
                ->setSlug('test-tg-phpunit-edited')
                ->setDescription('put description');
            $tg->edit();
            $this->assertEquals('test-tg-phpunit-edited', $tg->getName());
            $this->assertEquals('test-tg-phpunit-edited', $tg->getSlug());
            $this->assertEquals('put description', $tg->getDescription());

            // 7. List — filter by name
            $listResult = $tg->list(['name' => 'test-tg-phpunit-edited']);
            $this->assertGreaterThanOrEqual(1, $listResult['count']);
            $ids = array_column($listResult['results'], 'id');
            $this->assertContains((int)$createdId, $ids);

            // 8. Delete
            $tg->delete();
            $this->assertNull($tg->getId());
            $createdId = null;

            // 9. Verify deleted
            $tgDeleted = new TenantGroup();
            $tgDeleted->setName('test-tg-phpunit-edited');
            $this->expectException(Exception::class);
            $tgDeleted->load();
        } finally {
            if ($createdId !== null) {
                try {
                    $cleanup = new TenantGroup();
                    $cleanup->setId($createdId);
                    $cleanup->delete();
                } catch (Exception) {
                }
            }
        }
    }
}
