<?php

namespace Ancalagon\Netbox;

use GuzzleHttp\Exception\GuzzleException;
use mkevenaar\NetBox\Api\Virtualization\VirtualMachines;
use mkevenaar\NetBox\Client;

class VirtualMachine
{
    private ?string $id = 'null';
    private string $name = '';
    private string $status = 'offline';
    private ?string $tenant = '1';

    // Optional properties (only sent if explicitly set)
    private ?string $site = null;
    private ?string $cluster = null;
    private ?string $device = null;
    private ?string $serial = null;
    private ?string $role = null;
    private ?string $platform = null;
    private ?string $primary_ip4 = null;
    private ?string $primary_ip6 = null;
    private ?int $vcpus = null;
    private ?int $memory = null;
    private ?int $disk = null;
    private string $description = '';
    private string $comments = '';
    private ?string $config_template = null;
    private ?string $local_context_data = null;
    private array $tags = [];
    private array $custom_fields = [];

    // Read-only/metadata
    private ?string $url = null;
    private ?string $display = null;
    private ?string $created = null;
    private ?string $last_updated = null;

    static private VirtualMachines $api;
    static private Client $client;

    public function __construct()
    {
        // New VM has no ID until created
        $this->setId(null);
        self::$client = new Client();
        self::$api = new VirtualMachines(self::$client);
    }

