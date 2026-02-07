<?php

namespace Ancalagon\Netbox;

use GuzzleHttp\Exception\GuzzleException;
use mkevenaar\NetBox\Client;

class NetworkInterface
{
    // Core identity
    private ?string $id = null;

    // Required relationships/fields for NetBox DCIM Interface
    private ?string $device = null; // device id (required)
    private string $name = '';
    private array $type = ['value'=>'virtual','label'=>'virtual']; // NetBox UI default

    // Common optional fields
    private ?string $label = null;
    private bool $enabled = true;
    private ?string $parent = null; // self-reference id
    private ?string $bridge = null; // self-reference id
    private ?string $lag = null;    // LAG interface id
    private ?int $mtu = null;
    private ?string $primary_mac_address = null; // MacAddress id
    private ?int $speed = null;
    private ?string $duplex = null; // half|full|auto
    private ?string $wwn = null;
    private bool $mgmt_only = false;
    private string $description = '';
    private string $mode = 'access';
    private ?string $rf_role = null;
    private ?string $rf_channel = null;
    private ?string $poe_mode = null; // off|pd|pse
    private ?string $poe_type = null; // type1-ieee802.3af etc.
    private ?int $rf_channel_frequency = null;
    private ?int $rf_channel_width = null;
    private ?int $tx_power = null;
    private ?string $untagged_vlan = null; // Vlan id
    private array $tagged_vlans = []; // array of Vlan ids
    private ?string $qinq_svlan = null; // Vlan id
    private ?string $vlan_translation_policy = null; // policy id
    private bool $mark_connected = false;
    private array $wireless_lans = []; // array of WirelessLAN ids
    private ?string $vrf = null; // VRF id
    private array $tags = [];
    private array $custom_fields = [];

    // Additional structures
    private array $vdcs = []; // array of VDC ids (NetBox 4.x)
    private ?string $module = null; // module id

    // Read-only/metadata
    private ?string $url = null;
    private ?string $display = null;
    private ?string $created = null;
    private ?string $last_updated = null;

    static private Client $client;

    public function __construct()
    {
        self::$client = new Client();
    }

    // CRUD
    /**
     * Create (POST)
     * @throws CloudGenException
     */
    public function add(): void
    {
        if (empty($this->getDevice())) {
            throw new CloudGenException("Missing device (DCIM Interface requires parent device)");
        }
        if (empty($this->getName())) {
            throw new CloudGenException("Missing name for NetworkInterface");
        }

        try {
            $res = self::$client->getHttpClient()->post("/dcim/interfaces/", $this->getAddParamArr());
            $this->loadFromApiResult($res);
        } catch (GuzzleException $e) {
            throw new CloudGenException("Couldn't create the DCIM Interface: {$e->getMessage()}");
        }
    }

    /**
     * Read single (by id or by device + name)
     * @throws CloudGenException
     */
    public function load(): void
    {
        try {
            if (!is_null($this->getId())) {
                $res = self::$client->getHttpClient()->get("/dcim/interfaces/" . $this->getId() . "/", []);
                $this->loadFromApiResult($res);
                return;
            }

            if (!empty($this->getDevice()) && !empty($this->getName())) {
                // NetBox API: 'device' expects name/slug, 'device_id' expects numeric ID
                $deviceFilterKey = is_numeric($this->getDevice()) ? 'device_id' : 'device';
                $res = self::$client->getHttpClient()->get("/dcim/interfaces/", [
                    $deviceFilterKey => $this->getDevice(),
                    'name' => $this->getName(),
                ]);

                if (($res['count'] ?? 0) === 0) {
                    throw new CloudGenException("NetworkInterface not found for device='{$this->getDevice()}', name='{$this->getName()}'");
                }
                if (($res['count'] ?? 0) > 1) {
                    throw new CloudGenException("Multiple NetworkInterface entries found for device='{$this->getDevice()}', name='{$this->getName()}'");
                }
                $this->loadFromApiResult($res['results'][0]);
                return;
            }

            throw new CloudGenException("Can't load NetworkInterface without 'id' or ('device' and 'name')");
        } catch (GuzzleException $e) {
            throw new CloudGenException("Couldn't load the DCIM Interface: {$e->getMessage()}");
        }
    }

