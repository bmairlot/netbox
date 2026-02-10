<?php

namespace Ancalagon\Netbox;

class DeviceRole
{
    private const string ENDPOINT = '/dcim/device-roles/';

    // Writable fields
    private ?string $id = null;
    private string $name = '';          // required
    private string $slug = '';          // required
    private string $color = '';
    private bool $vm_role = true;
    private ?string $config_template = null; // FK
    private string $description = '';
    private array $tags = [];
    private array $custom_fields = [];

    // Read-only fields
    private ?string $url = null;
    private ?string $display_url = null;
    private ?string $display = null;
    private ?string $created = null;
    private ?string $last_updated = null;
    private ?int $device_count = null;
    private ?int $virtualmachine_count = null;

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
            throw new Exception("Missing name for DeviceRole");
        }
        if (empty($this->getSlug())) {
            throw new Exception("Missing slug for DeviceRole");
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
            throw new Exception("Can't load DeviceRole without 'id', 'name' or 'slug'");
        }

        $res = self::$client->get(self::ENDPOINT, $params);

        if (($res['count'] ?? 0) === 0) {
            throw new Exception("DeviceRole not found");
        }
        if (($res['count'] ?? 0) > 1) {
            throw new Exception("Multiple DeviceRoles returned by query");
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
            throw new Exception("Can't edit DeviceRole without 'id'");
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
            throw new Exception("Can't update DeviceRole without 'id'");
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
            throw new Exception("Can't delete DeviceRole without 'id'");
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
            'vm_role' => $this->isVmRole(),
        ];

        if (!empty($this->getColor())) { $params['color'] = $this->getColor(); }
        if (!is_null($this->getConfigTemplate())) { $params['config_template'] = (int)$this->getConfigTemplate(); }
        if (!empty($this->getDescription())) { $params['description'] = $this->getDescription(); }
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
        $this->setColor((string)($res['color'] ?? ''));
        $this->setVmRole((bool)($res['vm_role'] ?? true));
        $this->setConfigTemplate(self::extractId($res['config_template'] ?? null));
        $this->setDescription((string)($res['description'] ?? ''));
        $this->setTags($res['tags'] ?? []);
        $this->setCustomFields($res['custom_fields'] ?? []);

        // Read-only fields
        $this->url = $res['url'] ?? null;
        $this->display_url = $res['display_url'] ?? null;
        $this->display = $res['display'] ?? null;
        $this->created = $res['created'] ?? null;
        $this->last_updated = $res['last_updated'] ?? null;
        $this->device_count = isset($res['device_count']) ? (int)$res['device_count'] : null;
        $this->virtualmachine_count = isset($res['virtualmachine_count']) ? (int)$res['virtualmachine_count'] : null;
    }

    private static function extractId($maybe): ?string
    {
        if (is_null($maybe) || $maybe === '') { return null; }
        if (is_array($maybe)) { return isset($maybe['id']) ? (string)$maybe['id'] : null; }
        return (string)$maybe;
    }

    // --- Getters / Setters ---

    public function getId(): ?string { return $this->id; }
    public function setId(?string $id): DeviceRole { $this->id = $id; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): DeviceRole { $this->name = $name; return $this; }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): DeviceRole { $this->slug = $slug; return $this; }

    public function getColor(): string { return $this->color; }
    public function setColor(string $color): DeviceRole { $this->color = $color; return $this; }

    public function isVmRole(): bool { return $this->vm_role; }
    public function setVmRole(bool $vm_role): DeviceRole { $this->vm_role = $vm_role; return $this; }

    public function getConfigTemplate(): ?string { return $this->config_template; }
    public function setConfigTemplate(?string $config_template): DeviceRole { $this->config_template = $config_template; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): DeviceRole { $this->description = $description; return $this; }

    public function getTags(): array { return $this->tags; }
    public function setTags(array $tags): DeviceRole { $this->tags = $tags; return $this; }

    public function getCustomFields(): array { return $this->custom_fields; }
    public function setCustomFields(array $custom_fields): DeviceRole { $this->custom_fields = $custom_fields; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function getDisplayUrl(): ?string { return $this->display_url; }
    public function getDisplay(): ?string { return $this->display; }
    public function getCreated(): ?string { return $this->created; }
    public function getLastUpdated(): ?string { return $this->last_updated; }
    public function getDeviceCount(): ?int { return $this->device_count; }
    public function getVirtualmachineCount(): ?int { return $this->virtualmachine_count; }
}
