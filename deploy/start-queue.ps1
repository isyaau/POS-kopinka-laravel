. (Join-Path $PSScriptRoot 'common.ps1')

Set-Location -LiteralPath (Get-ProjectRoot)

$env:APP_ENV = 'production'
$env:XDEBUG_MODE = 'off'

$php = Resolve-PhpPath
Write-Log "queue service starting (php=$php)"

while ($true) {
    Invoke-Production -Php $php -Arguments @(
        'artisan', 'queue:work', '--env=production', '--tries=3', '--timeout=60', '--sleep=3'
    ) | Out-Null

    Write-Log "queue:work exited (code $LASTEXITCODE), restarting in 5s"
    Start-Sleep -Seconds 5
}
