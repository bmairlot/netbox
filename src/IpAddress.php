<?php

namespace Ancalagon\Netbox;

use GuzzleHttp\Exception\GuzzleException;
use mkevenaar\NetBox\Client;

class IpAddress
{
    private ?string $id = 'null';
    private string $address = '';  // required - CIDR notation (e.g., "192.168.1.1/24")
    private ?string $vrf = null;   // VRF id
    private ?string $tenant = null; // Tenant id
    private string $status = 'active'; // active, reserved, deprecated, dhcp, slaac
    private ?string $role = null;  // loopback, secondary, anycast, vip, vrrp, hsrp, glbp, carp
    private ?string $assigned_object_type = null; // e.g., "dcim.interface", "virtualization.vminterface"
    private ?string $assigned_object_id = null;
    private ?string $nat_inside = null; // IpAddress id for NAT inside
    private string $dns_name = '';
    private string $description = '';
    private string $comments = '';
    private array $tags = [];
    private array $custom_fields = [];

    // Read-only fields returned by NetBox
    private ?string $url = null;
    private ?string $display_url = null;
    private ?string $display = null;
    private ?int $family = null;  // 4 or 6
    private $assigned_object = null; // may be array|string|null depending on NetBox response
    private array $nat_outside = []; // array of nested IpAddress refs
    private ?string $created = null;
    private ?string $last_updated = null;

    static private Client $client;

    public function __construct()
    {
        // New object has no ID until created
        $this->setId(null);
        self::$client = new Client();
    }

    /**
     * Create (POST)
     * @throws Exception
     */
    public function add(): void
    {
        if (empty($this->getAddress())) {
            throw new Exception("Missing address for IpAddress");
        }

        try {
            $res = self::$client->getHttpClient()->post("/ipam/ip-addresses/", $this->getAddParamArr());
            $this->loadFromApiResult($res);
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't create the IP Address: {$e->getMessage()}");
        }
    }

    /**
     * Read single (by id or by address + vrf)
     * @throws Exception
     */
    public function load(): void
    {
        try {
            if (!is_null($this->getId())) {
                $res = self::$client->getHttpClient()->get("/ipam/ip-addresses/" . $this->getId() . "/", []);
                $this->loadFromApiResult($res);
                return;
            }

            if (!empty($this->getAddress())) {
                $params = ['address' => $this->getAddress()];
                if (!is_null($this->getVrf())) {
                    $params['vrf_id'] = $this->getVrf();
                }

                $res = self::$client->getHttpClient()->get("/ipam/ip-addresses/", $params);

                if (($res['count'] ?? 0) === 0) {
                    throw new Exception("IpAddress not found for address='{$this->getAddress()}'");
                }
                if (($res['count'] ?? 0) > 1) {
                    throw new Exception("Multiple IpAddress entries found for address='{$this->getAddress()}'. Consider specifying VRF.");
                }
                $this->loadFromApiResult($res['results'][0]);
                return;
            }

            throw new Exception("Can't load IpAddress without 'id' or 'address'");
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't load the IP Address: {$e->getMessage()}");
        }
    }

    /**
     * List with optional filters
     * @param array $filters
     * @return array
     * @throws Exception
     */
    public function list(array $filters = []): array
    {
        try {
            return self::$client->getHttpClient()->get("/ipam/ip-addresses/", $filters);
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't list IP Addresses: {$e->getMessage()}");
        }
    }

    /**
     * Replace (PUT)
     * @throws Exception
     */
    public function edit(): void
    {
        if (is_null($this->getId())) {
            throw new Exception("Can't edit IpAddress without 'id'");
        }
        try {
            $res = self::$client->getHttpClient()->put("/ipam/ip-addresses/" . $this->getId() . "/", $this->getEditParamArr());
            $this->loadFromApiResult($res);
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't edit the IP Address: {$e->getMessage()}");
        }
    }

    /**
     * Partial Update (PATCH)
     * @throws Exception
     */
    public function update(): void
    {
        if (is_null($this->getId())) {
            throw new Exception("Can't update IpAddress without 'id'");
        }
        try {
            $res = self::$client->getHttpClient()->patch("/ipam/ip-addresses/" . $this->getId() . "/", $this->getEditParamArr());
            $this->loadFromApiResult($res);
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't update the IP Address: {$e->getMessage()}");
        }
    }

