<?php

namespace Ancalagon\Netbox;

class DeviceType
{
    private const string ENDPOINT = '/dcim/device-types/';

    // Writable fields
    private ?string $id = null;
    private ?string $manufacturer = null; // required FK
    private string $model = '';           // required
    private string $slug = '';            // required
    private ?string $default_platform = null;
    private string $part_number = '';
    private float $u_height = 1.0;
    private bool $is_full_depth = true;
    private bool $exclude_from_utilization = false;
    private ?string $subdevice_role = null;
    private ?string $airflow = null;
    private ?float $weight = null;
    private ?string $weight_unit = null;
    private string $description = '';
    private string $comments = '';
    private ?string $owner = null;
    private array $tags = [];
    private array $custom_fields = [];

    // Read-only fields
    private ?string $url = null;
    private ?string $display_url = null;
    private ?string $display = null;
    private ?string $created = null;
    private ?string $last_updated = null;
    private ?int $device_count = null;
    private ?int $console_port_template_count = null;
    private ?int $console_server_port_template_count = null;
    private ?int $power_port_template_count = null;
    private ?int $power_outlet_template_count = null;
    private ?int $interface_template_count = null;
    private ?int $front_port_template_count = null;
    private ?int $rear_port_template_count = null;
    private ?int $device_bay_template_count = null;
    private ?int $module_bay_template_count = null;
    private ?int $inventory_item_template_count = null;
    private $front_image = null;
    private $rear_image = null;

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
        if (is_null($this->getManufacturer())) {
            throw new Exception("Missing manufacturer for DeviceType");
        }
        if (empty($this->getModel())) {
            throw new Exception("Missing model for DeviceType");
        }
        if (empty($this->getSlug())) {
            throw new Exception("Missing slug for DeviceType");
        }

