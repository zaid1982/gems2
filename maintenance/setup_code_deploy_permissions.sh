#!/bin/bash
# Grant the web server write access to deployable code paths for Code Deploy Browser.
# Run once on each machine (local XAMPP or production Linux).
#
# Windows: use setup_code_deploy_permissions.bat or .ps1 instead (this script
# does not run icacls). Git Bash on Windows will point you to the .bat file.

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"

echo "GFM GEMS Code Deploy — permissions setup"
echo "Project root: $ROOT"
echo ""

if [[ "$(uname -s)" =~ ^(MINGW|MSYS|CYGWIN) ]] || [[ "${OS:-}" == "Windows_NT" ]]; then
    echo "Windows detected."
    echo "Run one of these from the maintenance folder (as Administrator if needed):"
    echo "  setup_code_deploy_permissions.bat"
    echo "  setup_code_deploy_permissions.bat iis"
    echo "  powershell -ExecutionPolicy Bypass -File setup_code_deploy_permissions.ps1"
    echo "  powershell -ExecutionPolicy Bypass -File setup_code_deploy_permissions.ps1 -Mode Iis"
    exit 0
fi

apply_macos_acl() {
    local web_user="$1"
    local target="$2"
    if [[ ! -e "$target" ]]; then
        return 0
    fi
    echo "  ACL: $target"
    chmod -R +a "$web_user allow read,write,append,execute,delete" "$target" 2>/dev/null || {
        echo "  Warning: some entries under $target could not be updated (skipped)."
    }
}

if [[ "$(uname -s)" == "Darwin" ]]; then
    WEB_USER="daemon"
    echo "Detected macOS / XAMPP. Granting ACL write access to user: $WEB_USER"
    if ! id "$WEB_USER" >/dev/null 2>&1; then
        echo "User $WEB_USER not found. Try: $0 _www"
        exit 1
    fi
    for dir in api js css maintenance; do
        apply_macos_acl "$WEB_USER" "$ROOT/$dir"
    done
    while IFS= read -r -d '' file; do
        apply_macos_acl "$WEB_USER" "$file"
    done < <(find "$ROOT" -maxdepth 1 -type f \( -name '*.html' -o -name '*.php' -o -name '.htaccess' \) -print0)
    echo ""
    echo "Done. Code paths should be writable by Apache ($WEB_USER)."
    echo "Blocked paths (upload/, config.ini) are unchanged by design."
    exit 0
fi

WEB_USER="${1:-www-data}"
if ! id "$WEB_USER" >/dev/null 2>&1; then
    echo "User $WEB_USER not found. Pass your web user, e.g.: sudo $0 apache"
    exit 1
fi

CODE_PATHS=("$ROOT/api" "$ROOT/js" "$ROOT/css" "$ROOT/maintenance")
while IFS= read -r -d '' file; do
    CODE_PATHS+=("$file")
done < <(find "$ROOT" -maxdepth 1 -type f \( -name '*.html' -o -name '*.php' -o -name '.htaccess' \) -print0)

if command -v setfacl >/dev/null 2>&1; then
    echo "Linux: applying ACL for $WEB_USER on code paths"
    for target in "${CODE_PATHS[@]}"; do
        [[ -e "$target" ]] || continue
        echo "  ACL: $target"
        setfacl -R -m "u:${WEB_USER}:rwX" "$target" 2>/dev/null || true
        if [[ -d "$target" ]]; then
            setfacl -R -d -m "u:${WEB_USER}:rwX" "$target" 2>/dev/null || true
        fi
    done
    echo "Done. ACL applied for $WEB_USER"
else
    echo "Linux: setfacl not found."
    echo "Either install acl (apt install acl) and re-run, or:"
    echo "  sudo chown -R $WEB_USER:$WEB_USER $ROOT/api $ROOT/js $ROOT/css $ROOT/maintenance"
    exit 1
fi

echo "Verify in Code Deploy Browser: Save should work now."
