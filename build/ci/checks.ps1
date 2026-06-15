param(
    [string]$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
)

$ErrorActionPreference = 'Stop'
Set-Location $ProjectRoot
$php = (Get-Command php -ErrorAction Stop).Source

$failures = New-Object System.Collections.Generic.List[string]
Get-ChildItem -Recurse -File -Filter *.php | Where-Object {
    $_.FullName -notmatch '\\.git\\'
} | ForEach-Object {
    & $php -l $_.FullName | Out-Null
    if ($LASTEXITCODE -ne 0) {
        $failures.Add($_.FullName)
    }
}

if ($failures.Count -gt 0) {
    $failures | ForEach-Object { Write-Host "PHP lint failure: $_" }
    throw 'PHP lint failed.'
}

& $php tests/run.php
if ($LASTEXITCODE -ne 0) {
    throw 'Architecture checks failed.'
}

Write-Host 'All Frota checks passed.'