    /**
     * List with optional filters
     * @param array $filters
     * @return array
     * @throws CloudGenException
     */
    public function list(array $filters = []): array
    {
        try {
            return self::$client->getHttpClient()->get("/dcim/interfaces/", $filters);
        } catch (GuzzleException $e) {
            throw new CloudGenException("Couldn't list DCIM Interfaces: {$e->getMessage()}");
        }
    }

    /**
     * Replace (PUT)
     * @throws CloudGenException
     */
    public function edit(): void
    {
        if (is_null($this->getId())) {
            throw new CloudGenException("Can't edit NetworkInterface without 'id'");
        }
        try {
            $res = self::$client->getHttpClient()->put("/dcim/interfaces/" . $this->getId() . "/", $this->getEditParamArr());
            $this->loadFromApiResult($res);
        } catch (GuzzleException $e) {
            throw new CloudGenException("Couldn't edit the DCIM Interface: {$e->getMessage()}");
        }
    }

    /**
     * Partial update (PATCH)
     * @throws CloudGenException
     */
    public function update(): void
    {
        if (is_null($this->getId())) {
            throw new CloudGenException("Can't update NetworkInterface without 'id'");
        }
        try {
            $res = self::$client->getHttpClient()->patch("/dcim/interfaces/" . $this->getId() . "/", $this->getEditParamArr());
            $this->loadFromApiResult($res);
        } catch (GuzzleException $e) {
            throw new CloudGenException("Couldn't update the DCIM Interface: {$e->getMessage()}");
        }
    }

    /**
     * Delete (DELETE)
     * @throws CloudGenException
     */
    public function delete(): void
    {
        if (is_null($this->getId())) {
            throw new CloudGenException("Can't delete NetworkInterface without 'id'");
        }
        try {
            self::$client->getHttpClient()->delete("/dcim/interfaces/" . $this->getId() . "/", []);
            $this->setId(null);
        } catch (GuzzleException $e) {
            throw new CloudGenException("Couldn't delete the DCIM Interface: {$e->getMessage()}");
        }
    }

    // Helper operations
    /**
     * List all interfaces for a Device
     * @param string $deviceId
     * @return array
     * @throws CloudGenException
     */
    public function listByDevice(string $deviceId): array
    {
        // NetBox API: 'device' expects name/slug, 'device_id' expects numeric ID
        $filterKey = is_numeric($deviceId) ? 'device_id' : 'device';
        return $this->list([$filterKey => $deviceId]);
    }

    /**
     * Assign or update primary MAC by MacAddress id
     * @param string $macId
     * @throws CloudGenException
     */
    public function setPrimaryMacById(string $macId): void
    {
        $this->setPrimaryMacAddress($macId);
        $this->update();
    }

    /**
     * Assign untagged VLAN by id and patch
     * @param string $vlanId
     * @throws CloudGenException
     */
    public function assignUntaggedVlan(string $vlanId): void
    {
        $this->setUntaggedVlan($vlanId);
        $this->update();
    }

    /**
     * Replace tagged VLANs set and patch
     * @param array $vlanIds
     * @throws CloudGenException
     */
    public function updateTaggedVlans(array $vlanIds): void
    {
        $this->tagged_vlans = array_values(array_unique(array_map('strval', $vlanIds)));
        $this->update();
    }

    /**
     * Add a single tagged VLAN id and patch
     * @param string $vlanId
     * @throws CloudGenException
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
     * @throws CloudGenException
     */
    public function removeTaggedVlan(string $vlanId): void
    {
        $this->updateTaggedVlans(array_values(array_filter($this->getTaggedVlans(), function ($v) use ($vlanId) {
            return (string)$v !== (string)$vlanId;
        })));
    }

