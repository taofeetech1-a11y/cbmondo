param([switch]$SkipBuild)
$ErrorActionPreference = 'Stop'
$projectRoot = $PSScriptRoot
Set-Location -LiteralPath $projectRoot
if (-not $SkipBuild) {
    npm run build
    if ($LASTEXITCODE -ne 0) { throw 'Asset build failed.' }
}
if (-not (Test-Path -LiteralPath "$projectRoot/public/build/manifest.json")) { throw 'Build assets first.' }
$releaseId = (Get-Date -Format 'yyyyMMdd-HHmmss') + '-' + [guid]::NewGuid().ToString('N').Substring(0,6)
$releaseRoot = Join-Path $projectRoot "storage/app/cpanel-$releaseId"
$appRoot = Join-Path $releaseRoot 'cbmondo'
New-Item -ItemType Directory -Path $appRoot | Out-Null
foreach ($directory in @('app','config','routes','resources','database/migrations','database/seeders','database/factories')) {
    $destination = Join-Path $appRoot $directory
    New-Item -ItemType Directory -Path $destination -Force | Out-Null
    Copy-Item -Path (Join-Path $projectRoot "$directory/*") -Destination $destination -Recurse -Force
}
foreach ($directory in @('bootstrap/cache','storage/app/private','storage/app/public','storage/framework/cache/data','storage/framework/sessions','storage/framework/views','storage/logs','public')) {
    New-Item -ItemType Directory -Path (Join-Path $appRoot $directory) -Force | Out-Null
}
Copy-Item -Path "$projectRoot/bootstrap/*.php" -Destination "$appRoot/bootstrap"
foreach ($entry in Get-ChildItem -LiteralPath "$projectRoot/public" -Force) {
    if ($entry.Name -notin @('hot','storage','fonts-manifest.dev.json') -and -not ($entry.Attributes -band [IO.FileAttributes]::ReparsePoint)) {
        Copy-Item -LiteralPath $entry.FullName -Destination "$appRoot/public" -Recurse -Force
    }
}
foreach ($file in @('artisan','composer.json','composer.lock','.env.cpanel.example','CPANEL.md')) {
    Copy-Item -LiteralPath (Join-Path $projectRoot $file) -Destination $appRoot
}
Write-Output "Release staging: $appRoot"
composer install --working-dir="$appRoot" --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts
if ($LASTEXITCODE -ne 0) { throw "Dependency installation failed. Staging retained at $appRoot." }
Add-Type -AssemblyName System.IO.Compression.FileSystem
$archive = "$releaseRoot.zip"
$zip = [IO.Compression.ZipFile]::Open($archive, [IO.Compression.ZipArchiveMode]::Create)
try {
    foreach ($entry in Get-ChildItem -LiteralPath $releaseRoot -Recurse -Force) {
        $relative = $entry.FullName.Substring($releaseRoot.Length + 1).Replace('\', '/')
        if ($entry.PSIsContainer) {
            $zip.CreateEntry($relative + '/') | Out-Null
        } else {
            [IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $entry.FullName, $relative, [IO.Compression.CompressionLevel]::Optimal) | Out-Null
        }
    }
} finally {
    $zip.Dispose()
}
Write-Output "Upload archive: $archive"
Get-FileHash -LiteralPath $archive -Algorithm SHA256
