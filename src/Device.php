<?php

namespace Ancalagon\Netbox;

class Device
{
    private const string ENDPOINT = '/dcim/devices/';

    // Writable fields
    private ?string $id = null;
    private string $name = '';              // required
    private ?string $device_type = null;    // required FK
    private ?string $role = null;           // required FK
    private ?string $site = null;           // required FK
    private ?string $tenant = null;
    private ?string $platform = null;
    private string $serial = '';
    private ?string $asset_tag = null;
    private string $status = 'active';      // planned, staging, active, decommissioning, offline
    private ?string $location = null;
    private ?string $rack = null;
    private ?float $position = null;
    private ?string $face = null;
    private ?float $latitude = null;
    private ?float $longitude = null;
    private ?string $airflow = null;
    private ?string $primary_ip4 = null;
    private ?string $primary_ip6 = null;
    private ?string $oob_ip = null;
    private ?string $cluster = null;
    private ?string $virtual_chassis = null;
    private ?int $vc_position = null;
    private ?int $vc_priority = null;
    private string $description = '';
    private string $comments = '';
    private ?string $config_template = null;
    private $local_context_data = null;
    private ?string $owner = null;
    private array $tags = [];
    private array $custom_fields = [];

    // Read-only fields
    private ?string $url = null;
    private ?string $display_url = null;
    private ?string $display = null;
    private ?string $created = null;
    private ?string $last_updated = null;
    private $parent_device = null;
    private ?int $console_port_count = null;
    private ?int $console_server_port_count = null;
    private ?int $power_port_count = null;
    private ?int $power_outlet_count = null;
    private ?int $interface_count = null;
    private ?int $front_port_count = null;
    private ?int $rear_port_count = null;
    private ?int $device_bay_count = null;
    private ?int $module_bay_count = null;
    private ?int $inventory_item_count = null;

    private static NetboxClient $client;

    public function __construct()
    {
        self::$client = new NetboxClient();
    }

    /**
     * Create (POST)
     * @throws Exception
     */
    public function add(): void
    {
        if (empty($this->getName())) {
            throw new Exception("Missing name for Device");
        }
        if (is_null($this->getDeviceType())) {
            throw new Exception("Missing device_type for Device");
        }
        if (is_null($this->getRole())) {
            throw new Exception("Missing role for Device");
        }
        if (is_null($this->getSite())) {
            throw new Exception("Missing site for Device");
        }

        $res = self::$client->post(self::ENDPOINT, $this->getAddParamArr());
        $this->loadFromApiResult($res);
    }

    /**
     * Read single (by id or name)
     * @throws Exception
     */
    public function load(): void
    {
        if (!is_null($this->getId())) {
            $res = self::$client->get(self::ENDPOINT . $this->getId() . '/');
            $this->loadFromApiResult($res);
            return;
        }

        $params = [];
        if (!empty($this->getName())) {
            $params['name'] = $this->getName();
        }

        if (empty($params)) {
            throw new Exception("Can't load Device without 'id' or 'name'");
        }

        $res = self::$client->get(self::ENDPOINT, $params);

        if (($res['count'] ?? 0) === 0) {
            throw new Exception("Device not found");
        }
        if (($res['count'] ?? 0) > 1) {
            throw new Exception("Multiple Devices returned by query");
        }

        $this->loadFromApiResult($res['results'][0]);
    }

    /**
     * List with optional filters
     * @param array $filters
     * @return array
     * @throws Exception
     */
    public function list(array $filters = []): array
    {
        return self::$client->get(self::ENDPOINT, $filters);
    }

    /**
     * Replace (PUT)
     * @throws Exception
     */
    public function edit(): void
    {
        if (is_null($this->getId())) {
            throw new Exception("Can't edit Device without 'id'");
        }

        $res = self::$client->put(self::ENDPOINT . $this->getId() . '/', $this->getEditParamArr());
        $this->loadFromApiResult($res);
    }

    /**
     * Partial Update (PATCH)
     * @throws Exception
     */
    public function update(): void
    {
        if (is_null($this->getId())) {
            throw new Exception("Can't update Device without 'id'");
        }

        $res = self::$client->patch(self::ENDPOINT . $this->getId() . '/', $this->getEditParamArr());
        $this->loadFromApiResult($res);
    }

