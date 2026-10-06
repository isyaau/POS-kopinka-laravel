param(
    [int]$Port = 80,
    [string]$BindHost = '0.0.0.0'
)

. (Join-Path $PSScriptRoot 'common.ps1')

Set-Location -LiteralPath (Get-ProjectRoot)

$env:APP_ENV = 'production'
$env:XDEBUG_MODE = 'off'

$php = Resolve-PhpPath
Write-Log "web service starting (php=$php, port=$Port)"

while ($true) {
    $exitCode = Invoke-Production -Php $php -Arguments @('artisan', 'migrate', '--force', '--env=production')
    if ($exitCode -ne 0) {
        Write-Log 'migrate failed, retrying in 10s'
        Start-Sleep -Seconds 10
        continue
    }

    $busy = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
    if ($busy) {
        Write-Log "port $Port already in use, waiting"
        Start-Sleep -Seconds 5
        continue
    }

    Invoke-Production -Php $php -Arguments @(
        'artisan', 'serve', "--host=$BindHost", '--port', "$Port", '--env=production'
    ) | Out-Null

    Write-Log "artisan serve exited (code $LASTEXITCODE), restarting in 5s"
    Start-Sleep -Seconds 5
}
