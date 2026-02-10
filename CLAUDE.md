# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PHP library (`ancalagon/netbox`) providing high-level object-oriented wrappers around the NetBox REST API. Uses a custom curl-based `NetboxClient` for all HTTP communication. Requires PHP 8.4+.

## Build & Dependencies

```bash
composer install          # Install dependencies
composer dump-autoload    # Regenerate autoloader after adding classes
```

## Testing

Integration tests run against a live NetBox instance (requires env vars below):

```bash
vendor/bin/phpunit                      # Run all tests
vendor/bin/phpunit tests/DeviceTest.php # Run a single test
```

## Environment

`NetboxClient` requires three environment variables:
- `NETBOX_URL_PREFIX` — NetBox API base URL (e.g., `https://netbox.example.com/api`)
- `NETBOX_KEY` — API token key prefix
- `NETBOX_TOKEN` — API token value

The token is assembled as `nbt_{NETBOX_KEY}.{NETBOX_TOKEN}`.

## Architecture

**Namespace:** `Ancalagon\Netbox` (PSR-4 autoloaded from `src/`)

**Entity classes** — Each file in `src/` wraps one NetBox API resource with full CRUD:

| Class | NetBox Endpoint |
|---|---|
| `VirtualMachine` | `/virtualization/virtual-machines/` |
| `VirtualMachineInterface` | `/virtualization/interfaces/` |
| `ClusterType` | `/virtualization/cluster-types/` |
| `ClusterGroup` | `/virtualization/cluster-groups/` |
| `Cluster` | `/virtualization/clusters/` |
| `Device` | `/dcim/devices/` |
| `DeviceType` | `/dcim/device-types/` |
| `DeviceRole` | `/dcim/device-roles/` |
| `NetworkInterface` | `/dcim/interfaces/` |
| `MacAddress` | `/dcim/mac-addresses/` |
| `IpAddress` | `/ipam/ip-addresses/` |
| `Prefix` | `/ipam/prefixes/` |
| `Vlan` | `/ipam/vlans/` |
| `Owner` | `/users/owners/` |
| `OwnerGroup` | `/users/owner-groups/` |

**All classes use `NetboxClient`** (curl-based): `self::$client->post/get/put/patch/delete()`.

**Common class structure:**
- `private const string ENDPOINT` for the API path
- `private static NetboxClient $client` initialized in every constructor
- Private properties with fluent setters (`return $this`)
- `add()` = POST, `load()` = GET (by id or unique field), `list()` = GET with filters, `edit()` = PUT, `update()` = PATCH, `delete()` = DELETE
- `getAddParamArr()` / `getEditParamArr()` build request payloads, only including non-null optional fields
- `loadFromApiResult(array $res)` hydrates the object from API response arrays
- `extractId($maybe)` static helper handles NetBox's polymorphic responses (nested `{id:...}` objects or plain scalars)
- Polymorphic fields (status, mode, role, face, airflow) stored as string, loaded via `is_array()` check
- Read-only fields assigned directly (`$this->url = ...`), no public setters
- No try/catch wrapping in CRUD — `NetboxClient` throws `Exception` directly
