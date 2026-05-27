$ErrorActionPreference = 'Stop'

param(
    [string]$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path,
    [string]$PhpBinary = 'php',
    [string]$BackupPath = '',
    [string]$Connection = '',
    [string]$LogDirectory = ''
)

Set-Location -LiteralPath $ProjectRoot

if ([string]::IsNullOrWhiteSpace($LogDirectory)) {
    $LogDirectory = Join-Path $ProjectRoot 'storage\logs\backups'
}

if (-not (Test-Path -LiteralPath $LogDirectory)) {
    New-Item -ItemType Directory -Path $LogDirectory -Force | Out-Null
}

$timestamp = Get-Date -Format 'yyyy-MM-dd_HHmmss'
$logFile = Join-Path $LogDirectory "weekly-db-backup_$timestamp.log"

$artisanArgs = @('artisan', 'backup:database')

if (-not [string]::IsNullOrWhiteSpace($Connection)) {
    $artisanArgs += "--connection=$Connection"
}

if (-not [string]::IsNullOrWhiteSpace($BackupPath)) {
    $artisanArgs += "--path=$BackupPath"
}

"[$(Get-Date -Format s)] Starting database backup from $ProjectRoot" | Tee-Object -FilePath $logFile -Append

try {
    & $PhpBinary @artisanArgs 2>&1 | Tee-Object -FilePath $logFile -Append

    if ($LASTEXITCODE -ne 0) {
        throw "Backup command exited with code $LASTEXITCODE."
    }

    "[$(Get-Date -Format s)] Database backup completed successfully." | Tee-Object -FilePath $logFile -Append
    exit 0
}
catch {
    "[$(Get-Date -Format s)] Database backup failed: $($_.Exception.Message)" | Tee-Object -FilePath $logFile -Append
    exit 1
}
