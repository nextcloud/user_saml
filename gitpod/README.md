# Try it on Gitpod
[![Open in Gitpod](https://gitpod.io/button/open-in-gitpod.svg)](https://gitpod.io/#https://github.com/[user]/[repository/)

It will automatically spin up and configure a full Nextcloud, MariaDB, PhpMyAdmin, SimpleSAMLphp and 389 Directory development server.

## Usage
- Open the Repository in gitpod
- Wait about 4 minutes for all servers to start (progress can be followed in the Nextcloud Terminal)
- If your browser doesn't block it, a new tab with the Nextcloud server opens automatically.
- If not go to the Remote Explorer Tab and open port 8080

## Ports
- 80: Nextcloud
- 8081: PhpMyAdmin
- 8082: SimpleSAMLphp Admin (/simplesaml subdirectory)

## Nextcloud Direct Login:
**Username:** dev

**Password:** t2qQ1C6ktYUv7

## Nextcloud Saml Login:
### 1
**Username:** test1

**Password:** test1password

### 2
**Username:** test2

**Password:** test2password

### 3
**Username:** bender

**Password:** bender

## PhpMyAdmin Login:
**Username:** nextcloud

**Password:** wdGq73jQB0p373gLdf6yLRj5

(It is fine to have these static logins, because gitpod has acess control built in and no sensitive data is stored in these dev servers)

## SimpleSAMLphp Admin Login:
**Username:** admin

**Password:** 1234

# OCC Acess
You can acess nextclouds occ shell using this command:
```
docker exec -it -u 33 gitpod_app_1 php occ
````