    /**
     * Delete (DELETE)
     * @throws Exception
     */
    public function delete(): void
    {
        if (is_null($this->getId())) {
            throw new Exception("Can't delete Device without 'id'");
        }

        self::$client->delete(self::ENDPOINT . $this->getId() . '/');
        $this->setId(null);
    }

    // --- Private helpers ---

    private function getAddParamArr(): array
    {
        $params = [
            'name' => $this->getName(),
            'device_type' => (int)$this->getDeviceType(),
            'role' => (int)$this->getRole(),
            'site' => (int)$this->getSite(),
            'status' => $this->getStatus(),
        ];

        if (!is_null($this->getTenant())) { $params['tenant'] = (int)$this->getTenant(); }
        if (!is_null($this->getPlatform())) { $params['platform'] = (int)$this->getPlatform(); }
        if (!empty($this->getSerial())) { $params['serial'] = $this->getSerial(); }
        if (!is_null($this->getAssetTag())) { $params['asset_tag'] = $this->getAssetTag(); }
        if (!is_null($this->getLocation())) { $params['location'] = (int)$this->getLocation(); }
        if (!is_null($this->getRack())) { $params['rack'] = (int)$this->getRack(); }
        if (!is_null($this->getPosition())) { $params['position'] = $this->getPosition(); }
        if (!is_null($this->getFace())) { $params['face'] = $this->getFace(); }
        if (!is_null($this->getLatitude())) { $params['latitude'] = $this->getLatitude(); }
        if (!is_null($this->getLongitude())) { $params['longitude'] = $this->getLongitude(); }
        if (!is_null($this->getAirflow())) { $params['airflow'] = $this->getAirflow(); }
        if (!is_null($this->getPrimaryIp4())) { $params['primary_ip4'] = (int)$this->getPrimaryIp4(); }
        if (!is_null($this->getPrimaryIp6())) { $params['primary_ip6'] = (int)$this->getPrimaryIp6(); }
        if (!is_null($this->getOobIp())) { $params['oob_ip'] = (int)$this->getOobIp(); }
        if (!is_null($this->getCluster())) { $params['cluster'] = (int)$this->getCluster(); }
        if (!is_null($this->getVirtualChassis())) { $params['virtual_chassis'] = (int)$this->getVirtualChassis(); }
        if (!is_null($this->getVcPosition())) { $params['vc_position'] = $this->getVcPosition(); }
        if (!is_null($this->getVcPriority())) { $params['vc_priority'] = $this->getVcPriority(); }
        if (!empty($this->getDescription())) { $params['description'] = $this->getDescription(); }
        if (!empty($this->getComments())) { $params['comments'] = $this->getComments(); }
        if (!is_null($this->getConfigTemplate())) { $params['config_template'] = (int)$this->getConfigTemplate(); }
        if (!is_null($this->getLocalContextData())) { $params['local_context_data'] = $this->getLocalContextData(); }
        if (!is_null($this->getOwner())) { $params['owner'] = (int)$this->getOwner(); }
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
        $this->setId(isset($res['id']) ? (string)$res['id'] : null);
        $this->setName((string)($res['name'] ?? $this->getName()));
        $this->setDeviceType(self::extractId($res['device_type'] ?? null));
        $this->setRole(self::extractId($res['role'] ?? null));
        $this->setSite(self::extractId($res['site'] ?? null));
        $this->setTenant(self::extractId($res['tenant'] ?? null));
        $this->setPlatform(self::extractId($res['platform'] ?? null));
        $this->setSerial((string)($res['serial'] ?? ''));
        $this->setAssetTag($res['asset_tag'] ?? null);

        if (isset($res['status'])) {
            $this->setStatus(is_array($res['status']) ? ($res['status']['value'] ?? 'active') : (string)$res['status']);
        }

        $this->setLocation(self::extractId($res['location'] ?? null));
        $this->setRack(self::extractId($res['rack'] ?? null));
        $this->setPosition(isset($res['position']) ? (float)$res['position'] : null);

        if (isset($res['face'])) {
            $this->setFace(is_array($res['face']) ? ($res['face']['value'] ?? null) : $res['face']);
        }

        $this->setLatitude(isset($res['latitude']) ? (float)$res['latitude'] : null);
        $this->setLongitude(isset($res['longitude']) ? (float)$res['longitude'] : null);

        if (isset($res['airflow'])) {
            $this->setAirflow(is_array($res['airflow']) ? ($res['airflow']['value'] ?? null) : $res['airflow']);
        }

        $this->setPrimaryIp4(self::extractId($res['primary_ip4'] ?? null));
        $this->setPrimaryIp6(self::extractId($res['primary_ip6'] ?? null));
        $this->setOobIp(self::extractId($res['oob_ip'] ?? null));
        $this->setCluster(self::extractId($res['cluster'] ?? null));
        $this->setVirtualChassis(self::extractId($res['virtual_chassis'] ?? null));
        $this->setVcPosition($res['vc_position'] ?? null);
        $this->setVcPriority($res['vc_priority'] ?? null);
        $this->setDescription((string)($res['description'] ?? ''));
        $this->setComments((string)($res['comments'] ?? ''));
        $this->setConfigTemplate(self::extractId($res['config_template'] ?? null));
        $this->setLocalContextData($res['local_context_data'] ?? null);
        $this->setOwner(self::extractId($res['owner'] ?? null));
        $this->setTags($res['tags'] ?? []);
        $this->setCustomFields($res['custom_fields'] ?? []);

        // Read-only fields
        $this->url = $res['url'] ?? null;
        $this->display_url = $res['display_url'] ?? null;
        $this->display = $res['display'] ?? null;
        $this->created = $res['created'] ?? null;
        $this->last_updated = $res['last_updated'] ?? null;
        $this->parent_device = $res['parent_device'] ?? null;
        $this->console_port_count = isset($res['console_port_count']) ? (int)$res['console_port_count'] : null;
        $this->console_server_port_count = isset($res['console_server_port_count']) ? (int)$res['console_server_port_count'] : null;
        $this->power_port_count = isset($res['power_port_count']) ? (int)$res['power_port_count'] : null;
        $this->power_outlet_count = isset($res['power_outlet_count']) ? (int)$res['power_outlet_count'] : null;
        $this->interface_count = isset($res['interface_count']) ? (int)$res['interface_count'] : null;
        $this->front_port_count = isset($res['front_port_count']) ? (int)$res['front_port_count'] : null;
        $this->rear_port_count = isset($res['rear_port_count']) ? (int)$res['rear_port_count'] : null;
        $this->device_bay_count = isset($res['device_bay_count']) ? (int)$res['device_bay_count'] : null;
        $this->module_bay_count = isset($res['module_bay_count']) ? (int)$res['module_bay_count'] : null;
        $this->inventory_item_count = isset($res['inventory_item_count']) ? (int)$res['inventory_item_count'] : null;
    }