        $res = self::$client->post(self::ENDPOINT, $this->getAddParamArr());
        $this->loadFromApiResult($res);
    }

    /**
     * Read single (by id, model, or slug)
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
        if (!empty($this->getModel())) {
            $params['model'] = $this->getModel();
        } elseif (!empty($this->getSlug())) {
            $params['slug'] = $this->getSlug();
        }

        if (empty($params)) {
            throw new Exception("Can't load DeviceType without 'id', 'model' or 'slug'");
        }

        $res = self::$client->get(self::ENDPOINT, $params);

        if (($res['count'] ?? 0) === 0) {
            throw new Exception("DeviceType not found");
        }
        if (($res['count'] ?? 0) > 1) {
            throw new Exception("Multiple DeviceTypes returned by query");
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
            throw new Exception("Can't edit DeviceType without 'id'");
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
            throw new Exception("Can't update DeviceType without 'id'");
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
            throw new Exception("Can't delete DeviceType without 'id'");
        }

        self::$client->delete(self::ENDPOINT . $this->getId() . '/');
        $this->setId(null);
    }

    // --- Private helpers ---

    private function getAddParamArr(): array
    {
        $params = [
            'manufacturer' => (int)$this->getManufacturer(),
            'model' => $this->getModel(),
            'slug' => $this->getSlug(),
            'u_height' => $this->getUHeight(),
            'is_full_depth' => $this->isFullDepth(),
            'exclude_from_utilization' => $this->isExcludeFromUtilization(),
        ];

        if (!is_null($this->getDefaultPlatform())) { $params['default_platform'] = (int)$this->getDefaultPlatform(); }
        if (!empty($this->getPartNumber())) { $params['part_number'] = $this->getPartNumber(); }
        if (!is_null($this->getSubdeviceRole())) { $params['subdevice_role'] = $this->getSubdeviceRole(); }
        if (!is_null($this->getAirflow())) { $params['airflow'] = $this->getAirflow(); }
        if (!is_null($this->getWeight())) { $params['weight'] = $this->getWeight(); }
        if (!is_null($this->getWeightUnit())) { $params['weight_unit'] = $this->getWeightUnit(); }
        if (!empty($this->getDescription())) { $params['description'] = $this->getDescription(); }
        if (!empty($this->getComments())) { $params['comments'] = $this->getComments(); }
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
        $this->setManufacturer(self::extractId($res['manufacturer'] ?? null));
        $this->setModel((string)($res['model'] ?? $this->getModel()));
        $this->setSlug((string)($res['slug'] ?? $this->getSlug()));
        $this->setDefaultPlatform(self::extractId($res['default_platform'] ?? null));
        $this->setPartNumber((string)($res['part_number'] ?? ''));
        $this->setUHeight((float)($res['u_height'] ?? 1.0));
        $this->setIsFullDepth((bool)($res['is_full_depth'] ?? true));
        $this->setExcludeFromUtilization((bool)($res['exclude_from_utilization'] ?? false));

        if (isset($res['subdevice_role'])) {
            $this->setSubdeviceRole(is_array($res['subdevice_role']) ? ($res['subdevice_role']['value'] ?? null) : $res['subdevice_role']);
        }
        if (isset($res['airflow'])) {
            $this->setAirflow(is_array($res['airflow']) ? ($res['airflow']['value'] ?? null) : $res['airflow']);
        }

        $this->setWeight(isset($res['weight']) ? (float)$res['weight'] : null);
        if (isset($res['weight_unit'])) {
            $this->setWeightUnit(is_array($res['weight_unit']) ? ($res['weight_unit']['value'] ?? null) : $res['weight_unit']);
        }
        $this->setDescription((string)($res['description'] ?? ''));
        $this->setComments((string)($res['comments'] ?? ''));
        $this->setOwner(self::extractId($res['owner'] ?? null));
        $this->setTags($res['tags'] ?? []);
        $this->setCustomFields($res['custom_fields'] ?? []);

        // Read-only fields
        $this->url = $res['url'] ?? null;
        $this->display_url = $res['display_url'] ?? null;
        $this->display = $res['display'] ?? null;
        $this->created = $res['created'] ?? null;
        $this->last_updated = $res['last_updated'] ?? null;
        $this->device_count = isset($res['device_count']) ? (int)$res['device_count'] : null;
        $this->console_port_template_count = isset($res['console_port_template_count']) ? (int)$res['console_port_template_count'] : null;
        $this->console_server_port_template_count = isset($res['console_server_port_template_count']) ? (int)$res['console_server_port_template_count'] : null;
        $this->power_port_template_count = isset($res['power_port_template_count']) ? (int)$res['power_port_template_count'] : null;
        $this->power_outlet_template_count = isset($res['power_outlet_template_count']) ? (int)$res['power_outlet_template_count'] : null;
        $this->interface_template_count = isset($res['interface_template_count']) ? (int)$res['interface_template_count'] : null;
        $this->front_port_template_count = isset($res['front_port_template_count']) ? (int)$res['front_port_template_count'] : null;
        $this->rear_port_template_count = isset($res['rear_port_template_count']) ? (int)$res['rear_port_template_count'] : null;
        $this->device_bay_template_count = isset($res['device_bay_template_count']) ? (int)$res['device_bay_template_count'] : null;
        $this->module_bay_template_count = isset($res['module_bay_template_count']) ? (int)$res['module_bay_template_count'] : null;
        $this->inventory_item_template_count = isset($res['inventory_item_template_count']) ? (int)$res['inventory_item_template_count'] : null;
        $this->front_image = $res['front_image'] ?? null;
        $this->rear_image = $res['rear_image'] ?? null;
    }

    private static function extractId($maybe): ?string
    {
        if (is_null($maybe) || $maybe === '') { return null; }
        if (is_array($maybe)) { return isset($maybe['id']) ? (string)$maybe['id'] : null; }
        return (string)$maybe;
    }

    // --- Getters / Setters ---

    public function getId(): ?string { return $this->id; }
    public function setId(?string $id): DeviceType { $this->id = $id; return $this; }

    public function getManufacturer(): ?string { return $this->manufacturer; }
    public function setManufacturer(?string $manufacturer): DeviceType { $this->manufacturer = $manufacturer; return $this; }

    public function getModel(): string { return $this->model; }
    public function setModel(string $model): DeviceType { $this->model = $model; return $this; }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): DeviceType { $this->slug = $slug; return $this; }

    public function getDefaultPlatform(): ?string { return $this->default_platform; }
    public function setDefaultPlatform(?string $default_platform): DeviceType { $this->default_platform = $default_platform; return $this; }

    public function getPartNumber(): string { return $this->part_number; }
    public function setPartNumber(string $part_number): DeviceType { $this->part_number = $part_number; return $this; }

    public function getUHeight(): float { return $this->u_height; }
    public function setUHeight(float $u_height): DeviceType { $this->u_height = $u_height; return $this; }

    public function isFullDepth(): bool { return $this->is_full_depth; }
    public function setIsFullDepth(bool $is_full_depth): DeviceType { $this->is_full_depth = $is_full_depth; return $this; }

    public function isExcludeFromUtilization(): bool { return $this->exclude_from_utilization; }
    public function setExcludeFromUtilization(bool $exclude_from_utilization): DeviceType { $this->exclude_from_utilization = $exclude_from_utilization; return $this; }

    public function getSubdeviceRole(): ?string { return $this->subdevice_role; }
    public function setSubdeviceRole(?string $subdevice_role): DeviceType { $this->subdevice_role = $subdevice_role; return $this; }

    public function getAirflow(): ?string { return $this->airflow; }
    public function setAirflow(?string $airflow): DeviceType { $this->airflow = $airflow; return $this; }

    public function getWeight(): ?float { return $this->weight; }
    public function setWeight(?float $weight): DeviceType { $this->weight = $weight; return $this; }

    public function getWeightUnit(): ?string { return $this->weight_unit; }
    public function setWeightUnit(?string $weight_unit): DeviceType { $this->weight_unit = $weight_unit; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): DeviceType { $this->description = $description; return $this; }

    public function getComments(): string { return $this->comments; }
    public function setComments(string $comments): DeviceType { $this->comments = $comments; return $this; }

    public function getOwner(): ?string { return $this->owner; }
    public function setOwner(?string $owner): DeviceType { $this->owner = $owner; return $this; }

    public function getTags(): array { return $this->tags; }
    public function setTags(array $tags): DeviceType { $this->tags = $tags; return $this; }

    public function getCustomFields(): array { return $this->custom_fields; }
    public function setCustomFields(array $custom_fields): DeviceType { $this->custom_fields = $custom_fields; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function getDisplayUrl(): ?string { return $this->display_url; }
    public function getDisplay(): ?string { return $this->display; }
    public function getCreated(): ?string { return $this->created; }
    public function getLastUpdated(): ?string { return $this->last_updated; }
    public function getDeviceCount(): ?int { return $this->device_count; }
    public function getConsolePortTemplateCount(): ?int { return $this->console_port_template_count; }
    public function getConsoleServerPortTemplateCount(): ?int { return $this->console_server_port_template_count; }
    public function getPowerPortTemplateCount(): ?int { return $this->power_port_template_count; }
    public function getPowerOutletTemplateCount(): ?int { return $this->power_outlet_template_count; }
    public function getInterfaceTemplateCount(): ?int { return $this->interface_template_count; }
    public function getFrontPortTemplateCount(): ?int { return $this->front_port_template_count; }
    public function getRearPortTemplateCount(): ?int { return $this->rear_port_template_count; }
    public function getDeviceBayTemplateCount(): ?int { return $this->device_bay_template_count; }
    public function getModuleBayTemplateCount(): ?int { return $this->module_bay_template_count; }
    public function getInventoryItemTemplateCount(): ?int { return $this->inventory_item_template_count; }
    public function getFrontImage() { return $this->front_image; }
    public function getRearImage() { return $this->rear_image; }
}
