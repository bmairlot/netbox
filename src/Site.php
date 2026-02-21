<?php

namespace Ancalagon\Netbox;

class Site
{
    private const string ENDPOINT = '/dcim/sites/';

    // Writable fields
    private ?string $id = null;
    private string $name = '';              // required
    private string $slug = '';              // required
    private string $status = 'active';      // planned, staging, active, decommissioning, retired
    private ?string $region = null;         // FK
    private ?string $group = null;          // FK (SiteGroup)
    private ?string $tenant = null;         // FK
    private string $facility = '';
    private ?string $time_zone = null;
    private string $description = '';
    private string $physical_address = '';
    private string $shipping_address = '';
    private ?float $latitude = null;
    private ?float $longitude = null;
    private ?string $owner = null;          // FK
    private string $comments = '';
    private array $asns = [];
    private array $tags = [];
    private array $custom_fields = [];

    // Read-only fields
    private ?string $url = null;
    private ?string $display_url = null;
    private ?string $display = null;
    private ?string $created = null;
    private ?string $last_updated = null;
    private ?int $circuit_count = null;
    private ?int $device_count = null;
    private ?int $prefix_count = null;
    private ?int $rack_count = null;
    private ?int $virtualmachine_count = null;
    private ?int $vlan_count = null;

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
            throw new Exception("Missing name for Site");
        }
        if (empty($this->getSlug())) {
            throw new Exception("Missing slug for Site");
        }

        $res = self::$client->post(self::ENDPOINT, $this->getAddParamArr());
        $this->loadFromApiResult($res);
    }

    /**
     * Read single (by id, name, or slug)
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
        } elseif (!empty($this->getSlug())) {
            $params['slug'] = $this->getSlug();
        }

        if (empty($params)) {
            throw new Exception("Can't load Site without 'id', 'name', or 'slug'");
        }

        $res = self::$client->get(self::ENDPOINT, $params);

        if (($res['count'] ?? 0) === 0) {
            throw new Exception("Site not found");
        }
        if (($res['count'] ?? 0) > 1) {
            throw new Exception("Multiple Sites returned by query");
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
            throw new Exception("Can't edit Site without 'id'");
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
            throw new Exception("Can't update Site without 'id'");
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
            throw new Exception("Can't delete Site without 'id'");
        }

        self::$client->delete(self::ENDPOINT . $this->getId() . '/');
        $this->setId(null);
    }

    // --- Private helpers ---

    private function getAddParamArr(): array
    {
        $params = [
            'name' => $this->getName(),
            'slug' => $this->getSlug(),
            'status' => $this->getStatus(),
        ];

        if (!is_null($this->getRegion())) { $params['region'] = (int)$this->getRegion(); }
        if (!is_null($this->getGroup())) { $params['group'] = (int)$this->getGroup(); }
        if (!is_null($this->getTenant())) { $params['tenant'] = (int)$this->getTenant(); }
        if (!empty($this->getFacility())) { $params['facility'] = $this->getFacility(); }
        if (!is_null($this->getTimeZone())) { $params['time_zone'] = $this->getTimeZone(); }
        if (!empty($this->getDescription())) { $params['description'] = $this->getDescription(); }
        if (!empty($this->getPhysicalAddress())) { $params['physical_address'] = $this->getPhysicalAddress(); }
        if (!empty($this->getShippingAddress())) { $params['shipping_address'] = $this->getShippingAddress(); }
        if (!is_null($this->getLatitude())) { $params['latitude'] = $this->getLatitude(); }
        if (!is_null($this->getLongitude())) { $params['longitude'] = $this->getLongitude(); }
        if (!is_null($this->getOwner())) { $params['owner'] = (int)$this->getOwner(); }
        if (!empty($this->getComments())) { $params['comments'] = $this->getComments(); }
        if (!empty($this->getAsns())) { $params['asns'] = $this->getAsns(); }
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
        $this->setSlug((string)($res['slug'] ?? $this->getSlug()));

        if (isset($res['status'])) {
            $this->setStatus(is_array($res['status']) ? ($res['status']['value'] ?? 'active') : (string)$res['status']);
        }

        $this->setRegion(self::extractId($res['region'] ?? null));
        $this->setGroup(self::extractId($res['group'] ?? null));
        $this->setTenant(self::extractId($res['tenant'] ?? null));
        $this->setFacility((string)($res['facility'] ?? ''));
        $this->setTimeZone($res['time_zone'] ?? null);
        $this->setDescription((string)($res['description'] ?? ''));
        $this->setPhysicalAddress((string)($res['physical_address'] ?? ''));
        $this->setShippingAddress((string)($res['shipping_address'] ?? ''));
        $this->setLatitude(isset($res['latitude']) ? (float)$res['latitude'] : null);
        $this->setLongitude(isset($res['longitude']) ? (float)$res['longitude'] : null);
        $this->setOwner(self::extractId($res['owner'] ?? null));
        $this->setComments((string)($res['comments'] ?? ''));
        $this->setAsns($res['asns'] ?? []);
        $this->setTags($res['tags'] ?? []);
        $this->setCustomFields($res['custom_fields'] ?? []);

        // Read-only fields
        $this->url = $res['url'] ?? null;
        $this->display_url = $res['display_url'] ?? null;
        $this->display = $res['display'] ?? null;
        $this->created = $res['created'] ?? null;
        $this->last_updated = $res['last_updated'] ?? null;
        $this->circuit_count = isset($res['circuit_count']) ? (int)$res['circuit_count'] : null;
        $this->device_count = isset($res['device_count']) ? (int)$res['device_count'] : null;
        $this->prefix_count = isset($res['prefix_count']) ? (int)$res['prefix_count'] : null;
        $this->rack_count = isset($res['rack_count']) ? (int)$res['rack_count'] : null;
        $this->virtualmachine_count = isset($res['virtualmachine_count']) ? (int)$res['virtualmachine_count'] : null;
        $this->vlan_count = isset($res['vlan_count']) ? (int)$res['vlan_count'] : null;
    }

    private static function extractId($maybe): ?string
    {
        if (is_null($maybe) || $maybe === '') { return null; }
        if (is_array($maybe)) { return isset($maybe['id']) ? (string)$maybe['id'] : null; }
        return (string)$maybe;
    }

    // --- Getters / Setters ---

    public function getId(): ?string { return $this->id; }
    public function setId(?string $id): Site { $this->id = $id; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): Site { $this->name = $name; return $this; }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): Site { $this->slug = $slug; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): Site { $this->status = $status; return $this; }

    public function getRegion(): ?string { return $this->region; }
    public function setRegion(?string $region): Site { $this->region = $region; return $this; }

    public function getGroup(): ?string { return $this->group; }
    public function setGroup(?string $group): Site { $this->group = $group; return $this; }

    public function getTenant(): ?string { return $this->tenant; }
    public function setTenant(?string $tenant): Site { $this->tenant = $tenant; return $this; }

    public function getFacility(): string { return $this->facility; }
    public function setFacility(string $facility): Site { $this->facility = $facility; return $this; }

    public function getTimeZone(): ?string { return $this->time_zone; }
    public function setTimeZone(?string $time_zone): Site { $this->time_zone = $time_zone; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): Site { $this->description = $description; return $this; }

    public function getPhysicalAddress(): string { return $this->physical_address; }
    public function setPhysicalAddress(string $physical_address): Site { $this->physical_address = $physical_address; return $this; }

    public function getShippingAddress(): string { return $this->shipping_address; }
    public function setShippingAddress(string $shipping_address): Site { $this->shipping_address = $shipping_address; return $this; }

    public function getLatitude(): ?float { return $this->latitude; }
    public function setLatitude(?float $latitude): Site { $this->latitude = $latitude; return $this; }

    public function getLongitude(): ?float { return $this->longitude; }
    public function setLongitude(?float $longitude): Site { $this->longitude = $longitude; return $this; }

    public function getOwner(): ?string { return $this->owner; }
    public function setOwner(?string $owner): Site { $this->owner = $owner; return $this; }

    public function getComments(): string { return $this->comments; }
    public function setComments(string $comments): Site { $this->comments = $comments; return $this; }

    public function getAsns(): array { return $this->asns; }
    public function setAsns(array $asns): Site { $this->asns = $asns; return $this; }

    public function getTags(): array { return $this->tags; }
    public function setTags(array $tags): Site { $this->tags = $tags; return $this; }

    public function getCustomFields(): array { return $this->custom_fields; }
    public function setCustomFields(array $custom_fields): Site { $this->custom_fields = $custom_fields; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function getDisplayUrl(): ?string { return $this->display_url; }
    public function getDisplay(): ?string { return $this->display; }
    public function getCreated(): ?string { return $this->created; }
    public function getLastUpdated(): ?string { return $this->last_updated; }
    public function getCircuitCount(): ?int { return $this->circuit_count; }
    public function getDeviceCount(): ?int { return $this->device_count; }
    public function getPrefixCount(): ?int { return $this->prefix_count; }
    public function getRackCount(): ?int { return $this->rack_count; }
    public function getVirtualmachineCount(): ?int { return $this->virtualmachine_count; }
    public function getVlanCount(): ?int { return $this->vlan_count; }
}