<?php

namespace Ancalagon\Netbox\Tests;

use Ancalagon\Netbox\Exception;
use Ancalagon\Netbox\Prefix;
use PHPUnit\Framework\TestCase;

class PrefixTest extends TestCase
{
    public function testPrefixCrudLifecycle(): void
    {
        $prefix = new Prefix();
        $prefix->setPrefix('10.199.0.0/24')
            ->setStatus('active');

        $createdId = null;

        try {
            // 1. Create
            $prefix->add();
            $createdId = $prefix->getId();
            $this->assertNotNull($createdId, 'Prefix should have an id after add()');
            $this->assertEquals('10.199.0.0/24', $prefix->getPrefix());
            $this->assertEquals('active', $prefix->getStatus());

            // 2. Load by id
            $prefixById = new Prefix();
            $prefixById->setId($createdId);
            $prefixById->load();
            $this->assertEquals($createdId, $prefixById->getId());
            $this->assertEquals('10.199.0.0/24', $prefixById->getPrefix());

            // 3. Load by prefix
            $prefixByPrefix = new Prefix();
            $prefixByPrefix->setPrefix('10.199.0.0/24');
            $prefixByPrefix->load();
            $this->assertEquals($createdId, $prefixByPrefix->getId());

            // 4. Update (PATCH) — change description
            $prefix->setDescription('patched description');
            $prefix->update();
            $this->assertEquals('patched description', $prefix->getDescription());

            // Verify by reloading
            $prefixReload = new Prefix();
            $prefixReload->setId($createdId);
            $prefixReload->load();
            $this->assertEquals('patched description', $prefixReload->getDescription());

            // 5. Edit (PUT) — full replace
            $prefix->setDescription('put description');
            $prefix->edit();
            $this->assertEquals('put description', $prefix->getDescription());

            // 6. List — filter by prefix
            $listResult = $prefix->list(['prefix' => '10.199.0.0/24']);
            $this->assertGreaterThanOrEqual(1, $listResult['count']);
            $ids = array_column($listResult['results'], 'id');
            $this->assertContains((int)$createdId, $ids);

            // 7. Delete
            $prefix->delete();
            $this->assertNull($prefix->getId());
            $createdId = null; // prevent double-delete in finally

            // 8. Verify deleted — load by prefix should throw
            $prefixDeleted = new Prefix();
            $prefixDeleted->setPrefix('10.199.0.0/24');
            $this->expectException(Exception::class);
            $prefixDeleted->load();
        } finally {
            // Cleanup: ensure test prefix is deleted even if assertions fail
            if ($createdId !== null) {
                try {
                    $cleanup = new Prefix();
                    $cleanup->setId($createdId);
                    $cleanup->delete();
                } catch (Exception) {
                    // ignore cleanup errors
                }
            }
        }
    }
}
