<?php

namespace Ancalagon\Netbox\Tests;

use Ancalagon\Netbox\Exception;
use Ancalagon\Netbox\IpAddress;
use PHPUnit\Framework\TestCase;

class IpAddressTest extends TestCase
{
    public function testIpAddressCrudLifecycle(): void
    {
        $ip = new IpAddress();
        $ip->setAddress('192.0.2.1/32')
            ->setStatus('active');

        $createdId = null;

        try {
            // 1. Create
            $ip->add();
            $createdId = $ip->getId();
            $this->assertNotNull($createdId, 'IpAddress should have an id after add()');
            $this->assertStringContainsString('192.0.2.1', $ip->getAddress());
            $this->assertEquals('active', $ip->getStatus());

            // 2. Load by id
            $ipById = new IpAddress();
            $ipById->setId($createdId);
            $ipById->load();
            $this->assertEquals($createdId, $ipById->getId());
            $this->assertStringContainsString('192.0.2.1', $ipById->getAddress());

            // 3. Load by address
            $ipByAddr = new IpAddress();
            $ipByAddr->setAddress('192.0.2.1/32');
            $ipByAddr->load();
            $this->assertEquals($createdId, $ipByAddr->getId());

            // 4. Update (PATCH) — change description
            $ip->setDescription('patched description');
            $ip->update();
            $this->assertEquals('patched description', $ip->getDescription());

            // Verify by reloading
            $ipReload = new IpAddress();
            $ipReload->setId($createdId);
            $ipReload->load();
            $this->assertEquals('patched description', $ipReload->getDescription());

            // 5. Edit (PUT) — full replace
            $ip->setDescription('put description')
                ->setDnsName('test.example.com');
            $ip->edit();
            $this->assertEquals('put description', $ip->getDescription());
            $this->assertEquals('test.example.com', $ip->getDnsName());

            // 6. List — filter by address
            $listResult = $ip->list(['address' => '192.0.2.1/32']);
            $this->assertGreaterThanOrEqual(1, $listResult['count']);
            $ids = array_column($listResult['results'], 'id');
            $this->assertContains((int)$createdId, $ids);

            // 7. Delete
            $ip->delete();
            $this->assertNull($ip->getId());
            $createdId = null;

            // 8. Verify deleted
            $ipDeleted = new IpAddress();
            $ipDeleted->setAddress('192.0.2.1/32');
            $this->expectException(Exception::class);
            $ipDeleted->load();
        } finally {
            if ($createdId !== null) {
                try {
                    $cleanup = new IpAddress();
                    $cleanup->setId($createdId);
                    $cleanup->delete();
                } catch (Exception) {
                }
            }
        }
    }
}
