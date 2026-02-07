<?php

namespace Ancalagon\Netbox;

use GuzzleHttp\Exception\GuzzleException;
use mkevenaar\NetBox\Client;

class VirtualMachineInterface
{
    private ?string $id = 'null';
    private ?string $virtual_machine = null; // required relationship (VM id)
    private string $name = '';
    private bool $enabled = true;
    private ?string $parent = null;   // self-reference id
    private ?string $bridge = null;   // self-reference id
    private ?int $mtu = null;
    private ?string $primary_mac_address = null; // MacAddress id
    private string $description = '';
    private string $mode = 'access'; // access, tagged, etc.
    private ?string $untagged_vlan = null; // Vlan id
    private array $tagged_vlans = []; // array of Vlan ids
    private ?string $qinq_svlan = null; // Vlan id
    private ?string $vlan_translation_policy = null; // policy id
    private ?string $vrf = null; // VRF id
    private array $tags = [];
    private array $custom_fields = [];

    // Additional common fields returned by NetBox (kept for consistency with MacAddress)
    private ?string $url = null;
    private ?string $display = null;
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
        if (empty($this->getVirtualMachine())) {
            throw new Exception("Missing Virtual Machine (interface requires parent VM)");
        }
        if (empty($this->getName())) {
            throw new Exception("Missing name for VirtualMachineInterface");
        }