    private static function extractId($maybe): ?string
    {
        if (is_null($maybe) || $maybe === '') { return null; }
        if (is_array($maybe)) { return isset($maybe['id']) ? (string)$maybe['id'] : null; }
        return (string)$maybe;
    }

    // --- Getters / Setters ---

    public function getId(): ?string { return $this->id; }
    public function setId(?string $id): Device { $this->id = $id; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): Device { $this->name = $name; return $this; }

    public function getDeviceType(): ?string { return $this->device_type; }
    public function setDeviceType(?string $device_type): Device { $this->device_type = $device_type; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(?string $role): Device { $this->role = $role; return $this; }

    public function getSite(): ?string { return $this->site; }
    public function setSite(?string $site): Device { $this->site = $site; return $this; }

    public function getTenant(): ?string { return $this->tenant; }
    public function setTenant(?string $tenant): Device { $this->tenant = $tenant; return $this; }

    public function getPlatform(): ?string { return $this->platform; }
    public function setPlatform(?string $platform): Device { $this->platform = $platform; return $this; }

    public function getSerial(): string { return $this->serial; }
    public function setSerial(string $serial): Device { $this->serial = $serial; return $this; }

    public function getAssetTag(): ?string { return $this->asset_tag; }
    public function setAssetTag(?string $asset_tag): Device { $this->asset_tag = $asset_tag; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): Device { $this->status = $status; return $this; }

    public function getLocation(): ?string { return $this->location; }
    public function setLocation(?string $location): Device { $this->location = $location; return $this; }

    public function getRack(): ?string { return $this->rack; }
    public function setRack(?string $rack): Device { $this->rack = $rack; return $this; }

    public function getPosition(): ?float { return $this->position; }
    public function setPosition(?float $position): Device { $this->position = $position; return $this; }

    public function getFace(): ?string { return $this->face; }
    public function setFace(?string $face): Device { $this->face = $face; return $this; }

    public function getLatitude(): ?float { return $this->latitude; }
    public function setLatitude(?float $latitude): Device { $this->latitude = $latitude; return $this; }

    public function getLongitude(): ?float { return $this->longitude; }
    public function setLongitude(?float $longitude): Device { $this->longitude = $longitude; return $this; }

    public function getAirflow(): ?string { return $this->airflow; }
    public function setAirflow(?string $airflow): Device { $this->airflow = $airflow; return $this; }

    public function getPrimaryIp4(): ?string { return $this->primary_ip4; }
    public function setPrimaryIp4(?string $primary_ip4): Device { $this->primary_ip4 = $primary_ip4; return $this; }

    public function getPrimaryIp6(): ?string { return $this->primary_ip6; }
    public function setPrimaryIp6(?string $primary_ip6): Device { $this->primary_ip6 = $primary_ip6; return $this; }

    public function getOobIp(): ?string { return $this->oob_ip; }
    public function setOobIp(?string $oob_ip): Device { $this->oob_ip = $oob_ip; return $this; }

    public function getCluster(): ?string { return $this->cluster; }
    public function setCluster(?string $cluster): Device { $this->cluster = $cluster; return $this; }

    public function getVirtualChassis(): ?string { return $this->virtual_chassis; }
    public function setVirtualChassis(?string $virtual_chassis): Device { $this->virtual_chassis = $virtual_chassis; return $this; }

    public function getVcPosition(): ?int { return $this->vc_position; }
    public function setVcPosition(?int $vc_position): Device { $this->vc_position = $vc_position; return $this; }

    public function getVcPriority(): ?int { return $this->vc_priority; }
    public function setVcPriority(?int $vc_priority): Device { $this->vc_priority = $vc_priority; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): Device { $this->description = $description; return $this; }

    public function getComments(): string { return $this->comments; }
    public function setComments(string $comments): Device { $this->comments = $comments; return $this; }

    public function getConfigTemplate(): ?string { return $this->config_template; }
    public function setConfigTemplate(?string $config_template): Device { $this->config_template = $config_template; return $this; }

    public function getLocalContextData() { return $this->local_context_data; }
    public function setLocalContextData($local_context_data): Device { $this->local_context_data = $local_context_data; return $this; }

    public function getOwner(): ?string { return $this->owner; }
    public function setOwner(?string $owner): Device { $this->owner = $owner; return $this; }

    public function getTags(): array { return $this->tags; }
    public function setTags(array $tags): Device { $this->tags = $tags; return $this; }

    public function getCustomFields(): array { return $this->custom_fields; }
    public function setCustomFields(array $custom_fields): Device { $this->custom_fields = $custom_fields; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function getDisplayUrl(): ?string { return $this->display_url; }
    public function getDisplay(): ?string { return $this->display; }
    public function getCreated(): ?string { return $this->created; }
    public function getLastUpdated(): ?string { return $this->last_updated; }
    public function getParentDevice() { return $this->parent_device; }
    public function getConsolePortCount(): ?int { return $this->console_port_count; }
    public function getConsoleServerPortCount(): ?int { return $this->console_server_port_count; }
    public function getPowerPortCount(): ?int { return $this->power_port_count; }
    public function getPowerOutletCount(): ?int { return $this->power_outlet_count; }
    public function getInterfaceCount(): ?int { return $this->interface_count; }
    public function getFrontPortCount(): ?int { return $this->front_port_count; }
    public function getRearPortCount(): ?int { return $this->rear_port_count; }
    public function getDeviceBayCount(): ?int { return $this->device_bay_count; }
    public function getModuleBayCount(): ?int { return $this->module_bay_count; }
    public function getInventoryItemCount(): ?int { return $this->inventory_item_count; }
}
