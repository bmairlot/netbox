<?php

namespace Ancalagon\Netbox;

use GuzzleHttp\Exception\GuzzleException;
use mkevenaar\NetBox\Client;

class MacAddress
{
    private ?string $id = 'null';
    private string $mac_address = '';
    private ?string $assigned_object_type = null;
    private ?string $assigned_object_id = null; // Keep string for consistency with other classes
    private $assigned_object = null; // may be array|string|null depending on NetBox response
    private string $description = '';
    private string $comments = '';
    private array $tags = [];
    private array $custom_fields = [];
    private ?string $url = null;
    private ?string $display_url = null;
    private ?string $display = null;
    private ?string $created = null;
    private ?string $last_updated = null;

    static private Client $client;

    public function __construct()
    {
        // New object has no ID until created
        $this->setId(null);
        self::$client = new Client();
    }

    /**
     * Create (POST)
     * @throws CloudGenException
     */
    public function add(): void
    {
        try {
            $res = self::$client->getHttpClient()->post("/dcim/mac-addresses/", $this->getAddParamArr());
            $this->loadFromApiResult($res);
        } catch (GuzzleException $e) {
            throw new CloudGenException("Couldn't create the Mac Address: {$e->getMessage()}");
        }
    }

    /**
     * Read single (by id or mac_address)
     * @throws CloudGenException
     */
    public function load(): void
    {
        try {
            if (!is_null($this->getId())) {
                $res = self::$client->getHttpClient()->get("/dcim/mac-addresses/" . $this->getId() . "/", []);
                $this->loadFromApiResult($res);
                return;
            }

            if (!empty($this->getMacAddress())) {
                $res = self::$client->getHttpClient()->get("/dcim/mac-addresses/", [
                    'mac_address' => $this->getMacAddress(),
                ]);

                if (($res['count'] ?? 0) === 0) {
                    throw new CloudGenException("MacAddress not found for mac_address='{$this->getMacAddress()}'");
                }
                if (($res['count'] ?? 0) > 1) {
                    throw new CloudGenException("Multiple MacAddress entries found for mac_address='{$this->getMacAddress()}'");
                }
                $this->loadFromApiResult($res['results'][0]);
                return;
            }

            throw new CloudGenException("Can't load MacAddress without 'id' or 'mac_address'");
        } catch (GuzzleException $e) {
            throw new CloudGenException("Couldn't load the Mac Address: {$e->getMessage()}");
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
            return self::$client->getHttpClient()->get("/dcim/mac-addresses/", $filters);
        } catch (GuzzleException $e) {
            throw new CloudGenException("Couldn't list Mac Addresses: {$e->getMessage()}");
        }
    }

    /**
     * Replace (PUT)
     * @throws CloudGenException
     */
    public function edit(): void
    {
        if (is_null($this->getId())) {
            throw new CloudGenException("Can't edit MacAddress without 'id'");
        }
        try {
            $res = self::$client->getHttpClient()->put("/dcim/mac-addresses/" . $this->getId() . "/", $this->getEditParamArr());
            $this->loadFromApiResult($res);
        } catch (GuzzleException $e) {
            throw new CloudGenException("Couldn't edit the Mac Address: {$e->getMessage()}");
        }
    }

    /**
     * Partial update (PATCH)
     * @throws CloudGenException
     */
    public function update(): void
    {
        if (is_null($this->getId())) {
            throw new CloudGenException("Can't update MacAddress without 'id'");
        }
        try {
            $res = self::$client->getHttpClient()->patch("/dcim/mac-addresses/" . $this->getId() . "/", $this->getEditParamArr());
            $this->loadFromApiResult($res);
        } catch (GuzzleException $e) {
            throw new CloudGenException("Couldn't update the Mac Address: {$e->getMessage()}");
        }
    }

    /**
     * Delete (DELETE)
     * @throws CloudGenException
     */
    public function delete(): void
    {
        if (is_null($this->getId())) {
            throw new CloudGenException("Can't delete MacAddress without 'id'");
        }
        try {
            self::$client->getHttpClient()->delete("/dcim/mac-addresses/" . $this->getId() . "/", []);
            $this->setId(null);
        } catch (GuzzleException $e) {
            throw new CloudGenException("Couldn't delete the Mac Address: {$e->getMessage()}");
        }
    }