    // --- Helpers: payload builders & loader ---
    private function getAddParamArr(): array
    {
        $params = [
            'device' => $this->getDevice(),
            'name' => $this->getName(),
            'type' => $this->getType(),
            'enabled' => $this->isEnabled(),
        ];

        if (!empty($this->vdcs)) { $params['vdcs'] = array_values(array_map('strval', $this->vdcs)); }
        if (!is_null($this->module)) { $params['module'] = $this->module; }
        if (!is_null($this->label)) { $params['label'] = $this->label; }
        if (!is_null($this->parent)) { $params['parent'] = $this->parent; }
        if (!is_null($this->bridge)) { $params['bridge'] = $this->bridge; }
        if (!is_null($this->lag)) { $params['lag'] = $this->lag; }
        if (!is_null($this->mtu)) { $params['mtu'] = $this->mtu; }
        if (!is_null($this->primary_mac_address)) { $params['primary_mac_address'] = $this->primary_mac_address; }
        if (!is_null($this->speed)) { $params['speed'] = $this->speed; }
        if (!is_null($this->duplex)) { $params['duplex'] = $this->duplex; }
        if (!is_null($this->wwn)) { $params['wwn'] = $this->wwn; }
        if ($this->mgmt_only) { $params['mgmt_only'] = $this->mgmt_only; }
        if (!empty($this->description)) { $params['description'] = $this->description; }
        if (!empty($this->mode)) { $params['mode'] = $this->mode; }
        if (!is_null($this->rf_role)) { $params['rf_role'] = $this->rf_role; }
        if (!is_null($this->rf_channel)) { $params['rf_channel'] = $this->rf_channel; }
        if (!is_null($this->poe_mode)) { $params['poe_mode'] = $this->poe_mode; }
        if (!is_null($this->poe_type)) { $params['poe_type'] = $this->poe_type; }
        if (!is_null($this->rf_channel_frequency)) { $params['rf_channel_frequency'] = $this->rf_channel_frequency; }
        if (!is_null($this->rf_channel_width)) { $params['rf_channel_width'] = $this->rf_channel_width; }
        if (!is_null($this->tx_power)) { $params['tx_power'] = $this->tx_power; }
        if (!is_null($this->untagged_vlan)) { $params['untagged_vlan'] = $this->untagged_vlan; }
        if (!empty($this->tagged_vlans)) { $params['tagged_vlans'] = $this->tagged_vlans; }
        if (!is_null($this->qinq_svlan)) { $params['qinq_svlan'] = $this->qinq_svlan; }
        if (!is_null($this->vlan_translation_policy)) { $params['vlan_translation_policy'] = $this->vlan_translation_policy; }
        if ($this->mark_connected) { $params['mark_connected'] = $this->mark_connected; }
        if (!empty($this->wireless_lans)) { $params['wireless_lans'] = array_values(array_map('strval', $this->wireless_lans)); }
        if (!is_null($this->vrf)) { $params['vrf'] = $this->vrf; }
        if (!empty($this->tags)) { $params['tags'] = $this->tags; }
        if (!empty($this->custom_fields)) { $params['custom_fields'] = $this->custom_fields; }

        return $params;
    }

    private function getEditParamArr(): array
    {
        // For simplicity, reuse add param builder which respects optional fields
        return $this->getAddParamArr();
    }

