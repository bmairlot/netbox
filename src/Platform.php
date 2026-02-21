<?php

namespace Ancalagon\Netbox;

class Platform
{
    private const string ENDPOINT = '/dcim/platforms/';

    // Writable fields
    private ?string $id = null;
    private string $name = '';              // required
    private string $slug = '';              // required
    private ?string $parent = null;         // FK to parent Platform
    private ?string $manufacturer = null;   // FK
    private ?string $config_template = null; // FK
    private string $description = '';
    private ?string $owner = null;          // FK
    private string $comments = '';
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
    private ?int $_depth = null;

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
            throw new Exception("Missing name for Platform");
        }
        if (empty($this->getSlug())) {
            throw new Exception("Missing slug for Platform");
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
            throw new Exception("Can't load Platform without 'id', 'name', or 'slug'");
        }

        $res = self::$client->get(self::ENDPOINT, $params);

        if (($res['count'] ?? 0) === 0) {
            throw new Exception("Platform not found");
        }
        if (($res['count'] ?? 0) > 1) {
            throw new Exception("Multiple Platforms returned by query");
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
            throw new Exception("Can't edit Platform without 'id'");
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
            throw new Exception("Can't update Platform without 'id'");
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
            throw new Exception("Can't delete Platform without 'id'");
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
        ];

        if (!is_null($this->getParent())) { $params['parent'] = (int)$this->getParent(); }
        if (!is_null($this->getManufacturer())) { $params['manufacturer'] = (int)$this->getManufacturer(); }
        if (!is_null($this->getConfigTemplate())) { $params['config_template'] = (int)$this->getConfigTemplate(); }
        if (!empty($this->getDescription())) { $params['description'] = $this->getDescription(); }
        if (!is_null($this->getOwner())) { $params['owner'] = (int)$this->getOwner(); }
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
        $this->setId(isset($res['id']) ? (string)$res['id'] : null);
        $this->setName((string)($res['name'] ?? $this->getName()));
        $this->setSlug((string)($res['slug'] ?? $this->getSlug()));
        $this->setParent(self::extractId($res['parent'] ?? null));
        $this->setManufacturer(self::extractId($res['manufacturer'] ?? null));
        $this->setConfigTemplate(self::extractId($res['config_template'] ?? null));
        $this->setDescription((string)($res['description'] ?? ''));
        $this->setOwner(self::extractId($res['owner'] ?? null));
        $this->setComments((string)($res['comments'] ?? ''));
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
        $this->_depth = isset($res['_depth']) ? (int)$res['_depth'] : null;
    }

    private static function extractId($maybe): ?string
    {
        if (is_null($maybe) || $maybe === '') { return null; }
        if (is_array($maybe)) { return isset($maybe['id']) ? (string)$maybe['id'] : null; }
        return (string)$maybe;
    }

    // --- Getters / Setters ---

    public function getId(): ?string { return $this->id; }
    public function setId(?string $id): Platform { $this->id = $id; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): Platform { $this->name = $name; return $this; }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): Platform { $this->slug = $slug; return $this; }

    public function getParent(): ?string { return $this->parent; }
    public function setParent(?string $parent): Platform { $this->parent = $parent; return $this; }

    public function getManufacturer(): ?string { return $this->manufacturer; }
    public function setManufacturer(?string $manufacturer): Platform { $this->manufacturer = $manufacturer; return $this; }

    public function getConfigTemplate(): ?string { return $this->config_template; }
    public function setConfigTemplate(?string $config_template): Platform { $this->config_template = $config_template; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): Platform { $this->description = $description; return $this; }

    public function getOwner(): ?string { return $this->owner; }
    public function setOwner(?string $owner): Platform { $this->owner = $owner; return $this; }

    public function getComments(): string { return $this->comments; }
    public function setComments(string $comments): Platform { $this->comments = $comments; return $this; }

    public function getTags(): array { return $this->tags; }
    public function setTags(array $tags): Platform { $this->tags = $tags; return $this; }

    public function getCustomFields(): array { return $this->custom_fields; }
    public function setCustomFields(array $custom_fields): Platform { $this->custom_fields = $custom_fields; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function getDisplayUrl(): ?string { return $this->display_url; }
    public function getDisplay(): ?string { return $this->display; }
    public function getCreated(): ?string { return $this->created; }
    public function getLastUpdated(): ?string { return $this->last_updated; }
    public function getDeviceCount(): ?int { return $this->device_count; }
    public function getVirtualmachineCount(): ?int { return $this->virtualmachine_count; }
    public function getDepth(): ?int { return $this->_depth; }
}
