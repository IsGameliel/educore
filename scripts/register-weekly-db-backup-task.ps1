$ErrorActionPreference = 'Stop'

param(
    [string]$TaskName = 'Educore Weekly Database Backup',
    [string]$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path,
    [string]$PhpBinary = 'php',
    [string]$DayOfWeek = 'Sunday',
    [string]$StartTime = '01:00'
)

$backupScript = Join-Path $ProjectRoot 'scripts\weekly-db-backup.ps1'

if (-not (Test-Path -LiteralPath $backupScript)) {
    throw "Backup script not found at $backupScript"
}

$startBoundary = [datetime]::Today.Add([timespan]::Parse($StartTime))
if ($startBoundary -lt (Get-Date)) {
    $startBoundary = $startBoundary.AddDays(1)
}

$actionArgs = @(
    '-ExecutionPolicy', 'Bypass',
    '-File', ('"{0}"' -f $backupScript),
    '-ProjectRoot', ('"{0}"' -f $ProjectRoot),
    '-PhpBinary', ('"{0}"' -f $PhpBinary)
) -join ' '

$action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument $actionArgs
$trigger = New-ScheduledTaskTrigger -Weekly -DaysOfWeek $DayOfWeek -At $startBoundary
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable

Register-ScheduledTask `
    -TaskName $TaskName `
    -Action $action `
    -Trigger $trigger `
    -Settings $settings `
    -Description 'Runs the Educore Laravel database backup once a week.' `
    -Force | Out-Null

Write-Host "Scheduled task '$TaskName' created or updated."
Write-Host "Runs every $DayOfWeek at $StartTime."