    private function loadFromApiResult(array $res): void
    {

        $this->setId(isset($res['id']) ? (string)$res['id'] : $this->getId());
        $this->setDevice(self::extractId($res['device'] ?? null));
        $this->setName((string)($res['name'] ?? $this->getName()));
        $this->setType($res['type'] ?? $this->getType());
        $this->setLabel(isset($res['label']) ? (string)$res['label'] : $this->getLabel());
        $this->setEnabled((bool)($res['enabled'] ?? $this->isEnabled()));
        $this->setParent(self::extractId($res['parent'] ?? null));
        $this->setBridge(self::extractId($res['bridge'] ?? null));
        $this->setLag(self::extractId($res['lag'] ?? null));
        $this->setMtu(isset($res['mtu']) ? (int)$res['mtu'] : $this->getMtu());
        $this->setPrimaryMacAddress(self::extractId($res['primary_mac_address'] ?? null));
        $this->setSpeed(isset($res['speed']) ? (int)$res['speed'] : $this->getSpeed());
        $this->setDuplex(isset($res['duplex']) ? (string)$res['duplex'] : $this->getDuplex());
        $this->setWwn(isset($res['wwn']) ? (string)$res['wwn'] : $this->getWwn());
        $this->setMgmtOnly((bool)($res['mgmt_only'] ?? $this->isMgmtOnly()));
        $this->setDescription((string)($res['description'] ?? $this->getDescription()));
        $this->setMode((string)($res['mode'] ?? $this->getMode()));
        $this->setRfRole(isset($res['rf_role']) ? (string)$res['rf_role'] : $this->getRfRole());
        $this->setRfChannel(isset($res['rf_channel']) ? (string)$res['rf_channel'] : $this->getRfChannel());
        $this->setPoeMode(isset($res['poe_mode']) ? (string)$res['poe_mode'] : $this->getPoeMode());
        $this->setPoeType(isset($res['poe_type']) ? (string)$res['poe_type'] : $this->getPoeType());
        $this->setRfChannelFrequency(isset($res['rf_channel_frequency']) ? (int)$res['rf_channel_frequency'] : $this->getRfChannelFrequency());
        $this->setRfChannelWidth(isset($res['rf_channel_width']) ? (int)$res['rf_channel_width'] : $this->getRfChannelWidth());
        $this->setTxPower(isset($res['tx_power']) ? (int)$res['tx_power'] : $this->getTxPower());
        $this->setUntaggedVlan(self::extractId($res['untagged_vlan'] ?? null));
        $tagged = $res['tagged_vlans'] ?? [];
        if (is_array($tagged)) {
            $this->tagged_vlans = array_map(function ($v) { return (string)self::extractId($v); }, $tagged);
        } else {
            $this->tagged_vlans = [];
        }
        $this->setQinqSvlan(self::extractId($res['qinq_svlan'] ?? null));
        $this->setVlanTranslationPolicy(self::extractId($res['vlan_translation_policy'] ?? null));
        $this->setMarkConnected(isset($res['mark_connected']) ? (bool)$res['mark_connected'] : $this->isMarkConnected());
        $wlans = $res['wireless_lans'] ?? [];
        if (is_array($wlans)) {
            $this->wireless_lans = array_map(function ($v) { return (string)self::extractId($v); }, $wlans);
        } else {
            $this->wireless_lans = [];
        }
        $this->setVrf(self::extractId($res['vrf'] ?? null));
        $this->setTags($res['tags'] ?? $this->getTags());
        $this->setCustomFields($res['custom_fields'] ?? $this->getCustomFields());

        $this->vdcs = array_map('strval', $res['vdcs'] ?? $this->vdcs);
        $this->module = self::extractId($res['module'] ?? $this->module);

        $this->url = $res['url'] ?? $this->url;
        $this->display = $res['display'] ?? $this->display;
        $this->created = $res['created'] ?? $this->created;
        $this->last_updated = $res['last_updated'] ?? $this->last_updated;
    }

    private static function extractId($maybe): ?string
    {
        if (is_null($maybe) || $maybe === '') { return null; }
        if (is_array($maybe)) { return isset($maybe['id']) ? (string)$maybe['id'] : null; }
        return (string)$maybe;
    }

    // --- Getters/Setters ---
    public function getId(): ?string { return $this->id; }
    public function setId(?string $id): NetworkInterface { $this->id = $id; return $this; }

