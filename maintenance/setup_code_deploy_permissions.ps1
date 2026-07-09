# Grant web server write access for Code Deploy Browser (Windows PowerShell).
# Usage:
#   .\setup_code_deploy_permissions.ps1              # XAMPP / local dev
#   .\setup_code_deploy_permissions.ps1 -Mode Iis    # IIS
# Run as Administrator if access is denied.

[CmdletBinding()]
param(
    [ValidateSet('Xampp', 'Iis')]
    [string] $Mode = 'Xampp'
)

$ErrorActionPreference = 'Stop'
$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path

Write-Host 'GFM GEMS Code Deploy - Windows permissions setup'
Write-Host "Project root: $Root"
Write-Host "Mode: $Mode"
Write-Host ''
Write-Host 'Granting Modify on api, js, css, maintenance (not upload/ or config.ini).'
Write-Host 'Run PowerShell as Administrator if access is denied.'
Write-Host ''

if ($Mode -eq 'Xampp') {
    $Grants = @(
        @{ Identity = 'Users'; Rights = 'Modify' },
        @{ Identity = 'SYSTEM'; Rights = 'Modify' }
    )
} else {
    $Grants = @(
        @{ Identity = 'IIS_IUSRS'; Rights = 'Modify' },
        @{ Identity = 'IUSR'; Rights = 'Modify' }
    )
}

$Targets = @(
    (Join-Path $Root 'api'),
    (Join-Path $Root 'js'),
    (Join-Path $Root 'css'),
    (Join-Path $Root 'maintenance')
)

Get-ChildItem -Path $Root -File -Include '*.html', '*.php', '.htaccess' -ErrorAction SilentlyContinue |
    ForEach-Object { $Targets += $_.FullName }

$Failed = $false

foreach ($Target in $Targets) {
    if (-not (Test-Path -LiteralPath $Target)) {
        continue
    }
    $Label = Split-Path -Leaf $Target
    Write-Host "  $Label"
    foreach ($Grant in $Grants) {
        try {
            $Acl = Get-Acl -LiteralPath $Target
            $Rule = New-Object System.Security.AccessControl.FileSystemAccessRule(
                $Grant.Identity,
                $Grant.Rights,
                'ContainerInherit, ObjectInherit',
                'None',
                'Allow'
            )
            $Acl.AddAccessRule($Rule)
            Set-Acl -LiteralPath $Target -AclObject $Acl
            if (Test-Path -LiteralPath $Target -PathType Container) {
                Get-ChildItem -LiteralPath $Target -Recurse -Force -ErrorAction SilentlyContinue |
                    ForEach-Object {
                        try {
                            $ChildAcl = Get-Acl -LiteralPath $_.FullName
                            $ChildAcl.AddAccessRule($Rule)
                            Set-Acl -LiteralPath $_.FullName -AclObject $ChildAcl
                        } catch {
                            Write-Warning "Skipped $($_.FullName): $($_.Exception.Message)"
                            $Failed = $true
                        }
                    }
            }
        } catch {
            Write-Warning "Failed on ${Target} ($($Grant.Identity)): $($_.Exception.Message)"
            $Failed = $true
        }
    }
}

Write-Host ''
if ($Failed) {
    Write-Host 'Completed with warnings. Re-run as Administrator.' -ForegroundColor Yellow
    exit 1
}

Write-Host 'Done. Code paths should be writable by the web server.' -ForegroundColor Green
Write-Host 'Verify in maintenance/code_deploy.html - Save a test file.'
