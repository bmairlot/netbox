<?php

namespace Ancalagon\Netbox;

class Vlan
{
    private const string ENDPOINT = '/ipam/vlans/';

    // Writable fields
    private ?string $id = null;
    private ?int $vid = null;       // required, 1–4094
    private string $name = '';      // required
    private string $status = 'active'; // active, reserved, deprecated
    private string $description = '';
    private string $comments = '';
    private ?string $site = null;
    private ?string $group = null;
    private ?string $tenant = null;
    private ?string $role = null;
    private ?string $qinq_role = null;  // svlan, cvlan
    private ?string $qinq_svlan = null;
    private ?string $owner = null;
    private array $tags = [];
    private array $custom_fields = [];

    // Read-only fields
    private ?string $url = null;
    private ?string $display_url = null;
    private ?string $display = null;
    private ?string $created = null;
    private ?string $last_updated = null;
    private ?int $prefix_count = null;
    private $l2vpn_termination = null;

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
        if ($this->getVid() === null) {
            throw new Exception("Missing vid for Vlan");
        }
        if (empty($this->getName())) {
            throw new Exception("Missing name for Vlan");
        }

        $res = self::$client->post(self::ENDPOINT, $this->getAddParamArr());
        $this->loadFromApiResult($res);
    }

    /**
     * Read single (by id, or list-filter by name/vid)
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
        if (!is_null($this->getVid())) {
            $params['vid'] = $this->getVid();
        }

        if (empty($params)) {
            throw new Exception("Can't load Vlan without 'id', 'name' or 'vid'");
        }

        $res = self::$client->get(self::ENDPOINT, $params);

        if (($res['count'] ?? 0) === 0) {
            throw new Exception("Vlan not found");
        }
        if (($res['count'] ?? 0) > 1) {
            throw new Exception("Multiple Vlans returned by query");
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
            throw new Exception("Can't edit Vlan without 'id'");
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
            throw new Exception("Can't update Vlan without 'id'");
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
            throw new Exception("Can't delete Vlan without 'id'");
        }

        self::$client->delete(self::ENDPOINT . $this->getId() . '/');
        $this->setId(null);
    }

    // --- Private helpers ---

    private function getAddParamArr(): array
    {
        $params = [
            'vid' => $this->getVid(),
            'name' => $this->getName(),
            'status' => $this->getStatus(),
        ];

        if (!is_null($this->getSite())) { $params['site'] = $this->getSite(); }
        if (!is_null($this->getGroup())) { $params['group'] = $this->getGroup(); }
        if (!is_null($this->getTenant())) { $params['tenant'] = $this->getTenant(); }
        if (!is_null($this->getRole())) { $params['role'] = $this->getRole(); }
        if (!is_null($this->getQinqRole())) { $params['qinq_role'] = $this->getQinqRole(); }
        if (!is_null($this->getQinqSvlan())) { $params['qinq_svlan'] = $this->getQinqSvlan(); }
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
        $this->setVid(isset($res['vid']) ? (int)$res['vid'] : $this->getVid());
        $this->setName((string)($res['name'] ?? $this->getName()));

        if (isset($res['status'])) {
            $this->setStatus(is_array($res['status']) ? ($res['status']['value'] ?? 'active') : (string)$res['status']);
        }

        $this->setDescription((string)($res['description'] ?? ''));
        $this->setComments((string)($res['comments'] ?? ''));
        $this->setSite(self::extractId($res['site'] ?? null));
        $this->setGroup(self::extractId($res['group'] ?? null));
        $this->setTenant(self::extractId($res['tenant'] ?? null));
        $this->setRole(self::extractId($res['role'] ?? null));

        if (isset($res['qinq_role'])) {
            $this->setQinqRole(is_array($res['qinq_role']) ? ($res['qinq_role']['value'] ?? null) : $res['qinq_role']);
        }

        $this->setQinqSvlan(self::extractId($res['qinq_svlan'] ?? null));
        $this->setOwner(self::extractId($res['owner'] ?? null));
        $this->setTags($res['tags'] ?? []);
        $this->setCustomFields($res['custom_fields'] ?? []);

        // Read-only fields
        $this->url = $res['url'] ?? null;
        $this->display_url = $res['display_url'] ?? null;
        $this->display = $res['display'] ?? null;
        $this->created = $res['created'] ?? null;
        $this->last_updated = $res['last_updated'] ?? null;
        $this->prefix_count = isset($res['prefix_count']) ? (int)$res['prefix_count'] : null;
        $this->l2vpn_termination = $res['l2vpn_termination'] ?? null;
    }

    private static function extractId($maybe): ?string
    {
        if (is_null($maybe) || $maybe === '') { return null; }
        if (is_array($maybe)) { return isset($maybe['id']) ? (string)$maybe['id'] : null; }
        return (string)$maybe;
    }

    // --- Getters / Setters ---

    public function getId(): ?string { return $this->id; }
    public function setId(?string $id): Vlan { $this->id = $id; return $this; }

    public function getVid(): ?int { return $this->vid; }
    public function setVid(?int $vid): Vlan { $this->vid = $vid; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): Vlan { $this->name = $name; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): Vlan { $this->status = $status; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): Vlan { $this->description = $description; return $this; }

    public function getComments(): string { return $this->comments; }
    public function setComments(string $comments): Vlan { $this->comments = $comments; return $this; }

    public function getSite(): ?string { return $this->site; }
    public function setSite(?string $site): Vlan { $this->site = $site; return $this; }

    public function getGroup(): ?string { return $this->group; }
    public function setGroup(?string $group): Vlan { $this->group = $group; return $this; }

    public function getTenant(): ?string { return $this->tenant; }
    public function setTenant(?string $tenant): Vlan { $this->tenant = $tenant; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(?string $role): Vlan { $this->role = $role; return $this; }

    public function getQinqRole(): ?string { return $this->qinq_role; }
    public function setQinqRole(?string $qinq_role): Vlan { $this->qinq_role = $qinq_role; return $this; }

    public function getQinqSvlan(): ?string { return $this->qinq_svlan; }
    public function setQinqSvlan(?string $qinq_svlan): Vlan { $this->qinq_svlan = $qinq_svlan; return $this; }

    public function getOwner(): ?string { return $this->owner; }
    public function setOwner(?string $owner): Vlan { $this->owner = $owner; return $this; }

    public function getTags(): array { return $this->tags; }
    public function setTags(array $tags): Vlan { $this->tags = $tags; return $this; }

    public function getCustomFields(): array { return $this->custom_fields; }
    public function setCustomFields(array $custom_fields): Vlan { $this->custom_fields = $custom_fields; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function getDisplayUrl(): ?string { return $this->display_url; }
    public function getDisplay(): ?string { return $this->display; }
    public function getCreated(): ?string { return $this->created; }
    public function getLastUpdated(): ?string { return $this->last_updated; }
    public function getPrefixCount(): ?int { return $this->prefix_count; }
    public function getL2vpnTermination() { return $this->l2vpn_termination; }
}
