# Workspace
Create shared workspaces and delegate management of their members and groups.

<p align="center">
<img width="80%" src="./screenshots/workspace-home-small.png" alt="Workspace Home">
</p>

Workspace allows managers to :
- Create shared workspaces
- Delegate management of each workspace to users (workspace managers) in order for them to
  - choose members
  - create groups
  - configure advanced permissions on workspace folders
- All through a simple unified interface, designed to simplify your users' experience and make them autonomous

This app is a Nextcloud extension of the Groupfolders app.

For more information, please visit [our website](https://www.arawa.fr/solutions/produits-arawa/arawa-workspace-for-nextcloud/) (french content).

## Installation
### Requirements
- PHP 7.4 to 8.1
- Nextcloud 21 to 24
- Our forked Groupfolders app, available on https://github.com/arawa/groupfolders, release [v9.1.0](https://github.com/arawa/groupfolders/releases/download/v9.1.0/groupfolders-9.1.0.tar.gz)

### Limit the Workspace app to specific groups

In your "application management" administrator interface, limit the application to the following groups: `GeneralManager` and `WorkspacesManagers`.

### 🔧 Configure Groupfolders for Workspace

To use the Wokspace app, you need to add the `GeneralManager` group in the `Group folders` field of the `Administration privileges` page.

In `Settings` > `Group folders` > `Group folder admin delegation` section, add the groups `GeneralManager` and `WorkspacesManagers`.

### Define which users will be General Managers

Add the users in charge of creating Workspaces to the GeneralManager group


## Development and Build
### Requirements
- npm v7.24.1
- composer v2.0.13
- make v3.82
- git v1.8

### 📦 Building the app

First, clone into your apps directory (example: `/var/www/html/nextcloud/apps/`).

```bash
git clone https://github.com/arawa/workspace.git
```

Then, you can build app :

```bash
cd workspace
make
```

🚨 **Caution** : You must install `npm` and `composer` before use `make` command line.

If it's okay, we can use or dev the Workspace app !

### 📦 Create an artifact

```bash
make source
```

An artifact will be created in the `build/artifacts/source` from the project.



### 📦 For Nextcloud 21 and 24, build [Arawa\Groupfolders](https://github.com/arawa/groupfolders)

Clone this app into your apps directory (example: `/var/www/html/nextcloud/apps/`) and switch of the branch to be in `allow-admin-delegation-stable21`.

```bash
git clone https://github.com/arawa/groupfolders.git
cd groupfolders
git checkout allow-admin-delegation-stable21
```

Then, you can build.

```bash
make
```

🚨 **Caution** : You must install `npm` and `composer` before use `make` command line.

After this, you can enable the Groupfolders app.

### 📋 Running tests

#### Front-end

```bash
npm run test
```

#### Back-end

```bash
composer run test
```

or

```bash
sudo -u nginx /usr/local/bin/composer run test
```
