$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$toolsDir = Join-Path $repoRoot 'tools'
$installDir = Join-Path $toolsDir 'Tesseract-OCR'
$installerPath = Join-Path $toolsDir 'tesseract-setup.exe'
$downloadUrl = 'https://github.com/tesseract-ocr/tesseract/releases/download/5.5.0/tesseract-ocr-w64-setup-5.5.0.20241111.exe'

if (!(Test-Path $toolsDir)) {
    New-Item -ItemType Directory -Path $toolsDir | Out-Null
}

Write-Host "Downloading Tesseract installer..."
Invoke-WebRequest -Uri $downloadUrl -OutFile $installerPath

Write-Host "Installing Tesseract into $installDir ..."
Start-Process -FilePath $installerPath -ArgumentList '/S', "/D=$installDir" -Wait

$tesseractExe = Join-Path $installDir 'tesseract.exe'
if (!(Test-Path $tesseractExe)) {
    throw "Install finished but tesseract.exe was not found at: $tesseractExe"
}

Write-Host "Setting user environment variable KYC_TESSERACT_CMD ..."
setx KYC_TESSERACT_CMD $tesseractExe | Out-Null

Write-Host "Install complete."
Write-Host "tesseract.exe: $tesseractExe"
Write-Host "Restart Apache/XAMPP (or your terminal) so env vars refresh."