    /**
     * Delete (DELETE)
     * @throws Exception
     */
    public function delete(): void
    {
        if (is_null($this->getId())) {
            throw new Exception("Can't delete IpAddress without 'id'");
        }
        try {
            self::$client->getHttpClient()->delete("/ipam/ip-addresses/" . $this->getId() . "/", []);
            $this->setId(null);
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't delete the IP Address: {$e->getMessage()}");
        }
    }

    // --- Helper operations ---

    /**
     * List all IPs in a specific VRF
     * @param string $vrfId
     * @return array
     * @throws Exception
     */
    public function listByVrf(string $vrfId): array
    {
        return $this->list(['vrf_id' => $vrfId]);
    }

    /**
     * List all IPs assigned to a specific interface
     * @param string $objectType e.g., "virtualization.vminterface"
     * @param string $objectId
     * @return array
     * @throws Exception
     */
    public function listByAssignedObject(string $objectType, string $objectId): array
    {
        return $this->list([
            'assigned_object_type' => $objectType,
            'assigned_object_id' => $objectId,
        ]);
    }

    /**
     * List all IPs for a VM interface
     * @param string $vmInterfaceId
     * @return array
     * @throws Exception
     */
    public function listByVmInterface(string $vmInterfaceId): array
    {
        return $this->listByAssignedObject('virtualization.vminterface', $vmInterfaceId);
    }

    /**
     * Assign IP to a VM interface and patch
     * @param string $vmInterfaceId
     * @throws Exception
     */
    public function assignToVmInterface(string $vmInterfaceId): void
    {
        $this->setAssignedObjectType('virtualization.vminterface');
        $this->setAssignedObjectId($vmInterfaceId);
        $this->update();
    }

    /**
     * Assign IP to a device interface and patch
     * @param string $interfaceId
     * @throws Exception
     */
    public function assignToInterface(string $interfaceId): void
    {
        $this->setAssignedObjectType('dcim.interface');
        $this->setAssignedObjectId($interfaceId);
        $this->update();
    }

    /**
     * Unassign IP from any object
     * @throws Exception
     */
    public function unassign(): void
    {
        $this->setAssignedObjectType(null);
        $this->setAssignedObjectId(null);
        $this->update();
    }

    // --- Private helpers ---

    private function getAddParamArr(): array
    {
        $params = [
            'address' => $this->getAddress(),
            'status' => $this->getStatus(),
        ];

        if (!is_null($this->getVrf())) { $params['vrf'] = $this->getVrf(); }
        if (!is_null($this->getTenant())) { $params['tenant'] = $this->getTenant(); }
        if (!is_null($this->getRole())) { $params['role'] = $this->getRole(); }
        if (!is_null($this->getAssignedObjectType())) { $params['assigned_object_type'] = $this->getAssignedObjectType(); }
        if (!is_null($this->getAssignedObjectId())) { $params['assigned_object_id'] = $this->getAssignedObjectId(); }
        if (!is_null($this->getNatInside())) { $params['nat_inside'] = $this->getNatInside(); }
        if (!empty($this->getDnsName())) { $params['dns_name'] = $this->getDnsName(); }
        if (!empty($this->getDescription())) { $params['description'] = $this->getDescription(); }
        if (!empty($this->getComments())) { $params['comments'] = $this->getComments(); }
        if (!empty($this->getTags())) { $params['tags'] = $this->getTags(); }
        if (!empty($this->getCustomFields())) { $params['custom_fields'] = $this->getCustomFields(); }

        return $params;
    }

    private function getEditParamArr(): array
    {
        return $this->getAddParamArr();
    }