    /**
     * Create (POST)
     * @throws Exception
     */
    public function add(): void
    {
        try {
            $res = self::$api->add($this->getAddParamArr());
            $this->loadFromApiResult($res);
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't create the Virtual Machine: {$e->getMessage()}");
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
                $res = self::$client->getHttpClient()->get("/virtualization/virtual-machines/" . $this->getId() . "/", []);
                $this->loadFromApiResult($res);
                return;
            }

            if (!empty($this->getName())) {
                $res = self::$client->getHttpClient()->get("/virtualization/virtual-machines/", [
                    'name' => $this->getName(),
                ]);

                if (($res['count'] ?? 0) === 0) {
                    throw new Exception("VirtualMachine not found for name='{$this->getName()}'");
                }
                if (($res['count'] ?? 0) > 1) {
                    throw new Exception("Multiple VirtualMachine entries found for name='{$this->getName()}'");
                }
                $this->loadFromApiResult($res['results'][0]);
                return;
            }

            throw new Exception("Can't load VirtualMachine without 'id' or 'name'");
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't load the Virtual Machine: {$e->getMessage()}");
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
            return self::$client->getHttpClient()->get("/virtualization/virtual-machines/", $filters);
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't list Virtual Machines: {$e->getMessage()}");
        }
    }

    /**
     * Replace (PUT)
     * @throws Exception
     */
    public function edit(): void
    {
        if (is_null($this->getId())) {
            throw new Exception("Can't edit VirtualMachine without 'id'");
        }
        try {
            $res = self::$client->getHttpClient()->put("/virtualization/virtual-machines/" . $this->getId() . "/", $this->getEditParamArr());
            $this->loadFromApiResult($res);
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't edit the Virtual Machine: {$e->getMessage()}");
        }
    }

    /**
     * Partial Update (PATCH)
     * @throws Exception
     */
    public function update(): void
    {
        if (is_null($this->getId())) {
            throw new Exception("Can't update VirtualMachine without 'id'");
        }
        try {
            $res = self::$client->getHttpClient()->patch("/virtualization/virtual-machines/" . $this->getId() . "/", $this->getEditParamArr());
            $this->loadFromApiResult($res);
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't update the Virtual Machine: {$e->getMessage()}");
        }
    }

    /**
     * Delete (DELETE)
     * @throws Exception
     */
    public function delete(): void
    {
        if (is_null($this->getId())) {
            throw new Exception("Can't delete VirtualMachine without 'id'");
        }
        try {
            self::$client->getHttpClient()->delete("/virtualization/virtual-machines/" . $this->getId() . "/", []);
            $this->setId(null);
        } catch (GuzzleException $e) {
            throw new Exception("Couldn't delete the Virtual Machine: {$e->getMessage()}");
        }
    }

    private function getAddParamArr(): array
    {
        $params = [
            'name' => $this->getName(),
            'status' => $this->getStatus(),
        ];

        // Optional values only when set
        if (!is_null($this->getTenant())) {
            $params['tenant'] = $this->getTenant();
        }
        if (!is_null($this->getSite())) {
            $params['site'] = $this->getSite();
        }
        if (!is_null($this->getCluster())) {
            $params['cluster'] = $this->getCluster();
        }
        if (!is_null($this->getDevice())) {
            $params['device'] = $this->getDevice();
        }
        if (!is_null($this->getSerial())) {
            $params['serial'] = $this->getSerial();
        }
        if (!is_null($this->getRole())) {
            $params['role'] = $this->getRole();
        }
        if (!is_null($this->getPlatform())) {
            $params['platform'] = $this->getPlatform();
        }
        if (!is_null($this->getPrimaryIp4())) {
            $params['primary_ip4'] = $this->getPrimaryIp4();
        }
        if (!is_null($this->getPrimaryIp6())) {
            $params['primary_ip6'] = $this->getPrimaryIp6();
        }
        if (!is_null($this->getVcpus())) {
            $params['vcpus'] = $this->getVcpus();
        }
        if (!is_null($this->getMemory())) {
            $params['memory'] = $this->getMemory();
        }
        if (!is_null($this->getDisk())) {
            $params['disk'] = $this->getDisk();
        }
        if (!empty($this->getDescription())) {
            $params['description'] = $this->getDescription();
        }
        if (!empty($this->getComments())) {
            $params['comments'] = $this->getComments();
        }
        if (!is_null($this->getConfigTemplate())) {
            $params['config_template'] = $this->getConfigTemplate();
        }
        if (!is_null($this->getLocalContextData())) {
            $params['local_context_data'] = $this->getLocalContextData();
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
        return $this->getAddParamArr();
    }

    private function loadFromApiResult(array $res): void
    {
        $this->setId(isset($res['id']) ? (string)$res['id'] : $this->getId());
        $this->setName((string)($res['name'] ?? $this->getName()));

        // Status can be string or object with value
        if (isset($res['status'])) {
            $this->setStatus(is_array($res['status']) ? ($res['status']['value'] ?? 'offline') : (string)$res['status']);
        }

        $this->setTenant(self::extractId($res['tenant'] ?? null));
        $this->setSite(self::extractId($res['site'] ?? null));
        $this->setCluster(self::extractId($res['cluster'] ?? null));
        $this->setDevice(self::extractId($res['device'] ?? null));
        $this->setSerial(isset($res['serial']) ? (string)$res['serial'] : $this->getSerial());
        $this->setRole(self::extractId($res['role'] ?? null));
        $this->setPlatform(self::extractId($res['platform'] ?? null));
        $this->setPrimaryIp4(self::extractId($res['primary_ip4'] ?? null));
        $this->setPrimaryIp6(self::extractId($res['primary_ip6'] ?? null));
        $this->setVcpus(isset($res['vcpus']) ? (int)$res['vcpus'] : $this->getVcpus());
        $this->setMemory(isset($res['memory']) ? (int)$res['memory'] : $this->getMemory());
        $this->setDisk(isset($res['disk']) ? (int)$res['disk'] : $this->getDisk());
        $this->setDescription((string)($res['description'] ?? $this->getDescription()));
        $this->setComments((string)($res['comments'] ?? $this->getComments()));
        $this->setConfigTemplate(self::extractId($res['config_template'] ?? null));
        $this->setLocalContextData(isset($res['local_context_data']) ? json_encode($res['local_context_data']) : $this->getLocalContextData());
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
    public function setId(?string $id): VirtualMachine { $this->id = $id; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): VirtualMachine { $this->name = $name; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): VirtualMachine { $this->status = $status; return $this; }

    public function getTenant(): ?string { return $this->tenant; }
    public function setTenant(?string $tenant): VirtualMachine { $this->tenant = $tenant; return $this; }

    public function getSite(): ?string { return $this->site; }
    public function setSite(?string $site): VirtualMachine { $this->site = $site; return $this; }

    public function getCluster(): ?string { return $this->cluster; }
    public function setCluster(?string $cluster): VirtualMachine { $this->cluster = $cluster; return $this; }

    public function getDevice(): ?string { return $this->device; }
    public function setDevice(?string $device): VirtualMachine { $this->device = $device; return $this; }

    public function getSerial(): ?string { return $this->serial; }
    public function setSerial(?string $serial): VirtualMachine { $this->serial = $serial; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(?string $role): VirtualMachine { $this->role = $role; return $this; }

    public function getPlatform(): ?string { return $this->platform; }
    public function setPlatform(?string $platform): VirtualMachine { $this->platform = $platform; return $this; }

    public function getPrimaryIp4(): ?string { return $this->primary_ip4; }
    public function setPrimaryIp4(?string $primary_ip4): VirtualMachine { $this->primary_ip4 = $primary_ip4; return $this; }

    public function getPrimaryIp6(): ?string { return $this->primary_ip6; }
    public function setPrimaryIp6(?string $primary_ip6): VirtualMachine { $this->primary_ip6 = $primary_ip6; return $this; }

    public function getVcpus(): ?int { return $this->vcpus; }
    public function setVcpus(?int $vcpus): VirtualMachine { $this->vcpus = $vcpus; return $this; }

    public function getMemory(): ?int { return $this->memory; }
    public function setMemory(?int $memory): VirtualMachine { $this->memory = $memory; return $this; }

    public function getDisk(): ?int { return $this->disk; }
    public function setDisk(?int $disk): VirtualMachine { $this->disk = $disk; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): VirtualMachine { $this->description = $description; return $this; }

    public function getComments(): string { return $this->comments; }
    public function setComments(string $comments): VirtualMachine { $this->comments = $comments; return $this; }

    public function getConfigTemplate(): ?string { return $this->config_template; }
    public function setConfigTemplate(?string $config_template): VirtualMachine { $this->config_template = $config_template; return $this; }

    public function getLocalContextData(): ?string { return $this->local_context_data; }
    public function setLocalContextData(?string $local_context_data): VirtualMachine { $this->local_context_data = $local_context_data; return $this; }

    public function getTags(): array { return $this->tags; }
    public function setTags(array $tags): VirtualMachine { $this->tags = $tags; return $this; }

    public function getCustomFields(): array { return $this->custom_fields; }
    public function setCustomFields(array $custom_fields): VirtualMachine { $this->custom_fields = $custom_fields; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function getDisplay(): ?string { return $this->display; }
    public function getCreated(): ?string { return $this->created; }
    public function getLastUpdated(): ?string { return $this->last_updated; }
}