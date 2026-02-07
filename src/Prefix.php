<?php

namespace unamur\CloudGen;

use unamur\CloudGen\CloudGenException;
use GuzzleHttp\Exception\GuzzleException;
use mkevenaar\NetBox\Api\IPAM\Prefixes;

class Prefix
{
    private ?string $id = 'null';
    private string $prefix = '';
    private string $status = '';
    private ?string $vrf = null;
    private ?string $tenant = null;
    private ?string $vlan_group = null;
    private ?string $vlan = null;
    private ?string $role = null;
    private ?string $scope_type = null;
    private ?string $scope_id = null;
    private bool $is_pool = false;
    private bool $mark_utilized = false;
    private string $description = '';
    private string $comments = '';
    private string $tags = '';

    static private Prefixes $api;

    public function __construct() {
        // when creating the Prefix has no ID
        $this->setId(null);
        self::$api = new Prefixes(new \mkevenaar\NetBox\Client());
    }

    /**
     * @throws CloudGenException
     */
    public function load(): void
    {
        $paramArr = [];
        if (!is_null($this->getId())) {
            $paramArr['id'] = $this->getId();
        }
        if (!empty($this->getPrefix())) {
            $paramArr['prefix'] = $this->getPrefix();
        }

        if (empty($paramArr)) {
            // We need to search on something
            throw new CloudGenException("Can't load with neither id or prefix");
        } else {
            try {
                $res = self::$api->list($paramArr);
                if ($res['count'] == 1) {
                    $this->loadFromApiResult($res['results'][0]);
                } else {
                    throw new CloudGenException("Multiple Prefixes returned by query with " . var_export($paramArr, true));
                }
            } catch (GuzzleException $e) {
                throw new CloudGenException("Couldn't load the Prefix: {$e->getMessage()}");
            }
        }
    }

    /**
     * @throws CloudGenException
     */
    public function add(): void
    {
        try {
            $res = self::$api->add($this->getAddParamArr());
            $this->setId($res["id"]);
        } catch (GuzzleException $e) {
            throw new CloudGenException("Couldn't create the Prefix: {$e->getMessage()}");
        }
    }

    private function getAddParamArr(): array
    {
        $params = [
            'prefix' => $this->getPrefix(),
            'status' => $this->getStatus(),
            'is_pool' => $this->isPool(),
            'mark_utilized' => $this->isMarkUtilized(),
            'description' => $this->getDescription()
        ];

        // Add optional parameters only if they are set
        if (!is_null($this->getVrf())) {
            $params['vrf'] = $this->getVrf();
        }
        if (!is_null($this->getTenant())) {
            $params['tenant'] = $this->getTenant();
        }
        if (!is_null($this->getVlanGroup())) {
            $params['vlan_group'] = $this->getVlanGroup();
        }
        if (!is_null($this->getVlan())) {
            $params['vlan'] = $this->getVlan();
        }
        if (!is_null($this->getRole())) {
            $params['role'] = $this->getRole();
        }
        if (!is_null($this->getScopeType())) {
            $params['scope_type'] = $this->getScopeType();
        }
        if (!is_null($this->getScopeId())) {
            $params['scope_id'] = $this->getScopeId();
        }
        if (!empty($this->getComments())) {
            $params['comments'] = $this->getComments();
        }
        if (!empty($this->getTags())) {
            $params['tags'] = $this->getTags();
        }

        return $params;
    }

    private function loadFromApiResult(array $result): void
    {
        $this->setId($result['id']);
        $this->setPrefix($result['prefix']);
        $this->setStatus($result['status']['value']);
        $this->setVrf($result['vrf']['name'] ?? null);
        $this->setTenant($result['tenant']['name'] ?? null);
        $this->setVlanGroup($result['vlan_group']['name'] ?? null);
        $this->setVlan($result['vlan']['vid'] ?? null);
        $this->setRole($result['role']['name'] ?? null);
        $this->setScopeType($result['scope_type'] ?? null);
        $this->setScopeId($result['scope_id'] ?? null);
        $this->setIsPool($result['is_pool']);
        $this->setMarkUtilized($result['mark_utilized']);
        $this->setDescription($result['description']);
        $this->setComments($result['comments']);
        $this->setTags($result['tags']);
    }

    // Getters and Setters

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): Prefix
    {
        $this->id = $id;
        return $this;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function setPrefix(string $prefix): Prefix
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): Prefix
    {
        $this->status = $status;
        return $this;
    }

    public function getVrf(): ?string
    {
        return $this->vrf;
    }

    public function setVrf(?string $vrf): Prefix
    {
        $this->vrf = $vrf;
        return $this;
    }

    public function getTenant(): ?string
    {
        return $this->tenant;
    }

    public function setTenant(?string $tenant): Prefix
    {
        $this->tenant = $tenant;
        return $this;
    }

    public function getVlanGroup(): ?string
    {
        return $this->vlan_group;
    }

    public function setVlanGroup(?string $vlanGroup): Prefix
    {
        $this->vlan_group = $vlanGroup;
        return $this;
    }

    public function getVlan(): ?string
    {
        return $this->vlan;
    }

    public function setVlan(?string $vlan): Prefix
    {
        $this->vlan = $vlan;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): Prefix
    {
        $this->role = $role;
        return $this;
    }

    public function getScopeType(): ?string
    {
        return $this->scope_type;
    }

    public function setScopeType(?string $scopeType): Prefix
    {
        $this->scope_type = $scopeType;
        return $this;
    }

    public function getScopeId(): ?string
    {
        return $this->scope_id;
    }

    public function setScopeId(?string $scopeId): Prefix
    {
        $this->scope_id = $scopeId;
        return $this;
    }

    public function isPool(): bool
    {
        return $this->is_pool;
    }

    public function setIsPool(bool $isPool): Prefix
    {
        $this->is_pool = $isPool;
        return $this;
    }

    public function isMarkUtilized(): bool
    {
        return $this->mark_utilized;
    }

    public function setMarkUtilized(bool $markUtilized): Prefix
    {
        $this->mark_utilized = $markUtilized;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): Prefix
    {
        $this->description = $description;
        return $this;
    }

    public function getComments(): string
    {
        return $this->comments;
    }

    public function setComments(string $comments): Prefix
    {
        $this->comments = $comments;
        return $this;
    }

    public function getTags(): string
    {
        return $this->tags;
    }

    public function setTags(string $tags): Prefix
    {
        $this->tags = $tags;
        return $this;
    }

    public static function getApi(): Prefixes
    {
        return self::$api;
    }

    public static function setApi(Prefixes $api): void
    {
        self::$api = $api;
    }
}
