# ancalagon/netbox

A PHP library providing high-level object-oriented wrappers around the [NetBox](https://netbox.dev/) REST API. Built on top of [`mkevenaar/netbox`](https://github.com/mkevenaar/netbox-php).

Each NetBox resource is represented as a PHP class with full CRUD support, fluent setters, and automatic hydration from API responses.

## Requirements

- PHP 8.4+
- `ext-json`
- `ext-curl`

## Installation

```bash
composer require ancalagon/netbox
```

## Configuration

The library requires two environment variables to connect to your NetBox instance:

```bash
export NETBOX_API="https://netbox.example.com/api"
export NETBOX_API_KEY="your-api-token"
```

## Usage

### Virtual Machines

```php
use Ancalagon\Netbox\VirtualMachine;

// Create a VM
$vm = new VirtualMachine();
$vm->setName('web-server-01')
   ->setStatus('active')
   ->setCluster('1')
   ->setVcpus(4)
   ->setMemory(8192)
   ->setDisk(100);
$vm->add();

echo $vm->getId(); // NetBox-assigned ID

// Load an existing VM by name
$vm = new VirtualMachine();
$vm->setName('web-server-01');
$vm->load();

// Load by ID
$vm = new VirtualMachine();
$vm->setId('42');
$vm->load();

// Update (PATCH)
$vm->setMemory(16384);
$vm->update();

// Replace (PUT)
$vm->edit();

// List with filters
$vm = new VirtualMachine();
$results = $vm->list(['cluster_id' => '1', 'status' => 'active']);

// Delete
$vm->delete();
```

### IP Addresses

```php
use Ancalagon\Netbox\IpAddress;

// Create an IP address
$ip = new IpAddress();
$ip->setAddress('192.168.1.10/24')
   ->setStatus('active')
   ->setDnsName('web-server-01.example.com');
$ip->add();

// Assign to a VM interface
$ip->assignToVmInterface('15');

// Assign to a physical device interface
$ip->assignToInterface('23');

// List IPs on a VM interface
$ip = new IpAddress();
$results = $ip->listByVmInterface('15');

// Unassign
$ip->unassign();
```

### VLANs

```php
use Ancalagon\Netbox\Vlan;

$vlan = new Vlan();
$vlan->setVid('100')
     ->setName('Management')
     ->setStatus('active')
     ->setDescription('Management VLAN');
$vlan->add();

// Load by VID
$vlan = new Vlan();
$vlan->setVid('100');
$vlan->load();
```

### Prefixes

```php
use Ancalagon\Netbox\Prefix;

$prefix = new Prefix();
$prefix->setPrefix('10.0.0.0/24')
       ->setStatus('active')
       ->setDescription('Server network');
$prefix->add();
```

### VM Interfaces

```php
use Ancalagon\Netbox\VirtualMachineInterface;

// Create an interface on a VM
$iface = new VirtualMachineInterface();
$iface->setVirtualMachine('42')
      ->setName('eth0')
      ->setEnabled(true)
      ->setMtu(1500);
$iface->add();

// VLAN management
$iface->assignUntaggedVlan('100');
$iface->addTaggedVlan('200');
$iface->addTaggedVlan('300');
$iface->removeTaggedVlan('200');

// List all interfaces for a VM
$iface = new VirtualMachineInterface();
$results = $iface->listByVm('42');
```

### Physical Network Interfaces

```php
use Ancalagon\Netbox\NetworkInterface;

$iface = new NetworkInterface();
$iface->setDevice('5')
      ->setName('GigabitEthernet0/1')
      ->setType(['value' => '1000base-t', 'label' => '1000BASE-T'])
      ->setEnabled(true);
$iface->add();

// List all interfaces for a device
$results = $iface->listByDevice('5');
```

### MAC Addresses

```php
use Ancalagon\Netbox\MacAddress;

$mac = new MacAddress();
$mac->setMacAddress('00:1A:2B:3C:4D:5E')
    ->setDescription('Primary NIC');
$mac->add();

// Load by MAC
$mac = new MacAddress();
$mac->setMacAddress('00:1A:2B:3C:4D:5E');
$mac->load();
```

### Owners and Owner Groups

```php
use Ancalagon\Netbox\OwnerGroup;
use Ancalagon\Netbox\Owner;

// Create an owner group
$group = new OwnerGroup();
$group->setName('Infrastructure Team')
      ->setDescription('Manages core infrastructure');
$group->add();

// Create an owner in that group
$owner = new Owner();
$owner->setName('Network Ops')
      ->setGroup($group->getId())
      ->setDescription('Network operations team')
      ->setUsers([1, 2, 3]);
$owner->add();

// List owners in a group
$owner = new Owner();
$results = $owner->listByGroup($group->getId());
```

## Error Handling

All API errors are wrapped in `Exception`:

```php
use Ancalagon\Netbox\Exception;

try {
    $vm = new VirtualMachine();
    $vm->setName('test-vm');
    $vm->load();
} catch (Exception $e) {
    echo $e->getMessage();
}
```

## CRUD Method Reference

Every entity class provides the same set of operations:

| Method | HTTP Verb | Description |
|---|---|---|
| `add()` | POST | Create a new resource |
| `load()` | GET | Fetch by ID or unique field(s) |
| `list()` | GET | List resources with optional filters |
| `edit()` | PUT | Full replacement of a resource |
| `update()` | PATCH | Partial update of a resource |
| `delete()` | DELETE | Remove a resource |

## License

MIT - see [LICENSE](LICENSE).
