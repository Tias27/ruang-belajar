$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..')
# Run one worker per deployment to preserve conversation order.
php artisan queue:work ai_memory --queue=ai-memory --sleep=2 --timeout=600 --tries=0