    public function getDevice(): ?string { return $this->device; }
    public function setDevice(?string $device): NetworkInterface { $this->device = $device; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): NetworkInterface { $this->name = $name; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(array $type): NetworkInterface { $this->type = $type; return $this; }

    public function getLabel(): ?string { return $this->label; }
    public function setLabel(?string $label): NetworkInterface { $this->label = $label; return $this; }

    public function isEnabled(): bool { return $this->enabled; }
    public function setEnabled(bool $enabled): NetworkInterface { $this->enabled = $enabled; return $this; }

    public function getParent(): ?string { return $this->parent; }
    public function setParent(?string $parent): NetworkInterface { $this->parent = $parent; return $this; }

    public function getBridge(): ?string { return $this->bridge; }
    public function setBridge(?string $bridge): NetworkInterface { $this->bridge = $bridge; return $this; }

    public function getLag(): ?string { return $this->lag; }
    public function setLag(?string $lag): NetworkInterface { $this->lag = $lag; return $this; }

    public function getMtu(): ?int { return $this->mtu; }
    public function setMtu(?int $mtu): NetworkInterface { $this->mtu = $mtu; return $this; }

    public function getPrimaryMacAddress(): ?string { return $this->primary_mac_address; }
    public function setPrimaryMacAddress(?string $primary_mac_address): NetworkInterface { $this->primary_mac_address = $primary_mac_address; return $this; }

    public function getSpeed(): ?int { return $this->speed; }
    public function setSpeed(?int $speed): NetworkInterface { $this->speed = $speed; return $this; }

    public function getDuplex(): ?string { return $this->duplex; }
    public function setDuplex(?string $duplex): NetworkInterface { $this->duplex = $duplex; return $this; }

    public function getWwn(): ?string { return $this->wwn; }
    public function setWwn(?string $wwn): NetworkInterface { $this->wwn = $wwn; return $this; }

    public function isMgmtOnly(): bool { return $this->mgmt_only; }
    public function setMgmtOnly(bool $mgmt_only): NetworkInterface { $this->mgmt_only = $mgmt_only; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): NetworkInterface { $this->description = $description; return $this; }

    public function getMode(): string { return $this->mode; }
    public function setMode(string $mode): NetworkInterface { $this->mode = $mode; return $this; }

    public function getRfRole(): ?string { return $this->rf_role; }
    public function setRfRole(?string $rf_role): NetworkInterface { $this->rf_role = $rf_role; return $this; }

    public function getRfChannel(): ?string { return $this->rf_channel; }
    public function setRfChannel(?string $rf_channel): NetworkInterface { $this->rf_channel = $rf_channel; return $this; }

    public function getPoeMode(): ?string { return $this->poe_mode; }
    public function setPoeMode(?string $poe_mode): NetworkInterface { $this->poe_mode = $poe_mode; return $this; }

    public function getPoeType(): ?string { return $this->poe_type; }
    public function setPoeType(?string $poe_type): NetworkInterface { $this->poe_type = $poe_type; return $this; }

    public function getRfChannelFrequency(): ?int { return $this->rf_channel_frequency; }
    public function setRfChannelFrequency(?int $rf_channel_frequency): NetworkInterface { $this->rf_channel_frequency = $rf_channel_frequency; return $this; }

    public function getRfChannelWidth(): ?int { return $this->rf_channel_width; }
    public function setRfChannelWidth(?int $rf_channel_width): NetworkInterface { $this->rf_channel_width = $rf_channel_width; return $this; }

    public function getTxPower(): ?int { return $this->tx_power; }
    public function setTxPower(?int $tx_power): NetworkInterface { $this->tx_power = $tx_power; return $this; }

    public function getUntaggedVlan(): ?string { return $this->untagged_vlan; }
    public function setUntaggedVlan(?string $untagged_vlan): NetworkInterface { $this->untagged_vlan = $untagged_vlan; return $this; }

    public function getTaggedVlans(): array { return $this->tagged_vlans; }
    public function setTaggedVlans(array $tagged_vlans): NetworkInterface { $this->tagged_vlans = array_values(array_map('strval', $tagged_vlans)); return $this; }

    public function getQinqSvlan(): ?string { return $this->qinq_svlan; }
    public function setQinqSvlan(?string $qinq_svlan): NetworkInterface { $this->qinq_svlan = $qinq_svlan; return $this; }

    public function getVlanTranslationPolicy(): ?string { return $this->vlan_translation_policy; }
    public function setVlanTranslationPolicy(?string $vlan_translation_policy): NetworkInterface { $this->vlan_translation_policy = $vlan_translation_policy; return $this; }

    public function isMarkConnected(): bool { return $this->mark_connected; }
    public function setMarkConnected(bool $mark_connected): NetworkInterface { $this->mark_connected = $mark_connected; return $this; }

    public function getWirelessLans(): array { return $this->wireless_lans; }
    public function setWirelessLans(array $wireless_lans): NetworkInterface { $this->wireless_lans = array_values(array_map('strval', $wireless_lans)); return $this; }

    public function getVrf(): ?string { return $this->vrf; }
    public function setVrf(?string $vrf): NetworkInterface { $this->vrf = $vrf; return $this; }

    public function getTags(): array { return $this->tags; }
    public function setTags(array $tags): NetworkInterface { $this->tags = $tags; return $this; }

    public function getCustomFields(): array { return $this->custom_fields; }
    public function setCustomFields(array $custom_fields): NetworkInterface { $this->custom_fields = $custom_fields; return $this; }

    public function getVdcs(): array { return $this->vdcs; }
    public function setVdcs(array $vdcs): NetworkInterface { $this->vdcs = array_values(array_map('strval', $vdcs)); return $this; }

    public function getModule(): ?string { return $this->module; }
    public function setModule(?string $module): NetworkInterface { $this->module = $module; return $this; }
}