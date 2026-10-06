param(
    [switch]$Backup,
    [switch]$SkipBuild,
    [switch]$NoDown,
    [switch]$Dev
)

$DeployDir = $PSScriptRoot
$Root = Split-Path -Parent $DeployDir
. (Join-Path $DeployDir 'common.ps1')

Set-Location -LiteralPath $Root

$env:APP_ENV = 'production'
$env:XDEBUG_MODE = 'off'

$php = Resolve-PhpPath
$git = (Get-Command git.exe -ErrorAction SilentlyContinue).Source
if (-not $git) { Write-Log 'update FAILED: git.exe not found'; exit 1 }
$npm = (Get-Command npm.cmd -ErrorAction SilentlyContinue).Source
$composer = (Get-Command composer.bat -ErrorAction SilentlyContinue).Source
if (-not $composer) { $composer = (Get-Command composer -ErrorAction SilentlyContinue).Source }
if (-not $composer) { Write-Log 'update FAILED: composer not found'; exit 1 }

function Invoke-Step {
    param(
        [string]$Name,
        [string]$Exe,
        [string[]]$Arguments
    )
    Write-Log "update: $Name"
    $previous = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        & $Exe @Arguments
        $code = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $previous
    }
    if ($code -ne 0) {
        Write-Log "update FAILED at '$Name' (exit $code)"
        exit $code
    }
}

function Get-EnvValue {
    param([string]$Key, [string]$Default = '')
    $file = Join-Path $Root '.env.production'
    if (-not (Test-Path -LiteralPath $file)) { return $Default }
    foreach ($line in (Get-Content -LiteralPath $file)) {
        if ($line -match "^\s*$([regex]::Escape($Key))\s*=\s*(.*)$") {
            return $Matches[1].Trim().Trim('"')
        }
    }
    return $Default
}

$before = (& $git rev-parse HEAD 2>$null | Out-String).Trim()
Write-Log "update starting (head=$before)"

if (-not $NoDown) {
    Invoke-Production -Php $php -Arguments @('artisan', 'down', '--retry=15', '--env=production') | Out-Null
}

try {
    $dirty = (& $git status --porcelain 2>$null | Out-String).Trim()
    if ($dirty) {
        Write-Log 'update ABORTED: local files changed, commit or revert them first:'
        $dirty -split "`r?`n" | ForEach-Object { Write-Log "  $_" }
        exit 1
    }

    Invoke-Step -Name 'git pull' -Exe $git -Arguments @('pull', '--ff-only', 'origin', 'main')

    $composerArgs = @('install', '--no-interaction', '--optimize-autoloader')
    if (-not $Dev) { $composerArgs += '--no-dev' }
    Invoke-Step -Name 'composer install' -Exe $composer -Arguments $composerArgs

    if (-not $SkipBuild) {
        if (-not $npm) {
            Write-Log 'update FAILED: npm.cmd not found'
            exit 1
        }
        Invoke-Step -Name 'npm install' -Exe $npm -Arguments @('install', '--ignore-scripts')
        Invoke-Step -Name 'npm run build' -Exe $npm -Arguments @('run', 'build')
    }

    if ($Backup) {
        $stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
        $backupDir = Join-Path $Root 'storage\app\backups'
        if (-not (Test-Path -LiteralPath $backupDir)) { New-Item -ItemType Directory -Path $backupDir -Force | Out-Null }

        $pgDump = $null
        foreach ($candidate in @(
            'C:\Program Files\PostgreSQL\18\bin\pg_dump.exe',
            'C:\Program Files\PostgreSQL\17\bin\pg_dump.exe',
            'C:\Program Files\PostgreSQL\16\bin\pg_dump.exe'
        )) { if (Test-Path -LiteralPath $candidate) { $pgDump = $candidate; break } }

        if ($pgDump) {
            $env:PGPASSWORD = Get-EnvValue -Key 'DB_PASSWORD'
            $dbHost = Get-EnvValue -Key 'DB_HOST' -Default '127.0.0.1'
            $dbPort = Get-EnvValue -Key 'DB_PORT' -Default '5432'
            $dbName = Get-EnvValue -Key 'DB_DATABASE' -Default 'pos_kopinka'
            $dbUser = Get-EnvValue -Key 'DB_USERNAME' -Default 'postgres'
            $file = Join-Path $backupDir "$dbName-$stamp.sql"
            Invoke-Step -Name "pg_dump $dbName" -Exe $pgDump -Arguments @(
                '-h', $dbHost, '-p', $dbPort, '-U', $dbUser, '-d', $dbName,
                '-f', $file, '--no-owner', '--no-privileges'
            )
            Remove-Item Env:\PGPASSWORD -ErrorAction SilentlyContinue
            Write-Log "backup: $file"
        } else {
            Write-Log 'backup skipped: pg_dump.exe not found'
        }
    }

    Invoke-Production -Php $php -Arguments @('artisan', 'migrate', '--force', '--env=production') | Out-Null
    Invoke-Production -Php $php -Arguments @('artisan', 'queue:restart', '--env=production') | Out-Null

    $after = (& $git rev-parse HEAD 2>$null | Out-String).Trim()
    if ($after -and $after -ne $before) {
        Write-Log 'changes applied:'
        (& $git log --oneline "$before..$after" 2>$null) | ForEach-Object { Write-Log "  $_" }
    } else {
        Write-Log 'already up to date'
    }

    Write-Log 'update finished OK'
} finally {
    if (-not $NoDown) {
        Invoke-Production -Php $php -Arguments @('artisan', 'up', '--env=production') | Out-Null
    }
}
