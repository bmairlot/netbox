<?php

namespace Ancalagon\Netbox;

use Ancalagon\Netbox\CloudGenException;
use GuzzleHttp\Exception\GuzzleException;
use mkevenaar\NetBox\Api\IPAM\Vlans;

class Vlan
{
    private ?string $id='null';
    private string $vid='';
    private string $name='';
    private string $status='';
    private string $description='';
    private ?string $vrf = null;

    static private Vlans $api;
    
    public function __construct() {
        // when creating the VM has no ID
        $this->setId(null);
        self::$api = new Vlans(new \mkevenaar\NetBox\Client());
    }

    /**
     * @throws CloudGenException
     */
    public function load(): void
    {
        $paramArr=[];
        if(!is_null($this->getId())) {
            $paramArr['id']=$this->getId();
        }
        if(!empty($this->getName())){
            $paramArr['name'] = $this->getName();
        }
        if(!empty($this->getVid())){
            $paramArr['vid'] = $this->getVid();
        }

        if(empty($paramArr)) {
            // We need to search on something
            throw new CloudGenException("Can't load with neither id, vID or name");
        }
        else{
            try {
                $res = self::$api->list($paramArr);
                if($res['count']==0){

                    throw new CloudGenException("VLAN not found".var_export($paramArr,true));
                }
                if($res['count']==1){
                    $this->setId($res['results'][0]['id']);
                    $this->setStatus($res['results'][0]['status']["value"]);
                    $this->setVid($res['results'][0]['vid']);
                    $this->setName($res['results'][0]['name']);
                    $this->setDescription($res['results'][0]['description']);
                    $this->setVrf($res['results'][0]['vrf']['name'] ?? null);
                }
                else{
                    print_r($res);
                    throw new CloudGenException("Multiple VLAN returned by query with ".var_export($paramArr,true));
                }
            } catch (GuzzleException $e) {
                throw new CloudGenException("Couldn't load the VLAN : {$e->getMessage()}");
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
            throw new CloudGenException("Couldn't create the Vlan : {$e->getMessage()}");
        }
    }

    function getAddParamArr():array{
        $params = [
            'vid'=>$this->getVid(),
            'name'=>$this->getName(),
            'status'=>$this->getStatus(),
            'description'=>$this->getDescription(),
        ];

        // Add VRF if it's set
        if (!is_null($this->getVrf())) {
            $params['vrf'] = $this->getVrf();
        }

        return $params;
    }

    private function loadFromApiResult(array $result): void{
        $this->setId($result['id']);
        $this->setVid($result['vid']);
        $this->setName($result['name']);
        $this->setVrf($result['vrf']['name'] ?? null);
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string|null $id
     * @return Vlan
     */
    public function setId(?string $id): Vlan
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getVid(): string
    {
        return $this->vid;
    }

    /**
     * @param string $vid
     * @return Vlan
     */
    public function setVid(string $vid): Vlan
    {
        $this->vid = $vid;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Vlan
     */
    public function setName(string $name): Vlan
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return Vlans
     */
    public static function getApi(): Vlans
    {
        return self::$api;
    }

    /**
     * @param Vlans $api
     */
    public static function setApi(Vlans $api): void
    {
        self::$api = $api;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return Vlan
     */
    public function setStatus(string $status): Vlan
    {
        $this->status = $status;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): Vlan
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getVrf(): ?string
    {
        return $this->vrf;
    }

    /**
     * @param string|null $vrf
     * @return Vlan
     */
    public function setVrf(?string $vrf): Vlan
    {
        $this->vrf = $vrf;
        return $this;
    }
}
