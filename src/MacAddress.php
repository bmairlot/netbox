<?php

namespace Ancalagon\Netbox;

class MacAddress
{
    private const string ENDPOINT = '/dcim/mac-addresses/';

    // Writable fields
    private ?string $id = null;
    private string $mac_address = '';
    private ?string $assigned_object_type = null;
    private ?string $assigned_object_id = null;
    private string $description = '';
    private string $comments = '';
    private array $tags = [];
    private array $custom_fields = [];

    // Read-only fields
    private $assigned_object = null; // may be array|string|null depending on NetBox response
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
        if (empty($this->getMacAddress())) {
            throw new Exception("Missing mac_address for MacAddress");
        }

        $res = self::$client->post(self::ENDPOINT, $this->getAddParamArr());
        $this->loadFromApiResult($res);
    }

    /**
     * Read single (by id or mac_address)
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
        if (!empty($this->getMacAddress())) {
            $params['mac_address'] = $this->getMacAddress();
        }

        if (empty($params)) {
            throw new Exception("Can't load MacAddress without 'id' or 'mac_address'");
        }

        $res = self::$client->get(self::ENDPOINT, $params);

        if (($res['count'] ?? 0) === 0) {
            throw new Exception("MacAddress not found");
        }
        if (($res['count'] ?? 0) > 1) {
            throw new Exception("Multiple MacAddresses returned by query");
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
            throw new Exception("Can't edit MacAddress without 'id'");
        }

        $res = self::$client->put(self::ENDPOINT . $this->getId() . '/', $this->getEditParamArr());
        $this->loadFromApiResult($res);
    }

    /**
     * Partial update (PATCH)
     * @throws Exception
     */
    public function update(): void
    {
        if (is_null($this->getId())) {
            throw new Exception("Can't update MacAddress without 'id'");
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
            throw new Exception("Can't delete MacAddress without 'id'");
        }

        self::$client->delete(self::ENDPOINT . $this->getId() . '/');
        $this->setId(null);
    }

    // --- Private helpers ---

    private function getAddParamArr(): array
    {
        $params = [
            'mac_address' => $this->getMacAddress(),
        ];

        if (!is_null($this->getAssignedObjectType())) { $params['assigned_object_type'] = $this->getAssignedObjectType(); }
        if (!is_null($this->getAssignedObjectId())) { $params['assigned_object_id'] = $this->getAssignedObjectId(); }
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
        $this->setMacAddress((string)($res['mac_address'] ?? $this->getMacAddress()));
        $this->setAssignedObjectType($res['assigned_object_type'] ?? null);
        if (isset($res['assigned_object_id'])) {
            $this->setAssignedObjectId((string)$res['assigned_object_id']);
        }
        $this->setDescription((string)($res['description'] ?? ''));
        $this->setComments((string)($res['comments'] ?? ''));
        $this->setTags($res['tags'] ?? []);
        $this->setCustomFields($res['custom_fields'] ?? []);

        // Read-only fields
        $this->assigned_object = $res['assigned_object'] ?? null;
        $this->url = $res['url'] ?? null;
        $this->display_url = $res['display_url'] ?? null;
        $this->display = $res['display'] ?? null;
        $this->created = $res['created'] ?? null;
        $this->last_updated = $res['last_updated'] ?? null;
    }

    // --- Getters / Setters ---

    public function getId(): ?string { return $this->id; }
    public function setId(?string $id): MacAddress { $this->id = $id; return $this; }

    public function getMacAddress(): string { return $this->mac_address; }
    public function setMacAddress(string $mac_address): MacAddress { $this->mac_address = $mac_address; return $this; }

    public function getAssignedObjectType(): ?string { return $this->assigned_object_type; }
    public function setAssignedObjectType(?string $assigned_object_type): MacAddress { $this->assigned_object_type = $assigned_object_type; return $this; }

    public function getAssignedObjectId(): ?string { return $this->assigned_object_id; }
    public function setAssignedObjectId(?string $assigned_object_id): MacAddress { $this->assigned_object_id = $assigned_object_id; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): MacAddress { $this->description = $description; return $this; }

    public function getComments(): string { return $this->comments; }
    public function setComments(string $comments): MacAddress { $this->comments = $comments; return $this; }

    public function getTags(): array { return $this->tags; }
    public function setTags(array $tags): MacAddress { $this->tags = $tags; return $this; }

    public function getCustomFields(): array { return $this->custom_fields; }
    public function setCustomFields(array $custom_fields): MacAddress { $this->custom_fields = $custom_fields; return $this; }

    public function getAssignedObject() { return $this->assigned_object; }
    public function getUrl(): ?string { return $this->url; }
    public function getDisplayUrl(): ?string { return $this->display_url; }
    public function getDisplay(): ?string { return $this->display; }
    public function getCreated(): ?string { return $this->created; }
    public function getLastUpdated(): ?string { return $this->last_updated; }
}
