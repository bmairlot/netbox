<?php

namespace Ancalagon\Netbox;

class Cluster
{
    private const string ENDPOINT = '/virtualization/clusters/';

    // Writable fields
    private ?string $id = null;
    private string $name = '';          // required
    private ?string $type = null;       // required (FK to ClusterType)
    private ?string $group = null;      // FK to ClusterGroup
    private string $status = 'active';  // planned, staging, active, decommissioning, offline
    private ?string $tenant = null;
    private ?string $scope_type = null;
    private ?string $scope_id = null;
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
    private ?int $virtualmachine_count = null;
    private ?int $allocated_memory = null;
    private ?int $allocated_disk = null;
    private ?float $allocated_vcpus = null;
    private mixed $scope = null;

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
            throw new Exception("Missing name for Cluster");
        }
        if (is_null($this->getType())) {
            throw new Exception("Missing type for Cluster");
        }

        $res = self::$client->post(self::ENDPOINT, $this->getAddParamArr());
        $this->loadFromApiResult($res);
    }

    /**
     * Read single (by id, or list-filter by name)
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
            throw new Exception("Can't load Cluster without 'id' or 'name'");
        }

        $res = self::$client->get(self::ENDPOINT, $params);

        if (($res['count'] ?? 0) === 0) {
            throw new Exception("Cluster not found");
        }
        if (($res['count'] ?? 0) > 1) {
            throw new Exception("Multiple Clusters returned by query");
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
            throw new Exception("Can't edit Cluster without 'id'");
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
            throw new Exception("Can't update Cluster without 'id'");
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
            throw new Exception("Can't delete Cluster without 'id'");
        }

        self::$client->delete(self::ENDPOINT . $this->getId() . '/');
        $this->setId(null);
    }

    // --- Private helpers ---

    private function getAddParamArr(): array
    {
        $params = [
            'name' => $this->getName(),
            'type' => (int)$this->getType(),
            'status' => $this->getStatus(),
        ];

        if (!is_null($this->getGroup())) { $params['group'] = (int)$this->getGroup(); }
        if (!is_null($this->getTenant())) { $params['tenant'] = (int)$this->getTenant(); }
        if (!is_null($this->getScopeType())) { $params['scope_type'] = $this->getScopeType(); }
        if (!is_null($this->getScopeId())) { $params['scope_id'] = (int)$this->getScopeId(); }
        if (!is_null($this->getOwner())) { $params['owner'] = (int)$this->getOwner(); }
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
        $this->setId(isset($res['id']) ? (string)$res['id'] : null);
        $this->setName((string)($res['name'] ?? $this->getName()));

        if (isset($res['status'])) {
            $this->setStatus(is_array($res['status']) ? ($res['status']['value'] ?? 'active') : (string)$res['status']);
        }

        $this->setType(self::extractId($res['type'] ?? null));
        $this->setGroup(self::extractId($res['group'] ?? null));
        $this->setTenant(self::extractId($res['tenant'] ?? null));
        $this->setScopeType($res['scope_type'] ?? null);
        $this->setScopeId(self::extractId($res['scope_id'] ?? null));
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
        $this->virtualmachine_count = isset($res['virtualmachine_count']) ? (int)$res['virtualmachine_count'] : null;
        $this->allocated_memory = isset($res['allocated_memory']) ? (int)$res['allocated_memory'] : null;
        $this->allocated_disk = isset($res['allocated_disk']) ? (int)$res['allocated_disk'] : null;
        $this->allocated_vcpus = isset($res['allocated_vcpus']) ? (float)$res['allocated_vcpus'] : null;
        $this->scope = $res['scope'] ?? null;
    }

    private static function extractId($maybe): ?string
    {
        if (is_null($maybe) || $maybe === '') { return null; }
        if (is_array($maybe)) { return isset($maybe['id']) ? (string)$maybe['id'] : null; }
        return (string)$maybe;
    }

    // --- Getters / Setters ---

    public function getId(): ?string { return $this->id; }
    public function setId(?string $id): Cluster { $this->id = $id; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): Cluster { $this->name = $name; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(?string $type): Cluster { $this->type = $type; return $this; }

    public function getGroup(): ?string { return $this->group; }
    public function setGroup(?string $group): Cluster { $this->group = $group; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): Cluster { $this->status = $status; return $this; }

    public function getTenant(): ?string { return $this->tenant; }
    public function setTenant(?string $tenant): Cluster { $this->tenant = $tenant; return $this; }

    public function getScopeType(): ?string { return $this->scope_type; }
    public function setScopeType(?string $scopeType): Cluster { $this->scope_type = $scopeType; return $this; }

    public function getScopeId(): ?string { return $this->scope_id; }
    public function setScopeId(?string $scopeId): Cluster { $this->scope_id = $scopeId; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): Cluster { $this->description = $description; return $this; }

    public function getComments(): string { return $this->comments; }
    public function setComments(string $comments): Cluster { $this->comments = $comments; return $this; }

    public function getOwner(): ?string { return $this->owner; }
    public function setOwner(?string $owner): Cluster { $this->owner = $owner; return $this; }

    public function getTags(): array { return $this->tags; }
    public function setTags(array $tags): Cluster { $this->tags = $tags; return $this; }

    public function getCustomFields(): array { return $this->custom_fields; }
    public function setCustomFields(array $custom_fields): Cluster { $this->custom_fields = $custom_fields; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function getDisplayUrl(): ?string { return $this->display_url; }
    public function getDisplay(): ?string { return $this->display; }
    public function getCreated(): ?string { return $this->created; }
    public function getLastUpdated(): ?string { return $this->last_updated; }
    public function getDeviceCount(): ?int { return $this->device_count; }
    public function getVirtualmachineCount(): ?int { return $this->virtualmachine_count; }
    public function getAllocatedMemory(): ?int { return $this->allocated_memory; }
    public function getAllocatedDisk(): ?int { return $this->allocated_disk; }
    public function getAllocatedVcpus(): ?float { return $this->allocated_vcpus; }
    public function getScope(): mixed { return $this->scope; }
}
