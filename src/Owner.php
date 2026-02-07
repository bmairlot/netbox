<?php

namespace Ancalagon\Netbox;

use GuzzleHttp\Exception\GuzzleException;
use mkevenaar\NetBox\Client;

class Owner
{
    private ?string $id = null;
    private string $name = '';
    private ?string $group = null; // OwnerGroup id
    private string $description = '';
    private array $user_groups = []; // array of user group ids
    private array $users = []; // array of user ids
    private array $tags = [];
    private array $custom_fields = [];

    // Read-only/metadata
    private ?string $url = null;
    private ?string $display = null;
    private ?string $created = null;
    private ?string $last_updated = null;

    static private Client $client;

    public function __construct()
    {
        $this->setId(null);
        self::$client = new Client();
    }

    /**
     * Create (POST)
     * @throws CloudGenException
     */
    public function add(): void
    {
        if (empty($this->getName())) {
            throw new CloudGenException("Missing name for Owner");
        }

        try {
            $res = self::$client->getHttpClient()->post("/tenancy/owners/", $this->getAddParamArr());
            $this->loadFromApiResult($res);
        } catch (GuzzleException $e) {
            throw new CloudGenException("Couldn't create the Owner: {$e->getMessage()}");
        }
    }

    /**
     * Read single (by id or by name)
     * @throws CloudGenException
     */
    public function load(): void
    {
        try {
            if (!is_null($this->getId())) {
                $res = self::$client->getHttpClient()->get("/tenancy/owners/" . $this->getId() . "/", []);
                $this->loadFromApiResult($res);
                return;
            }

            if (!empty($this->getName())) {
                $res = self::$client->getHttpClient()->get("/tenancy/owners/", [
                    'name' => $this->getName(),
                ]);

                if (($res['count'] ?? 0) === 0) {
                    throw new CloudGenException("Owner not found for name='{$this->getName()}'");
                }
                if (($res['count'] ?? 0) > 1) {
                    throw new CloudGenException("Multiple Owner entries found for name='{$this->getName()}'");
                }
                $this->loadFromApiResult($res['results'][0]);
                return;
            }

            throw new CloudGenException("Can't load Owner without 'id' or 'name'");
        } catch (GuzzleException $e) {
            throw new CloudGenException("Couldn't load the Owner: {$e->getMessage()}");
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
            return self::$client->getHttpClient()->get("/tenancy/owners/", $filters);
        } catch (GuzzleException $e) {
            throw new CloudGenException("Couldn't list Owners: {$e->getMessage()}");
        }
    }

    /**
     * Replace (PUT)
     * @throws CloudGenException
     */
    public function edit(): void
    {
        if (is_null($this->getId())) {
            throw new CloudGenException("Can't edit Owner without 'id'");
        }
        try {
            $res = self::$client->getHttpClient()->put("/tenancy/owners/" . $this->getId() . "/", $this->getEditParamArr());
            $this->loadFromApiResult($res);
        } catch (GuzzleException $e) {
            throw new CloudGenException("Couldn't edit the Owner: {$e->getMessage()}");
        }
    }

    /**
     * Partial Update (PATCH)
     * @throws CloudGenException
     */
    public function update(): void
    {
        if (is_null($this->getId())) {
            throw new CloudGenException("Can't update Owner without 'id'");
        }
        try {
            $res = self::$client->getHttpClient()->patch("/tenancy/owners/" . $this->getId() . "/", $this->getEditParamArr());
            $this->loadFromApiResult($res);
        } catch (GuzzleException $e) {
            throw new CloudGenException("Couldn't update the Owner: {$e->getMessage()}");
        }
    }

    /**
     * Delete (DELETE)
     * @throws CloudGenException
     */
    public function delete(): void
    {
        if (is_null($this->getId())) {
            throw new CloudGenException("Can't delete Owner without 'id'");
        }
        try {
            self::$client->getHttpClient()->delete("/tenancy/owners/" . $this->getId() . "/", []);
            $this->setId(null);
        } catch (GuzzleException $e) {
            throw new CloudGenException("Couldn't delete the Owner: {$e->getMessage()}");
        }
    }

    // --- Helper operations ---

    /**
     * List all owners in a specific group
     * @param string $groupId
     * @return array
     * @throws CloudGenException
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
        ];

        if (!is_null($this->getGroup())) { $params['group'] = $this->getGroup(); }
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
        $this->setId(isset($res['id']) ? (string)$res['id'] : $this->getId());
        $this->setName((string)($res['name'] ?? $this->getName()));
        $this->setGroup(self::extractId($res['group'] ?? null));
        $this->setDescription((string)($res['description'] ?? $this->getDescription()));
        $this->setUserGroups(array_map('intval', $res['user_groups'] ?? $this->getUserGroups()));
        $this->setUsers(array_map('intval', $res['users'] ?? $this->getUsers()));
        $this->setTags($res['tags'] ?? $this->getTags());
        $this->setCustomFields($res['custom_fields'] ?? $this->getCustomFields());

        // Read-only
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
    public function getDisplay(): ?string { return $this->display; }
    public function getCreated(): ?string { return $this->created; }
    public function getLastUpdated(): ?string { return $this->last_updated; }
}
