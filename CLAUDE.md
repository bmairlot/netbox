# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PHP library (`ancalagon/netbox`) providing high-level object-oriented wrappers around the NetBox REST API. Built on top of the low-level `mkevenaar/netbox` client library (v3.x). Requires PHP 8.4+.

## Build & Dependencies

```bash
composer install          # Install dependencies
composer dump-autoload    # Regenerate autoloader after adding classes
```

No test suite or linter is configured yet.

## Environment

The underlying `mkevenaar\NetBox\Client` requires two environment variables:
- `NETBOX_API` — NetBox instance base URL
- `NETBOX_API_KEY` — API authentication token

## Architecture

**Namespace:** `Ancalagon\Netbox` (PSR-4 autoloaded from `src/`)

**Entity classes** — Each file in `src/` wraps one NetBox API resource with full CRUD:

| Class | NetBox Endpoint | API Access Pattern |
|---|---|---|
| `VirtualMachine` | `/virtualization/virtual-machines/` | Uses `mkevenaar` VirtualMachines API + raw HTTP |
| `VirtualMachineInterface` | `/virtualization/interfaces/` | Raw HTTP via `Client::getHttpClient()` |
| `NetworkInterface` | `/dcim/interfaces/` | Raw HTTP |
| `IpAddress` | `/ipam/ip-addresses/` | Raw HTTP |
| `MacAddress` | `/dcim/mac-addresses/` | Raw HTTP |
| `Owner` | `/tenancy/owners/` | Raw HTTP |
| `OwnerGroup` | `/tenancy/owner-groups/` | Raw HTTP |
| `Prefix` | `/ipam/prefixes/` | Uses `mkevenaar` Prefixes API |
| `Vlan` | `/ipam/vlans/` | Uses `mkevenaar` Vlans API |

**Two API access patterns exist:**
1. **Via `mkevenaar` typed API classes** (`Prefix`, `Vlan`, early `VirtualMachine::add()`): uses `self::$api->list()`, `self::$api->add()`.
2. **Via raw HTTP client** (newer classes): uses `self::$client->getHttpClient()->get/post/put/patch/delete()` with endpoint paths directly.

**Common class structure:**
- Private properties with fluent setters (`return $this`)
- Static `$client` / `$api` field initialized in every constructor (creates a new `mkevenaar\NetBox\Client` each time)
- `add()` = POST, `load()` = GET (by id or unique field), `list()` = GET with filters, `edit()` = PUT, `update()` = PATCH, `delete()`/`remove()` = DELETE
- `getAddParamArr()` / `getEditParamArr()` build request payloads, only including non-null optional fields
- `loadFromApiResult(array $res)` hydrates the object from API response arrays
- `extractId($maybe)` static helper handles NetBox's polymorphic responses (nested `{id:...}` objects or plain scalars)
- `CloudGenException` wraps all `GuzzleException` errors

**Inconsistency notes:**
- `Prefix` and `Vlan` are older/simpler (fewer CRUD methods, different coding style)
- `NetworkInterface.getType()` declares return type `string` but the property and setter use `array`