    private function loadFromApiResult(array $res): void
    {
        $this->setId((string)($res['id'] ?? null));
        $this->setAddress((string)($res['address'] ?? ''));
        $this->setVrf(self::extractId($res['vrf'] ?? null));
        $this->setTenant(self::extractId($res['tenant'] ?? null));

        // Status can be string or object with value
        if (isset($res['status'])) {
            $this->setStatus(is_array($res['status']) ? ($res['status']['value'] ?? 'active') : (string)$res['status']);
        }

        // Role can be string or object with value
        if (isset($res['role'])) {
            $this->setRole(is_array($res['role']) ? ($res['role']['value'] ?? null) : (string)$res['role']);
        }

        $this->setAssignedObjectType($res['assigned_object_type'] ?? null);
        if (isset($res['assigned_object_id'])) {
            $this->setAssignedObjectId((string)$res['assigned_object_id']);
        }
        $this->setNatInside(self::extractId($res['nat_inside'] ?? null));
        $this->setDnsName((string)($res['dns_name'] ?? ''));
        $this->setDescription((string)($res['description'] ?? ''));
        $this->setComments((string)($res['comments'] ?? ''));
        $this->setTags($res['tags'] ?? []);
        $this->setCustomFields($res['custom_fields'] ?? []);

        // Read-only fields
        $this->url = $res['url'] ?? null;
        $this->display_url = $res['display_url'] ?? null;
        $this->display = $res['display'] ?? null;
        $this->created = $res['created'] ?? null;
        $this->last_updated = $res['last_updated'] ?? null;
        $this->assigned_object = $res['assigned_object'] ?? null;
        $this->nat_outside = $res['nat_outside'] ?? [];

        // Family can be int or object with value
        if (isset($res['family'])) {
            $this->family = is_array($res['family']) ? ($res['family']['value'] ?? null) : (int)$res['family'];
        }
    }

    private static function extractId($maybe): ?string
    {
        if (is_null($maybe) || $maybe === '') { return null; }
        if (is_array($maybe)) { return isset($maybe['id']) ? (string)$maybe['id'] : null; }
        return (string)$maybe;
    }

    // --- Getters / Setters ---

    public function getId(): ?string { return $this->id; }
    public function setId(?string $id): IpAddress { $this->id = $id; return $this; }

    public function getAddress(): string { return $this->address; }
    public function setAddress(string $address): IpAddress { $this->address = $address; return $this; }

    public function getVrf(): ?string { return $this->vrf; }
    public function setVrf(?string $vrf): IpAddress { $this->vrf = $vrf; return $this; }

    public function getTenant(): ?string { return $this->tenant; }
    public function setTenant(?string $tenant): IpAddress { $this->tenant = $tenant; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): IpAddress { $this->status = $status; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(?string $role): IpAddress { $this->role = $role; return $this; }

    public function getAssignedObjectType(): ?string { return $this->assigned_object_type; }
    public function setAssignedObjectType(?string $assigned_object_type): IpAddress { $this->assigned_object_type = $assigned_object_type; return $this; }

    public function getAssignedObjectId(): ?string { return $this->assigned_object_id; }
    public function setAssignedObjectId(?string $assigned_object_id): IpAddress { $this->assigned_object_id = $assigned_object_id; return $this; }

    public function getAssignedObject() { return $this->assigned_object; }

    public function getNatInside(): ?string { return $this->nat_inside; }
    public function setNatInside(?string $nat_inside): IpAddress { $this->nat_inside = $nat_inside; return $this; }

    public function getNatOutside(): array { return $this->nat_outside; }

    public function getDnsName(): string { return $this->dns_name; }
    public function setDnsName(string $dns_name): IpAddress { $this->dns_name = $dns_name; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): IpAddress { $this->description = $description; return $this; }

    public function getComments(): string { return $this->comments; }
    public function setComments(string $comments): IpAddress { $this->comments = $comments; return $this; }

    public function getTags(): array { return $this->tags; }
    public function setTags(array $tags): IpAddress { $this->tags = $tags; return $this; }

    public function getCustomFields(): array { return $this->custom_fields; }
    public function setCustomFields(array $custom_fields): IpAddress { $this->custom_fields = $custom_fields; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function getDisplayUrl(): ?string { return $this->display_url; }
    public function getDisplay(): ?string { return $this->display; }
    public function getFamily(): ?int { return $this->family; }
    public function getCreated(): ?string { return $this->created; }
    public function getLastUpdated(): ?string { return $this->last_updated; }
}
