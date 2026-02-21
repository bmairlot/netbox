<?php

namespace Ancalagon\Netbox\Tests;

use Ancalagon\Netbox\Exception;
use Ancalagon\Netbox\NetboxClient;
use Ancalagon\Netbox\Site;
use PHPUnit\Framework\TestCase;

class SiteTest extends TestCase
{
    public function testSiteCrudLifecycle(): void
    {
        $createdId = null;

        try {
            // 1. Create
            $site = new Site();
            $site->setName('test-site-phpunit')
                ->setSlug('test-site-phpunit')
                ->setStatus('active')
                ->setFacility('DC1')
                ->setPhysicalAddress('123 Test Street')
                ->setLatitude(48.8566)
                ->setLongitude(2.3522);

            $site->add();
            $createdId = $site->getId();
            $this->assertNotNull($createdId, 'Site should have an id after add()');
            $this->assertEquals('test-site-phpunit', $site->getName());
            $this->assertEquals('test-site-phpunit', $site->getSlug());
            $this->assertEquals('active', $site->getStatus());
            $this->assertEquals('DC1', $site->getFacility());
            $this->assertEquals('123 Test Street', $site->getPhysicalAddress());
            $this->assertEqualsWithDelta(48.8566, $site->getLatitude(), 0.001);
            $this->assertEqualsWithDelta(2.3522, $site->getLongitude(), 0.001);

            // 2. Load by id
            $siteById = new Site();
            $siteById->setId($createdId);
            $siteById->load();
            $this->assertEquals($createdId, $siteById->getId());
            $this->assertEquals('test-site-phpunit', $siteById->getName());

            // 3. Load by name
            $siteByName = new Site();
            $siteByName->setName('test-site-phpunit');
            $siteByName->load();
            $this->assertEquals($createdId, $siteByName->getId());

            // 4. Load by slug
            $siteBySlug = new Site();
            $siteBySlug->setSlug('test-site-phpunit');
            $siteBySlug->load();
            $this->assertEquals($createdId, $siteBySlug->getId());

            // 5. Update (PATCH) — change description
            $site->setDescription('patched description');
            $site->update();
            $this->assertEquals('patched description', $site->getDescription());

            // Verify by reloading
            $siteReload = new Site();
            $siteReload->setId($createdId);
            $siteReload->load();
            $this->assertEquals('patched description', $siteReload->getDescription());

            // 6. Edit (PUT) — full replace
            $site->setName('test-site-phpunit-edited')
                ->setSlug('test-site-phpunit-edited')
                ->setDescription('put description')
                ->setShippingAddress('456 Ship Ave');
            $site->edit();
            $this->assertEquals('test-site-phpunit-edited', $site->getName());
            $this->assertEquals('test-site-phpunit-edited', $site->getSlug());
            $this->assertEquals('put description', $site->getDescription());
            $this->assertEquals('456 Ship Ave', $site->getShippingAddress());

            // 7. List — filter by name
            $listResult = $site->list(['name' => 'test-site-phpunit-edited']);
            $this->assertGreaterThanOrEqual(1, $listResult['count']);
            $ids = array_column($listResult['results'], 'id');
            $this->assertContains((int)$createdId, $ids);

            // 8. Delete
            $site->delete();
            $this->assertNull($site->getId());
            $createdId = null;

            // 9. Verify deleted
            $siteDeleted = new Site();
            $siteDeleted->setName('test-site-phpunit-edited');
            $this->expectException(Exception::class);
            $siteDeleted->load();
        } finally {
            if ($createdId !== null) {
                try {
                    $cleanup = new Site();
                    $cleanup->setId($createdId);
                    $cleanup->delete();
                } catch (Exception) {
                }
            }
        }
    }
}
