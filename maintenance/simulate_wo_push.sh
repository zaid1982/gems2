#!/usr/bin/env bash
# Install release APK + simulate push for a real WO/WR/PPM task.
#
# Usage:
#   ./maintenance/simulate_wo_push.sh
#   ./maintenance/simulate_wo_push.sh --task-no WOUITMM26081227363 --user-id 1257
#   ./maintenance/simulate_wo_push.sh --task-no WOUITMM26081227363 --username ibrahim --skip-install
#   ./maintenance/simulate_wo_push.sh --list-users
#
set -euo pipefail

ROOT_GEMS="$(cd "$(dirname "$0")/.." && pwd)"
ROOT_APP="${ROOT_APP:-/Users/zaefrul/Development/GFM/metadata-gfm}"
APK="${APK:-$ROOT_APP/build/app/outputs/flutter-apk/app-release.apk}"
ADB="${ADB:-$(command -v adb || true)}"
if [[ -z "${ADB}" && -x "$HOME/Library/Android/sdk/platform-tools/adb" ]]; then
  ADB="$HOME/Library/Android/sdk/platform-tools/adb"
fi

TASK_NO="WOUITMM26081227363"
USER_ID=""
USERNAME=""
NOTI_TEXT_ID="6"
NETWORK_SOURCE="gems20"
SKIP_INSTALL=0
DRY_RUN=0
LIST_USERS=0
WAIT_DEVICE_SEC=60

while [[ $# -gt 0 ]]; do
  case "$1" in
    --task-no) TASK_NO="$2"; shift 2 ;;
    --user-id) USER_ID="$2"; shift 2 ;;
    --username) USERNAME="$2"; shift 2 ;;
    --noti-text-id) NOTI_TEXT_ID="$2"; shift 2 ;;
    --network-source) NETWORK_SOURCE="$2"; shift 2 ;;
    --apk) APK="$2"; shift 2 ;;
    --skip-install) SKIP_INSTALL=1; shift ;;
    --dry-run) DRY_RUN=1; shift ;;
    --list-users) LIST_USERS=1; shift ;;
    --wait-sec) WAIT_DEVICE_SEC="$2"; shift 2 ;;
    -h|--help)
      sed -n '2,12p' "$0"
      exit 0
      ;;
    *)
      echo "Unknown arg: $1" >&2
      exit 1
      ;;
  esac
done

PHP_BIN="${PHP_BIN:-/Applications/XAMPP/xamppfiles/bin/php}"
[[ -x "$PHP_BIN" ]] || PHP_BIN="$(command -v php)"

if [[ "$LIST_USERS" -eq 1 ]]; then
  "$PHP_BIN" "$ROOT_GEMS/maintenance/test_push_notification.php" --list-users
  exit 0
fi

if [[ "$SKIP_INSTALL" -eq 0 ]]; then
  if [[ ! -f "$APK" ]]; then
    echo "FAIL: APK not found: $APK"
    echo "Build first: cd \"$ROOT_APP\" && flutter build apk --release"
    exit 1
  fi
  if [[ -z "${ADB}" || ! -x "${ADB}" ]]; then
    echo "FAIL: adb not found"
    exit 1
  fi

  echo "==> Waiting for Android device (USB debugging) up to ${WAIT_DEVICE_SEC}s..."
  if ! "$ADB" wait-for-device shell true >/dev/null 2>&1 &
  then
    :
  fi
  SECONDS=0
  while true; do
    DEVICES="$("$ADB" devices | awk 'NR>1 && $2=="device" {print $1}')"
    if [[ -n "$DEVICES" ]]; then
      echo "OK: device(s):"
      echo "$DEVICES"
      break
    fi
    if (( SECONDS >= WAIT_DEVICE_SEC )); then
      echo "FAIL: no adb device in ${WAIT_DEVICE_SEC}s."
      echo "Plug phone, enable Developer options + USB debugging, accept RSA prompt, retry."
      exit 1
    fi
    sleep 2
  done

  echo "==> Installing $APK"
  "$ADB" install -r "$APK"
  echo "OK: installed"
  echo
  echo "Next on phone:"
  echo "  1) Open GEMS"
  echo "  2) Login with NetworkSource = GEMS 2.0 (gfmgems)"
  echo "  3) Stay on homepage so FCM token is saved"
  echo "  4) Re-run this script with --skip-install --user-id <your_id> --task-no $TASK_NO"
  echo
  if [[ -z "$USER_ID" && -z "$USERNAME" ]]; then
    echo "No --user-id/--username yet — listing users with tokens:"
    "$PHP_BIN" "$ROOT_GEMS/maintenance/test_push_notification.php" --list-users || true
    exit 0
  fi
fi

ARGS=(
  --task-no="$TASK_NO"
  --noti-text-id="$NOTI_TEXT_ID"
  --network-source="$NETWORK_SOURCE"
)
[[ -n "$USER_ID" ]] && ARGS+=(--user-id="$USER_ID")
[[ -n "$USERNAME" ]] && ARGS+=(--username="$USERNAME")
[[ "$DRY_RUN" -eq 1 ]] && ARGS+=(--dry-run)

echo "==> Simulating push: task_no=$TASK_NO noti_text_id=$NOTI_TEXT_ID network_source=$NETWORK_SOURCE"
"$PHP_BIN" "$ROOT_GEMS/maintenance/test_push_notification.php" "${ARGS[@]}"
