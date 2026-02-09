<?php

namespace Ancalagon\Netbox;

class ClusterType
{
    private const string ENDPOINT = '/virtualization/cluster-types/';

    // Writable fields
    private ?string $id = null;
    private string $name = '';          // required
    private string $slug = '';          // required
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
    private ?int $cluster_count = null;

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
            throw new Exception("Missing name for ClusterType");
        }
        if (empty($this->getSlug())) {
            throw new Exception("Missing slug for ClusterType");
        }

        $res = self::$client->post(self::ENDPOINT, $this->getAddParamArr());
        $this->loadFromApiResult($res);
    }

    /**
     * Read single (by id, or list-filter by name/slug)
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
        if (!empty($this->getSlug())) {
            $params['slug'] = $this->getSlug();
        }

        if (empty($params)) {
            throw new Exception("Can't load ClusterType without 'id', 'name' or 'slug'");
        }

        $res = self::$client->get(self::ENDPOINT, $params);

        if (($res['count'] ?? 0) === 0) {
            throw new Exception("ClusterType not found");
        }
        if (($res['count'] ?? 0) > 1) {
            throw new Exception("Multiple ClusterTypes returned by query");
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
            throw new Exception("Can't edit ClusterType without 'id'");
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
            throw new Exception("Can't update ClusterType without 'id'");
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
            throw new Exception("Can't delete ClusterType without 'id'");
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
        $this->setName((string)($res['name'] ?? $this->getName()));
        $this->setSlug((string)($res['slug'] ?? $this->getSlug()));
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
        $this->cluster_count = isset($res['cluster_count']) ? (int)$res['cluster_count'] : null;
    }

    private static function extractId($maybe): ?string
    {
        if (is_null($maybe) || $maybe === '') { return null; }
        if (is_array($maybe)) { return isset($maybe['id']) ? (string)$maybe['id'] : null; }
        return (string)$maybe;
    }

    // --- Getters / Setters ---

    public function getId(): ?string { return $this->id; }
    public function setId(?string $id): ClusterType { $this->id = $id; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): ClusterType { $this->name = $name; return $this; }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): ClusterType { $this->slug = $slug; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): ClusterType { $this->description = $description; return $this; }

    public function getComments(): string { return $this->comments; }
    public function setComments(string $comments): ClusterType { $this->comments = $comments; return $this; }

    public function getOwner(): ?string { return $this->owner; }
    public function setOwner(?string $owner): ClusterType { $this->owner = $owner; return $this; }

    public function getTags(): array { return $this->tags; }
    public function setTags(array $tags): ClusterType { $this->tags = $tags; return $this; }

    public function getCustomFields(): array { return $this->custom_fields; }
    public function setCustomFields(array $custom_fields): ClusterType { $this->custom_fields = $custom_fields; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function getDisplayUrl(): ?string { return $this->display_url; }
    public function getDisplay(): ?string { return $this->display; }
    public function getCreated(): ?string { return $this->created; }
    public function getLastUpdated(): ?string { return $this->last_updated; }
    public function getClusterCount(): ?int { return $this->cluster_count; }
}
