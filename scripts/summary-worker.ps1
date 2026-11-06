$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..')
php artisan ai:summary-worker