    // --- Helpers ---
    private function getAddParamArr(): array
    {
        $params = [
            'mac_address' => $this->getMacAddress(),
        ];

        if (!is_null($this->getAssignedObjectType())) {
            $params['assigned_object_type'] = $this->getAssignedObjectType();
        }
        if (!is_null($this->getAssignedObjectId())) {
            $params['assigned_object_id'] = $this->getAssignedObjectId();
        }
        if (!empty($this->getDescription())) {
            $params['description'] = $this->getDescription();
        }
        if (!empty($this->getComments())) {
            $params['comments'] = $this->getComments();
        }
        if (!empty($this->getTags())) {
            $params['tags'] = $this->getTags();
        }
        if (!empty($this->getCustomFields())) {
            $params['custom_fields'] = $this->getCustomFields();
        }

        return $params;
    }

    private function getEditParamArr(): array
    {
        // For edit/update we allow same fields as add
        return $this->getAddParamArr();
    }

    private function loadFromApiResult(array $result): void
    {
        $this->setId(isset($result['id']) ? (string)$result['id'] : $this->getId());
        $this->setMacAddress($result['mac_address'] ?? $this->getMacAddress());
        $this->setAssignedObjectType($result['assigned_object_type'] ?? $this->getAssignedObjectType());
        if (isset($result['assigned_object_id'])) {
            $this->setAssignedObjectId((string)$result['assigned_object_id']);
        }
        $this->setAssignedObject($result['assigned_object'] ?? $this->getAssignedObject());
        $this->setDescription($result['description'] ?? $this->getDescription());
        $this->setComments($result['comments'] ?? $this->getComments());
        $this->setTags($result['tags'] ?? $this->getTags());
        $this->setCustomFields($result['custom_fields'] ?? $this->getCustomFields());
        $this->setUrl($result['url'] ?? $this->getUrl());
        $this->setDisplayUrl($result['display_url'] ?? $this->getDisplayUrl());
        $this->setDisplay($result['display'] ?? $this->getDisplay());
        $this->setCreated($result['created'] ?? $this->getCreated());
        $this->setLastUpdated($result['last_updated'] ?? $this->getLastUpdated());
    }

    // --- Getters/Setters ---
    public function getId(): ?string { return $this->id; }
    public function setId(?string $id): MacAddress { $this->id = $id; return $this; }

    public function getMacAddress(): string { return $this->mac_address; }
    public function setMacAddress(string $mac_address): MacAddress { $this->mac_address = $mac_address; return $this; }

    public function getAssignedObjectType(): ?string { return $this->assigned_object_type; }
    public function setAssignedObjectType(?string $assigned_object_type): MacAddress { $this->assigned_object_type = $assigned_object_type; return $this; }

    public function getAssignedObjectId(): ?string { return $this->assigned_object_id; }
    public function setAssignedObjectId(?string $assigned_object_id): MacAddress { $this->assigned_object_id = $assigned_object_id; return $this; }

    public function getAssignedObject() { return $this->assigned_object; }
    public function setAssignedObject($assigned_object): MacAddress { $this->assigned_object = $assigned_object; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): MacAddress { $this->description = $description; return $this; }

    public function getComments(): string { return $this->comments; }
    public function setComments(string $comments): MacAddress { $this->comments = $comments; return $this; }

    public function getTags(): array { return $this->tags; }
    public function setTags(array $tags): MacAddress { $this->tags = $tags; return $this; }

    public function getCustomFields(): array { return $this->custom_fields; }
    public function setCustomFields(array $custom_fields): MacAddress { $this->custom_fields = $custom_fields; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function setUrl(?string $url): MacAddress { $this->url = $url; return $this; }

    public function getDisplayUrl(): ?string { return $this->display_url; }
    public function setDisplayUrl(?string $display_url): MacAddress { $this->display_url = $display_url; return $this; }

    public function getDisplay(): ?string { return $this->display; }
    public function setDisplay(?string $display): MacAddress { $this->display = $display; return $this; }

    public function getCreated(): ?string { return $this->created; }
    public function setCreated(?string $created): MacAddress { $this->created = $created; return $this; }

    public function getLastUpdated(): ?string { return $this->last_updated; }
    public function setLastUpdated(?string $last_updated): MacAddress { $this->last_updated = $last_updated; return $this; }
}
