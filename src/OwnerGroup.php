<?php

namespace Ancalagon\Netbox;

use GuzzleHttp\Exception\GuzzleException;
use mkevenaar\NetBox\Client;

class OwnerGroup
{
    private ?string $id = null;
    private string $name = '';
    private string $description = '';
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
     * @throws Exception
     */
    public function add(): void
    {
        if (empty($this->getName())) {
            throw new Exception("Missing name for OwnerGroup");
        }

        try {
            $res = self::$client->getHttpClient()->post("/tenancy/owner-groups/", $this->getAddParamArr());
            $this->loadFromApiResult($res);
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't create the Owner Group: {$e->getMessage()}");
        }
    }

    /**
     * Read single (by id or by name)
     * @throws Exception
     */
    public function load(): void
    {
        try {
            if (!is_null($this->getId())) {
                $res = self::$client->getHttpClient()->get("/tenancy/owner-groups/" . $this->getId() . "/", []);
                $this->loadFromApiResult($res);
                return;
            }

            if (!empty($this->getName())) {
                $res = self::$client->getHttpClient()->get("/tenancy/owner-groups/", [
                    'name' => $this->getName(),
                ]);

                if (($res['count'] ?? 0) === 0) {
                    throw new Exception("OwnerGroup not found for name='{$this->getName()}'");
                }
                if (($res['count'] ?? 0) > 1) {
                    throw new Exception("Multiple OwnerGroup entries found for name='{$this->getName()}'");
                }
                $this->loadFromApiResult($res['results'][0]);
                return;
            }

            throw new Exception("Can't load OwnerGroup without 'id' or 'name'");
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't load the Owner Group: {$e->getMessage()}");
        }
    }

    /**
     * List with optional filters
     * @param array $filters
     * @return array
     * @throws Exception
     */
    public function list(array $filters = []): array
    {
        try {
            return self::$client->getHttpClient()->get("/tenancy/owner-groups/", $filters);
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't list Owner Groups: {$e->getMessage()}");
        }
    }

    /**
     * Replace (PUT)
     * @throws Exception
     */
    public function edit(): void
    {
        if (is_null($this->getId())) {
            throw new Exception("Can't edit OwnerGroup without 'id'");
        }
        try {
            $res = self::$client->getHttpClient()->put("/tenancy/owner-groups/" . $this->getId() . "/", $this->getEditParamArr());
            $this->loadFromApiResult($res);
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't edit the Owner Group: {$e->getMessage()}");
        }
    }

    /**
     * Partial Update (PATCH)
     * @throws Exception
     */
    public function update(): void
    {
        if (is_null($this->getId())) {
            throw new Exception("Can't update OwnerGroup without 'id'");
        }
        try {
            $res = self::$client->getHttpClient()->patch("/tenancy/owner-groups/" . $this->getId() . "/", $this->getEditParamArr());
            $this->loadFromApiResult($res);
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't update the Owner Group: {$e->getMessage()}");
        }
    }

    /**
     * Delete (DELETE)
     * @throws Exception
     */
    public function delete(): void
    {
        if (is_null($this->getId())) {
            throw new Exception("Can't delete OwnerGroup without 'id'");
        }
        try {
            self::$client->getHttpClient()->delete("/tenancy/owner-groups/" . $this->getId() . "/", []);
            $this->setId(null);
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't delete the Owner Group: {$e->getMessage()}");
        }
    }

    // --- Private helpers ---

    private function getAddParamArr(): array
    {
        $params = [
            'name' => $this->getName(),
        ];

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
        $this->setId(isset($res['id']) ? (string)$res['id'] : $this->getId());
        $this->setName((string)($res['name'] ?? $this->getName()));
        $this->setDescription((string)($res['description'] ?? $this->getDescription()));
        $this->setTags($res['tags'] ?? $this->getTags());
        $this->setCustomFields($res['custom_fields'] ?? $this->getCustomFields());

        // Read-only
        $this->url = $res['url'] ?? $this->url;
        $this->display = $res['display'] ?? $this->display;
        $this->created = $res['created'] ?? $this->created;
        $this->last_updated = $res['last_updated'] ?? $this->last_updated;
    }

    // --- Getters/Setters ---

    public function getId(): ?string { return $this->id; }
    public function setId(?string $id): OwnerGroup { $this->id = $id; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): OwnerGroup { $this->name = $name; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): OwnerGroup { $this->description = $description; return $this; }

    public function getTags(): array { return $this->tags; }
    public function setTags(array $tags): OwnerGroup { $this->tags = $tags; return $this; }

    public function getCustomFields(): array { return $this->custom_fields; }
    public function setCustomFields(array $custom_fields): OwnerGroup { $this->custom_fields = $custom_fields; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function getDisplay(): ?string { return $this->display; }
    public function getCreated(): ?string { return $this->created; }
    public function getLastUpdated(): ?string { return $this->last_updated; }
}
