param(
    [switch]$NoElevate,
    [switch]$Build
)

$DeployDir = $PSScriptRoot
$Root = Split-Path -Parent $DeployDir
. (Join-Path $DeployDir 'common.ps1')

$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()
).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin -and -not $NoElevate) {
    try {
        Start-Process powershell.exe -Verb RunAs -ArgumentList @(
            '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', "`"$PSCommandPath`""
        ) -Wait
        exit
    } catch {
        Write-Warning 'Percobaan elevation dibatalkan. Pakai task untuk user login (bukan boot).'
        $NoElevate = $true
    }
}

$php = Resolve-PhpPath
Set-Content -LiteralPath (Join-Path $DeployDir 'php.path') -Value $php -NoNewline
Write-Log "php: $php"

if (-not (Test-Path -LiteralPath $Root)) { throw "project root not found: $Root" }
Set-Location -LiteralPath $Root

$ip = Get-LanIPv4
if (-not $ip) { $ip = '127.0.0.1' }
Write-Log "LAN IP: $ip"

$envFile = Join-Path $Root '.env.production'
if (-not (Test-Path -LiteralPath $envFile)) {
    $source = @('.env', '.env.example') |
        ForEach-Object { Join-Path $Root $_ } |
        Where-Object { Test-Path -LiteralPath $_ } |
        Select-Object -First 1
    if (-not $source) { throw '.env / .env.example not found' }

    Copy-Item -LiteralPath $source -Destination $envFile
    $content = Get-Content -LiteralPath $envFile -Raw
    $content = [regex]::Replace($content, '(?m)^APP_ENV=.*$', 'APP_ENV=production')
    $content = [regex]::Replace($content, '(?m)^APP_DEBUG=.*$', 'APP_DEBUG=false')
    $content = [regex]::Replace($content, '(?m)^APP_URL=.*$', "APP_URL=http://$ip")
    Set-Content -LiteralPath $envFile -Value $content -NoNewline
    Write-Log ".env.production created (source: $(Split-Path -Leaf $source))"

    if ($content -notmatch '(?m)^APP_KEY=base64:.+$') {
        $keyCode = Invoke-Production -Php $php -Arguments @('artisan', 'key:generate', '--env=production', '--force')
        if ($keyCode -eq 0) { Write-Log 'APP_KEY generated' } else { Write-Warning 'APP_KEY generation failed' }
    }
} else {
    Write-Log '.env.production already exists, left untouched'
}

if ($Build) {
    Write-Log 'building frontend assets...'
    $npmCmd = (Get-Command npm.cmd -ErrorAction SilentlyContinue).Source
    if (-not $npmCmd) { throw 'npm.cmd not found' }
    & $npmCmd install --ignore-scripts
    & $npmCmd run build
}
if (-not (Test-Path -LiteralPath (Join-Path $Root 'public\build'))) {
    Write-Warning 'public/build belum ada. Jalankan: npm install && npm run build'
}

$tasks = @(
    @{ Name = 'Kopinka Web';    Script = (Join-Path $DeployDir 'start-web.ps1') },
    @{ Name = 'Kopinka Queue';  Script = (Join-Path $DeployDir 'start-queue.ps1') }
)

function Register-AppTask {
    param(
        [string]$TaskName,
        [string]$ScriptPath,
        [bool]$SystemWide
    )

    $arg = '-NoProfile -ExecutionPolicy Bypass -File "' + $ScriptPath + '"'
    $action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument $arg

    if ($SystemWide) {
        $trigger = New-ScheduledTaskTrigger -AtStartup
        $principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest
    } else {
        $trigger = New-ScheduledTaskTrigger -AtLogOn -User $env:USERNAME
        $principal = New-ScheduledTaskPrincipal -UserId "$env:USERDOMAIN\$env:USERNAME" -LogonType Interactive -RunLevel Limited
    }

    $settings = New-ScheduledTaskSettingsSet `
        -StartWhenAvailable `
        -ExecutionTimeLimit ([TimeSpan]::Zero) `
        -RestartCount 10 `
        -RestartInterval (New-TimeSpan -Minutes 1) `
        -MultipleInstances IgnoreNew

    try {
        Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger `
            -Principal $principal -Settings $settings -Force -ErrorAction Stop | Out-Null
        Write-Log "task registered: $TaskName"
    } catch {
        Write-Warning "gagal membuat task $TaskName : $($_.Exception.Message)"
    }
}

if ($isAdmin) {
    try {
        Set-Service -Name 'postgresql-x64-18' -StartupType Automatic -ErrorAction Stop
        if ((Get-Service -Name 'postgresql-x64-18').Status -ne 'Running') { Start-Service 'postgresql-x64-18' }
    } catch { Write-Warning "pgsql service: $($_.Exception.Message)" }

    $rule = 'POS Kopinka HTTP'
    netsh advfirewall firewall delete rule name="$rule" | Out-Null
    netsh advfirewall firewall add rule name="$rule" dir=in action=allow protocol=TCP localport=80 profile=any | Out-Null
    Write-Log 'firewall rule added (TCP 80)'

    foreach ($task in $tasks) { Register-AppTask -TaskName $task.Name -ScriptPath $task.Script -SystemWide $true }
    Write-Log 'tasks registered (start at boot, run as SYSTEM)'
} else {
    foreach ($task in $tasks) { Register-AppTask -TaskName $task.Name -ScriptPath $task.Script -SystemWide $false }
    Write-Log 'tasks registered (start at logon, current user)'
}

Invoke-Production -Php $php -Arguments @('artisan', 'migrate', '--force', '--env=production') | Out-Null

foreach ($task in $tasks) { Start-ScheduledTask -TaskName $task.Name -ErrorAction SilentlyContinue }
Start-Sleep -Seconds 5

Write-Host ''
Write-Host "URL aplikasi : http://$ip"
foreach ($task in $tasks) {
    $registered = Get-ScheduledTask -TaskName $task.Name -ErrorAction SilentlyContinue
    if (-not $registered) {
        Write-Host ('{0,-16}: NOT REGISTERED' -f $task.Name)
        continue
    }
    $info = Get-ScheduledTaskInfo -TaskName $task.Name -ErrorAction SilentlyContinue
    Write-Host ('{0,-16}: {1} (last result: {2})' -f $task.Name, $registered.State, $info.LastTaskResult)
}
Write-Host ''
Write-Host 'Test: buka http://<IP-PC-INI> di browser, dari PC lain di jaringan yang sama.'
