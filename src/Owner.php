<?php

namespace Ancalagon\Netbox;

class Owner
{
    private const string ENDPOINT = '/users/owners/';

    // Writable fields
    private ?string $id = null;
    private string $name = '';
    private ?string $group = null; // OwnerGroup id
    private string $description = '';
    private array $user_groups = []; // array of user group ids
    private array $users = []; // array of user ids
    private array $tags = [];
    private array $custom_fields = [];

    // Read-only fields
    private ?string $url = null;
    private ?string $display_url = null;
    private ?string $display = null;
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
        if (empty($this->getName())) {
            throw new Exception("Missing name for Owner");
        }
        if (is_null($this->getGroup())) {
            throw new Exception("Missing group for Owner");
        }

        $res = self::$client->post(self::ENDPOINT, $this->getAddParamArr());
        $this->loadFromApiResult($res);
    }

    /**
     * Read single (by id or by name)
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
            throw new Exception("Can't load Owner without 'id' or 'name'");
        }

        $res = self::$client->get(self::ENDPOINT, $params);

        if (($res['count'] ?? 0) === 0) {
            throw new Exception("Owner not found");
        }
        if (($res['count'] ?? 0) > 1) {
            throw new Exception("Multiple Owners returned by query");
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
            throw new Exception("Can't edit Owner without 'id'");
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
            throw new Exception("Can't update Owner without 'id'");
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
            throw new Exception("Can't delete Owner without 'id'");
        }

        self::$client->delete(self::ENDPOINT . $this->getId() . '/');
        $this->setId(null);
    }

    // --- Helper operations ---

    /**
     * List all owners in a specific group
     * @param string $groupId
     * @return array
     * @throws Exception
     */
    public function listByGroup(string $groupId): array
    {
        return $this->list(['group_id' => $groupId]);
    }

    // --- Private helpers ---

    private function getAddParamArr(): array
    {
        $params = [
            'name' => $this->getName(),
            'group' => (int)$this->getGroup(),
        ];
        if (!empty($this->getDescription())) { $params['description'] = $this->getDescription(); }
        if (!empty($this->getUserGroups())) { $params['user_groups'] = $this->getUserGroups(); }
        if (!empty($this->getUsers())) { $params['users'] = $this->getUsers(); }
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
        $this->setGroup(self::extractId($res['group'] ?? null));
        $this->setDescription((string)($res['description'] ?? ''));
        $this->setUserGroups(array_map('intval', $res['user_groups'] ?? []));
        $this->setUsers(array_map('intval', $res['users'] ?? []));
        $this->setTags($res['tags'] ?? []);
        $this->setCustomFields($res['custom_fields'] ?? []);

        // Read-only fields
        $this->url = $res['url'] ?? null;
        $this->display_url = $res['display_url'] ?? null;
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

    // --- Getters / Setters ---

    public function getId(): ?string { return $this->id; }
    public function setId(?string $id): Owner { $this->id = $id; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): Owner { $this->name = $name; return $this; }

    public function getGroup(): ?string { return $this->group; }
    public function setGroup(?string $group): Owner { $this->group = $group; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): Owner { $this->description = $description; return $this; }

    public function getUserGroups(): array { return $this->user_groups; }
    public function setUserGroups(array $user_groups): Owner { $this->user_groups = array_map('intval', $user_groups); return $this; }

    public function getUsers(): array { return $this->users; }
    public function setUsers(array $users): Owner { $this->users = array_map('intval', $users); return $this; }

    public function getTags(): array { return $this->tags; }
    public function setTags(array $tags): Owner { $this->tags = $tags; return $this; }

    public function getCustomFields(): array { return $this->custom_fields; }
    public function setCustomFields(array $custom_fields): Owner { $this->custom_fields = $custom_fields; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function getDisplayUrl(): ?string { return $this->display_url; }
    public function getDisplay(): ?string { return $this->display; }
    public function getCreated(): ?string { return $this->created; }
    public function getLastUpdated(): ?string { return $this->last_updated; }
}
