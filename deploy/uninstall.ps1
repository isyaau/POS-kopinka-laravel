$DeployDir = $PSScriptRoot
$Root = Split-Path -Parent $DeployDir
. (Join-Path $DeployDir 'common.ps1')

foreach ($name in @('Kopinka Web', 'Kopinka Queue')) {
    Stop-ScheduledTask -TaskName $name -ErrorAction SilentlyContinue
    Unregister-ScheduledTask -TaskName $name -Confirm:$false -ErrorAction SilentlyContinue
    Write-Log "task removed: $name"
}

netsh advfirewall firewall delete rule name='POS Kopinka HTTP' | Out-Null
Write-Log 'firewall rule removed'

Write-Host 'Selesai. File proyek & .env.production tidak dihapus.'
