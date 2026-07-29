{
  "name": "Unify V9 - Fixed",
  "image": "mcr.microsoft.com/devcontainers/base:jammy",
  "features": {
    "ghcr.io/devcontainers/features/common-utils:2": {
      "installZsh": true,
      "username": "codespace",
      "userUid": "1000",
      "userGid": "1000"
    },
    "ghcr.io/devcontainers/features/git:1": {},
    "ghcr.io/devcontainers/features/node:1": {
      "version": "20",
      "installYarnUsingApt": false
    },
    "ghcr.io/devcontainers/features/php:1": {
      "version": "8.2",
      "installComposer": true
    }
  },
  "postCreateCommand": "bash .devcontainer/setup.sh",
  "forwardPorts": [8000, 5173],
  "portsAttributes": {
    "8000": {
      "label": "Backend API (Laravel 8000)",
      "onAutoForward": "notify",
      "visibility": "public"
    },
    "5173": {
      "label": "Frontend (Vite 5173)",
      "onAutoForward": "openBrowser",
      "visibility": "public"
    }
  },
  "customizations": {
    "vscode": {
      "extensions": [
        "bmewburn.vscode-intelephense-client",
        "esbenp.prettier-vscode",
        "bradlc.vscode-tailwindcss",
        "ms-vscode.vscode-json"
      ],
      "settings": {
        "terminal.integrated.defaultProfile.linux": "bash"
      }
    }
  },
  "remoteUser": "codespace"
}