        try {
            $res = self::$client->getHttpClient()->post("/virtualization/interfaces/", $this->getAddParamArr());
            $this->loadFromApiResult($res);
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't create the Virtual Machine Interface: {$e->getMessage()}");
        }
    }

    /**
     * Read single (by id or by VM + name)
     * @throws Exception
     */
    public function load(): void
    {
        try {
            if (!is_null($this->getId())) {
                $res = self::$client->getHttpClient()->get("/virtualization/interfaces/" . $this->getId() . "/", []);
                $this->loadFromApiResult($res);
                return;
            }

            if (!empty($this->getVirtualMachine()) && !empty($this->getName())) {
                // NetBox API: 'virtual_machine' expects name/slug, 'virtual_machine_id' expects numeric ID
                $vmFilterKey = is_numeric($this->getVirtualMachine()) ? 'virtual_machine_id' : 'virtual_machine';
                $res = self::$client->getHttpClient()->get("/virtualization/interfaces/", [
                    $vmFilterKey => $this->getVirtualMachine(),
                    'name' => $this->getName(),
                ]);

                if (($res['count'] ?? 0) === 0) {
                    throw new Exception("VirtualMachineInterface not found for vm='{$this->getVirtualMachine()}', name='{$this->getName()}'");
                }
                if (($res['count'] ?? 0) > 1) {
                    throw new Exception("Multiple VirtualMachineInterface entries found for vm='{$this->getVirtualMachine()}', name='{$this->getName()}'");
                }
                $this->loadFromApiResult($res['results'][0]);
                return;
            }

            throw new Exception("Can't load VirtualMachineInterface without 'id' or ('virtual_machine' and 'name')");
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't load the Virtual Machine Interface: {$e->getMessage()}");
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
            return self::$client->getHttpClient()->get("/virtualization/interfaces/", $filters);
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't list Virtual Machine Interfaces: {$e->getMessage()}");
        }
    }

    /**
     * Replace (PUT)
     * @throws Exception
     */
    public function edit(): void
    {
        if (is_null($this->getId())) {
            throw new Exception("Can't edit VirtualMachineInterface without 'id'");
        }
        try {
            $res = self::$client->getHttpClient()->put("/virtualization/interfaces/" . $this->getId() . "/", $this->getEditParamArr());
            $this->loadFromApiResult($res);
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't edit the Virtual Machine Interface: {$e->getMessage()}");
        }
    }

    /**
     * Partial Update (PATCH)
     * @throws Exception
     */
    public function update(): void
    {
        if (is_null($this->getId())) {
            throw new Exception("Can't update VirtualMachineInterface without 'id'");
        }
        try {
            $res = self::$client->getHttpClient()->patch("/virtualization/interfaces/" . $this->getId() . "/", $this->getEditParamArr());
            $this->loadFromApiResult($res);
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't update the Virtual Machine Interface: {$e->getMessage()}");
        }
    }

    /**
     * Delete (DELETE)
     * @throws Exception
     */
    public function delete(): void
    {
        if (is_null($this->getId())) {
            throw new Exception("Can't delete VirtualMachineInterface without 'id'");
        }
        try {
            self::$client->getHttpClient()->delete("/virtualization/interfaces/" . $this->getId() . "/", []);
            $this->setId(null);
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't delete the Virtual Machine Interface: {$e->getMessage()}");
        }
    }

    // Helper operations

    /**
     * List all interfaces for a VM
     * @param string $vmId
     * @return array
     * @throws Exception
     */
    public function listByVm(string $vmId): array
    {
        // NetBox API: 'virtual_machine' expects name/slug, 'virtual_machine_id' expects numeric ID
        $filterKey = is_numeric($vmId) ? 'virtual_machine_id' : 'virtual_machine';
        return $this->list([$filterKey => $vmId]);
    }

    /**
     * Assign or update primary MAC by MacAddress id
     * @param string $macId
     * @throws Exception
     */
    public function setPrimaryMacById(string $macId): void
    {
        $this->setPrimaryMacAddress($macId);
        $this->update();
    }

    /**
     * Assign untagged VLAN by id and patch
     * @param string $vlanId
     * @throws Exception
     */
    public function assignUntaggedVlan(string $vlanId): void
    {
        $this->setUntaggedVlan($vlanId);
        $this->update();
    }

    /**
     * Replace tagged VLANs set and patch
     * @param array $vlanIds
     * @throws Exception
     */
    public function updateTaggedVlans(array $vlanIds): void
    {
        $this->tagged_vlans = array_values(array_unique(array_map('strval', $vlanIds)));
        $this->update();
    }

    /**
     * Add a single tagged VLAN id and patch
     * @param string $vlanId
     * @throws Exception
     */
    public function addTaggedVlan(string $vlanId): void
    {
        $current = $this->getTaggedVlans();
        $current[] = (string)$vlanId;
        $this->updateTaggedVlans($current);
    }

    /**
     * Remove a single tagged VLAN id and patch
     * @param string $vlanId
     * @throws Exception
     */
    public function removeTaggedVlan(string $vlanId): void
    {
        $this->updateTaggedVlans(array_values(array_filter($this->getTaggedVlans(), function ($v) use ($vlanId) {
            return (string)$v !== (string)$vlanId;
        })));
    }

    private function getAddParamArr(): array
    {
        $params = [
            'virtual_machine' => $this->getVirtualMachine(),
            'name' => $this->getName(),
            'enabled' => $this->isEnabled(),
        ];

        if (!is_null($this->getParent())) { $params['parent'] = $this->getParent(); }
        if (!is_null($this->getBridge())) { $params['bridge'] = $this->getBridge(); }
        if (!is_null($this->getMtu())) { $params['mtu'] = $this->getMtu(); }
        if (!empty($this->getDescription())) { $params['description'] = $this->getDescription(); }
        if (!empty($this->getMode())) { $params['mode'] = $this->getMode(); }
        if (!is_null($this->getUntaggedVlan())) { $params['untagged_vlan'] = $this->getUntaggedVlan(); }
        if (!empty($this->getTaggedVlans())) { $params['tagged_vlans'] = $this->getTaggedVlans(); }
        if (!is_null($this->getQinqSvlan())) { $params['qinq_svlan'] = $this->getQinqSvlan(); }
        if (!is_null($this->getVlanTranslationPolicy())) { $params['vlan_translation_policy'] = $this->getVlanTranslationPolicy(); }
        if (!is_null($this->getVrf())) { $params['vrf'] = $this->getVrf(); }
        if (!empty($this->getTags())) { $params['tags'] = $this->getTags(); }
        if (!empty($this->getCustomFields())) { $params['custom_fields'] = $this->getCustomFields(); }
        if (!is_null($this->getPrimaryMacAddress())) { $params['primary_mac_address'] = $this->getPrimaryMacAddress(); }

        return $params;
    }

    private function getEditParamArr(): array
    {
        // For simplicity, reuse add param builder which respects optional fields
        return $this->getAddParamArr();
    }

    private function loadFromApiResult(array $res): void
    {
        // Scalars and simple relationships may be returned as objects; accept both id or object with id
        $this->setId((string)($res['id'] ?? null));
        $this->setVirtualMachine(self::extractId($res['virtual_machine'] ?? null));
        $this->setName((string)($res['name'] ?? ''));
        $this->setEnabled((bool)($res['enabled'] ?? true));
        $this->setParent(self::extractId($res['parent'] ?? null));
        $this->setBridge(self::extractId($res['bridge'] ?? null));
        $this->setMtu(isset($res['mtu']) ? (int)$res['mtu'] : null);
        $this->setPrimaryMacAddress(self::extractId($res['primary_mac_address'] ?? null));
        $this->setDescription((string)($res['description'] ?? ''));
        // Mode can be string or object with value/label
        if (isset($res['mode'])) {
            $this->setMode(is_array($res['mode']) ? ($res['mode']['value'] ?? 'access') : (string)$res['mode']);
        }
        $this->setUntaggedVlan(self::extractId($res['untagged_vlan'] ?? null));
        $tagged = $res['tagged_vlans'] ?? [];
        if (is_array($tagged)) {
            $this->tagged_vlans = array_map(function ($v) { return (string)self::extractId($v); }, $tagged);
        } else {
            $this->tagged_vlans = [];
        }
        $this->setQinqSvlan(self::extractId($res['qinq_svlan'] ?? null));
        $this->setVlanTranslationPolicy(self::extractId($res['vlan_translation_policy'] ?? null));
        $this->setVrf(self::extractId($res['vrf'] ?? null));
        $this->setTags($res['tags'] ?? []);
        $this->setCustomFields($res['custom_fields'] ?? []);

        $this->url = $res['url'] ?? null;
        $this->display = $res['display'] ?? null;
        $this->created = $res['created'] ?? null;
        $this->last_updated = $res['last_updated'] ?? null;
    }

    private static function extractId($maybe): ?string
    {
        if (is_null($maybe) || $maybe === '') { return null; }
        if (is_array($maybe)) { return isset($maybe['id']) ? (string)$maybe['id'] : null; }
        return (string)$maybe;
    }

    public function getId(): ?string { return $this->id; }
    public function setId(?string $id): VirtualMachineInterface { $this->id = $id; return $this; }

    public function getVirtualMachine(): ?string { return $this->virtual_machine; }
    public function setVirtualMachine(?string $virtual_machine): VirtualMachineInterface { $this->virtual_machine = $virtual_machine; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): VirtualMachineInterface { $this->name = $name; return $this; }

    public function isEnabled(): bool { return $this->enabled; }
    public function setEnabled(bool $enabled): VirtualMachineInterface { $this->enabled = $enabled; return $this; }

    public function getParent(): ?string { return $this->parent; }
    public function setParent(?string $parent): VirtualMachineInterface { $this->parent = $parent; return $this; }

    public function getBridge(): ?string { return $this->bridge; }
    public function setBridge(?string $bridge): VirtualMachineInterface { $this->bridge = $bridge; return $this; }

    public function getMtu(): ?int { return $this->mtu; }
    public function setMtu(?int $mtu): VirtualMachineInterface { $this->mtu = $mtu; return $this; }

    public function getPrimaryMacAddress(): ?string { return $this->primary_mac_address; }
    public function setPrimaryMacAddress(?string $primary_mac_address): VirtualMachineInterface { $this->primary_mac_address = $primary_mac_address; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): VirtualMachineInterface { $this->description = $description; return $this; }

    public function getMode(): string { return $this->mode; }
    public function setMode(string $mode): VirtualMachineInterface { $this->mode = $mode; return $this; }

    public function getUntaggedVlan(): ?string { return $this->untagged_vlan; }
    public function setUntaggedVlan(?string $untagged_vlan): VirtualMachineInterface { $this->untagged_vlan = $untagged_vlan; return $this; }

    public function getTaggedVlans(): array { return $this->tagged_vlans; }
    public function setTaggedVlans(array $tagged_vlans): VirtualMachineInterface { $this->tagged_vlans = array_values(array_map('strval', $tagged_vlans)); return $this; }

    public function getQinqSvlan(): ?string { return $this->qinq_svlan; }
    public function setQinqSvlan(?string $qinq_svlan): VirtualMachineInterface { $this->qinq_svlan = $qinq_svlan; return $this; }

    public function getVlanTranslationPolicy(): ?string { return $this->vlan_translation_policy; }
    public function setVlanTranslationPolicy(?string $vlan_translation_policy): VirtualMachineInterface { $this->vlan_translation_policy = $vlan_translation_policy; return $this; }

    public function getVrf(): ?string { return $this->vrf; }
    public function setVrf(?string $vrf): VirtualMachineInterface { $this->vrf = $vrf; return $this; }

    public function getTags(): array { return $this->tags; }
    public function setTags(array $tags): VirtualMachineInterface { $this->tags = $tags; return $this; }

    public function getCustomFields(): array { return $this->custom_fields; }
    public function setCustomFields(array $custom_fields): VirtualMachineInterface { $this->custom_fields = $custom_fields; return $this; }
}