<?php

namespace Ancalagon\Netbox;

class Prefix
{
    private const ENDPOINT = '/ipam/prefixes/';

    // Writable fields
    private ?string $id = null;
    private string $prefix = '';    // required
    private string $status = 'active'; // active, container, reserved, deprecated
    private string $description = '';
    private string $comments = '';
    private ?string $vrf = null;
    private ?string $tenant = null;
    private ?string $vlan = null;
    private ?string $role = null;
    private ?string $site = null;
    private ?string $scope_type = null;
    private ?string $scope_id = null;
    private ?string $owner = null;
    private bool $is_pool = false;
    private bool $mark_utilized = false;
    private array $tags = [];
    private array $custom_fields = [];

    // Read-only fields
    private ?string $url = null;
    private ?string $display_url = null;
    private ?string $display = null;
    private ?int $family = null;
    private ?int $children = null;
    private ?int $depth = null;
    private ?string $created = null;
    private ?string $last_updated = null;

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
        if (empty($this->getPrefix())) {
            throw new Exception("Missing prefix for Prefix");
        }

        $res = self::$client->post(self::ENDPOINT, $this->getAddParamArr());
        $this->loadFromApiResult($res);
    }

    /**
     * Read single (by id, or list-filter by prefix)
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
        if (!empty($this->getPrefix())) {
            $params['prefix'] = $this->getPrefix();
        }

        if (empty($params)) {
            throw new Exception("Can't load Prefix without 'id' or 'prefix'");
        }

        $res = self::$client->get(self::ENDPOINT, $params);

        if (($res['count'] ?? 0) === 0) {
            throw new Exception("Prefix not found");
        }
        if (($res['count'] ?? 0) > 1) {
            throw new Exception("Multiple Prefixes returned by query");
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
            throw new Exception("Can't edit Prefix without 'id'");
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
            throw new Exception("Can't update Prefix without 'id'");
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
            throw new Exception("Can't delete Prefix without 'id'");
        }

        self::$client->delete(self::ENDPOINT . $this->getId() . '/');
        $this->setId(null);
    }

    // --- Private helpers ---

    private function getAddParamArr(): array
    {
        $params = [
            'prefix' => $this->getPrefix(),
            'status' => $this->getStatus(),
            'is_pool' => $this->isPool(),
            'mark_utilized' => $this->isMarkUtilized(),
        ];

        if (!is_null($this->getVrf())) { $params['vrf'] = $this->getVrf(); }
        if (!is_null($this->getTenant())) { $params['tenant'] = $this->getTenant(); }
        if (!is_null($this->getVlan())) { $params['vlan'] = $this->getVlan(); }
        if (!is_null($this->getRole())) { $params['role'] = $this->getRole(); }
        if (!is_null($this->getSite())) { $params['site'] = $this->getSite(); }
        if (!is_null($this->getScopeType())) { $params['scope_type'] = $this->getScopeType(); }
        if (!is_null($this->getScopeId())) { $params['scope_id'] = $this->getScopeId(); }
        if (!is_null($this->getOwner())) { $params['owner'] = $this->getOwner(); }
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
        $this->setPrefix((string)($res['prefix'] ?? $this->getPrefix()));

        if (isset($res['status'])) {
            $this->setStatus(is_array($res['status']) ? ($res['status']['value'] ?? 'active') : (string)$res['status']);
        }

        $this->setDescription((string)($res['description'] ?? ''));
        $this->setComments((string)($res['comments'] ?? ''));
        $this->setVrf(self::extractId($res['vrf'] ?? null));
        $this->setTenant(self::extractId($res['tenant'] ?? null));
        $this->setVlan(self::extractId($res['vlan'] ?? null));
        $this->setRole(self::extractId($res['role'] ?? null));
        $this->setSite(self::extractId($res['site'] ?? null));
        $this->setScopeType($res['scope_type'] ?? null);
        $this->setScopeId(self::extractId($res['scope_id'] ?? null));
        $this->setOwner(self::extractId($res['owner'] ?? null));
        $this->setIsPool($res['is_pool'] ?? false);
        $this->setMarkUtilized($res['mark_utilized'] ?? false);
        $this->setTags($res['tags'] ?? []);
        $this->setCustomFields($res['custom_fields'] ?? []);

        // Read-only fields
        $this->url = $res['url'] ?? null;
        $this->display_url = $res['display_url'] ?? null;
        $this->display = $res['display'] ?? null;
        $this->family = isset($res['family']) ? (int)$res['family']['value'] : null;
        $this->children = isset($res['children']) ? (int)$res['children'] : null;
        $this->depth = isset($res['depth']) ? (int)$res['depth'] : null;
        $this->created = $res['created'] ?? null;
        $this->last_updated = $res['last_updated'] ?? null;
    }

    private static function extractId($maybe): ?string
    {
        if (is_null($maybe) || $maybe === '') { return null; }
        if (is_array($maybe)) { return isset($maybe['id']) ? (string)$maybe['id'] : null; }
        return (string)$maybe;
    }

    // --- Getters / Setters ---

    public function getId(): ?string { return $this->id; }
    public function setId(?string $id): Prefix { $this->id = $id; return $this; }

    public function getPrefix(): string { return $this->prefix; }
    public function setPrefix(string $prefix): Prefix { $this->prefix = $prefix; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): Prefix { $this->status = $status; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): Prefix { $this->description = $description; return $this; }

    public function getComments(): string { return $this->comments; }
    public function setComments(string $comments): Prefix { $this->comments = $comments; return $this; }

    public function getVrf(): ?string { return $this->vrf; }
    public function setVrf(?string $vrf): Prefix { $this->vrf = $vrf; return $this; }

    public function getTenant(): ?string { return $this->tenant; }
    public function setTenant(?string $tenant): Prefix { $this->tenant = $tenant; return $this; }

    public function getVlan(): ?string { return $this->vlan; }
    public function setVlan(?string $vlan): Prefix { $this->vlan = $vlan; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(?string $role): Prefix { $this->role = $role; return $this; }

    public function getSite(): ?string { return $this->site; }
    public function setSite(?string $site): Prefix { $this->site = $site; return $this; }

    public function getScopeType(): ?string { return $this->scope_type; }
    public function setScopeType(?string $scopeType): Prefix { $this->scope_type = $scopeType; return $this; }

    public function getScopeId(): ?string { return $this->scope_id; }
    public function setScopeId(?string $scopeId): Prefix { $this->scope_id = $scopeId; return $this; }

    public function getOwner(): ?string { return $this->owner; }
    public function setOwner(?string $owner): Prefix { $this->owner = $owner; return $this; }

    public function isPool(): bool { return $this->is_pool; }
    public function setIsPool(bool $isPool): Prefix { $this->is_pool = $isPool; return $this; }

    public function isMarkUtilized(): bool { return $this->mark_utilized; }
    public function setMarkUtilized(bool $markUtilized): Prefix { $this->mark_utilized = $markUtilized; return $this; }

    public function getTags(): array { return $this->tags; }
    public function setTags(array $tags): Prefix { $this->tags = $tags; return $this; }

    public function getCustomFields(): array { return $this->custom_fields; }
    public function setCustomFields(array $custom_fields): Prefix { $this->custom_fields = $custom_fields; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function getDisplayUrl(): ?string { return $this->display_url; }
    public function getDisplay(): ?string { return $this->display; }
    public function getFamily(): ?int { return $this->family; }
    public function getChildren(): ?int { return $this->children; }
    public function getDepth(): ?int { return $this->depth; }
    public function getCreated(): ?string { return $this->created; }
    public function getLastUpdated(): ?string { return $this->last_updated; }
}
