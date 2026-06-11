$ErrorActionPreference = 'Stop'

$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$outputDir = Join-Path $root 'storage/app/deploy'
$zipPath = Join-Path $outputDir "ruangbelajar-ai-$timestamp.zip"
$ignoreFile = Join-Path $root '.deployignore'

if (-not (Test-Path -LiteralPath $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir | Out-Null
}

$ignored = @()
if (Test-Path -LiteralPath $ignoreFile) {
    $ignored = Get-Content -LiteralPath $ignoreFile |
        ForEach-Object { $_.Trim().TrimEnd('/') -replace '/', '\' } |
        Where-Object { $_ -and -not $_.StartsWith('#') }
}

$ignored += @(
    'storage\app\deploy',
    'storage\logs',
    '.deployignore'
)

function Test-DeployIgnored {
    param([string] $RelativePath)

    $normalized = $RelativePath.TrimEnd('\')
    if ($normalized.StartsWith('.\')) {
        $normalized = $normalized.Substring(2)
    }

    foreach ($pattern in $ignored) {
        if ($normalized.Equals($pattern, [System.StringComparison]::OrdinalIgnoreCase)) {
            return $true
        }

        if ($normalized.StartsWith($pattern + '\', [System.StringComparison]::OrdinalIgnoreCase)) {
            return $true
        }
    }

    return $false
}

$tempDir = Join-Path $env:TEMP "ruangbelajar-deploy-$timestamp"
if (Test-Path -LiteralPath $tempDir) {
    Remove-Item -LiteralPath $tempDir -Recurse -Force
}
New-Item -ItemType Directory -Path $tempDir | Out-Null

try {
    Get-ChildItem -LiteralPath $root -Force | ForEach-Object {
        $relative = $_.Name
        if (Test-DeployIgnored $relative) {
            return
        }

        $destination = Join-Path $tempDir $relative
        Copy-Item -LiteralPath $_.FullName -Destination $destination -Recurse -Force
    }

    Get-ChildItem -LiteralPath $tempDir -Recurse -Force | ForEach-Object {
        $relative = $_.FullName.Substring($tempDir.Length + 1)
        if (Test-DeployIgnored $relative) {
            Remove-Item -LiteralPath $_.FullName -Recurse -Force
        }
    }

    @(
        'bootstrap/cache',
        'storage/app/private',
        'storage/app/public',
        'storage/framework/cache',
        'storage/framework/cache/data',
        'storage/framework/sessions',
        'storage/framework/testing',
        'storage/framework/views',
        'storage/logs'
    ) | ForEach-Object {
        $requiredDir = Join-Path $tempDir $_
        if (-not (Test-Path -LiteralPath $requiredDir)) {
            New-Item -ItemType Directory -Path $requiredDir -Force | Out-Null
        }

        $keepFile = Join-Path $requiredDir '.gitignore'
        if (-not (Test-Path -LiteralPath $keepFile)) {
            Set-Content -LiteralPath $keepFile -Value "*" -Encoding ASCII
        }
    }

    Compress-Archive -Path (Join-Path $tempDir '*') -DestinationPath $zipPath -Force

    if (-not (Test-Path -LiteralPath $zipPath)) {
        throw "Zip deploy gagal dibuat: $zipPath"
    }

    $zip = Get-Item -LiteralPath $zipPath
    if ($zip.Length -le 0) {
        throw "Zip deploy kosong: $zipPath"
    }

    Write-Output "Deploy zip dibuat: $zipPath"
} finally {
    if (Test-Path -LiteralPath $tempDir) {
        Remove-Item -LiteralPath $tempDir -Recurse -Force
    }
}
