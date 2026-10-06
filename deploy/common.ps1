Set-StrictMode -Version Latest
$ErrorActionPreference = 'Continue'

$script:DeployDir = $PSScriptRoot

function Get-ProjectRoot {
    return (Split-Path -Parent $script:DeployDir)
}

function Resolve-PhpPath {
    $marker = Join-Path $script:DeployDir 'php.path'
    if (Test-Path -LiteralPath $marker) {
        $saved = (Get-Content -LiteralPath $marker -Raw).Trim()
        if ($saved -and (Test-Path -LiteralPath $saved)) { return $saved }
    }

    $cmd = Get-Command php.exe -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }

    $wamp = 'C:\wamp64\bin\php'
    if (Test-Path -LiteralPath $wamp) {
        $found = @(Get-ChildItem -LiteralPath $wamp -Directory -ErrorAction SilentlyContinue |
            ForEach-Object { Join-Path $_.FullName 'php.exe' } |
            Where-Object { Test-Path -LiteralPath $_ })
        if ($found.Count -gt 0) { return $found[$found.Count - 1] }
    }

    throw 'php.exe not found. Run deploy\install.ps1 first.'
}

function Write-Log {
    param([string]$Message)
    $line = '[{0}] {1}' -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $Message
    Write-Host $line
    $logFile = Join-Path (Get-ProjectRoot) 'storage\logs\services.log'
    try {
        if (Test-Path -LiteralPath (Split-Path -Parent $logFile)) {
            Add-Content -LiteralPath $logFile -Value $line -ErrorAction SilentlyContinue
        }
    } catch { }
}

function Get-LanIPv4 {
    try {
        $route = Get-NetRoute -AddressFamily IPv4 -DestinationPrefix '0.0.0.0/0' -ErrorAction SilentlyContinue |
            Sort-Object -Property RouteMetric | Select-Object -First 1
        if ($route) {
            $ip = Get-NetIPAddress -AddressFamily IPv4 -InterfaceIndex $route.ifIndex -ErrorAction SilentlyContinue |
                Where-Object { $_.IPAddress -notlike '127.*' } | Select-Object -First 1
            if ($ip) { return $ip.IPAddress }
        }
    } catch { }

    $fallback = Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue |
        Where-Object { $_.IPAddress -notlike '127.*' -and $_.IPAddress -notlike '169.254.*' } |
        Select-Object -First 1
    if ($fallback) { return $fallback.IPAddress }
    return $null
}

function Invoke-Production {
    param(
        [Parameter(Mandatory = $true)][string]$Php,
        [Parameter(Mandatory = $true)][string[]]$Arguments
    )
    $previous = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $out = & $Php @Arguments 2>&1
        $code = $LASTEXITCODE
        foreach ($line in $out) { Write-Log "$line" }
        return $code
    } finally {
        $ErrorActionPreference = $previous
    }
}
