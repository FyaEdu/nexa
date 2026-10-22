# Mengunduh PHP, Composer, dan Node.js versi portable ke folder tools\runtime
# Tidak mengubah PATH Windows dan tidak butuh admin (kecuali VC++ Runtime kalau belum ada).
$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$rt   = Join-Path $root 'runtime'
$tmp  = Join-Path $rt '_unduhan'
New-Item -ItemType Directory -Force -Path $rt, $tmp | Out-Null

function Unduh($url, $out) {
    Write-Host "    mengunduh $url"
    Invoke-WebRequest -Uri $url -OutFile $out -UseBasicParsing
}

# ---------- 0. VC++ Runtime (dibutuhkan PHP) ----------
$vc = Get-ItemProperty 'HKLM:\SOFTWARE\Microsoft\VisualStudio\14.0\VC\Runtimes\x64' -ErrorAction SilentlyContinue
if (-not $vc -or $vc.Installed -ne 1) {
    Write-Host '[0] Memasang Visual C++ Runtime (akan muncul jendela izin admin, klik Yes)...'
    $vcf = Join-Path $tmp 'vc_redist.x64.exe'
    Unduh 'https://aka.ms/vs/17/release/vc_redist.x64.exe' $vcf
    Start-Process -FilePath $vcf -ArgumentList '/install','/quiet','/norestart' -Verb RunAs -Wait
} else { Write-Host '[0] Visual C++ Runtime sudah ada.' }

# ---------- 1. PHP 8.3 ----------
$php = Join-Path $rt 'php'
if (-not (Test-Path (Join-Path $php 'php.exe'))) {
    Write-Host '[1] Memasang PHP 8.3...'
    $zipName = $null
    try {
        $rel = Invoke-RestMethod 'https://windows.php.net/downloads/releases/releases.json'
        $entry = $rel.'8.3'
        $prop = $entry.PSObject.Properties | Where-Object { $_.Name -match '^nts-vs\d+-x64$' } | Select-Object -First 1
        $zipName = $prop.Value.zip.path
    } catch { Write-Host '    releases.json gagal, coba cara lain...' }
    if (-not $zipName) {
        $html = (Invoke-WebRequest 'https://windows.php.net/downloads/releases/' -UseBasicParsing).Content
        $m = [regex]::Matches($html, 'php-8\.3\.\d+-nts-Win32-vs\d+-x64\.zip') | ForEach-Object { $_.Value } | Sort-Object { [version]($_ -replace '^php-(\d+\.\d+\.\d+).*','$1') } -Descending | Select-Object -First 1
        $zipName = $m
    }
    if (-not $zipName) { throw 'Tidak menemukan file PHP 8.3 untuk diunduh.' }
    $zf = Join-Path $tmp 'php.zip'
    try { Unduh "https://windows.php.net/downloads/releases/$zipName" $zf }
    catch { Unduh "https://windows.php.net/downloads/releases/archives/$zipName" $zf }
    if (Test-Path $php) { Remove-Item $php -Recurse -Force }
    Expand-Archive -Path $zf -DestinationPath $php -Force

} else { Write-Host '[1] PHP sudah ada.' }


# php.ini minimal ditulis ulang setiap kali (lebih andal daripada mengedit php.ini-development)
$iniLines = @(
  '; dibuat otomatis oleh MULAI-NEXA',
  ('extension_dir = "' + (Join-Path $php 'ext') + '"'),
  'memory_limit = 512M',
  'date.timezone = Asia/Jakarta',
  'allow_url_fopen = On'
)
$exts = 'curl','fileinfo','intl','mbstring','openssl','pdo_sqlite','sqlite3','zip'
foreach ($e in $exts) { $iniLines += "extension=$e" }
Set-Content -Path (Join-Path $php 'php.ini') -Value $iniLines -Encoding ASCII
$mods = (& (Join-Path $php 'php.exe') -m 2>&1) | Out-String
$hilang = @()
foreach ($e in $exts) { if ($mods -notmatch "(?mi)^$e\s*$") { $hilang += $e } }
if ($hilang.Count -gt 0) {
    Write-Host '[!] Ekstensi PHP berikut gagal aktif:' ($hilang -join ', ')
    Write-Host '--- keluaran php -m ---'
    Write-Host $mods
    throw 'PHP belum siap. Screenshot pesan ini dan kirim ke yang bikin.'
}
Set-Content -Path (Join-Path $php 'php.ini.ok') -Value 'ok' -Encoding ASCII
Write-Host '    PHP siap dengan semua ekstensi.'

# ---------- 2. Composer ----------
$composer = Join-Path $rt 'composer.phar'
if (-not (Test-Path $composer)) {
    Write-Host '[2] Memasang Composer...'
    Unduh 'https://getcomposer.org/download/latest-stable/composer.phar' $composer
} else { Write-Host '[2] Composer sudah ada.' }
Set-Content -Path (Join-Path $rt 'composer.bat') -Value '@php "%~dp0composer.phar" %*' -Encoding ASCII

# ---------- 3. Node.js LTS ----------
$node = Join-Path $rt 'node'
if (-not (Test-Path (Join-Path $node 'node.exe'))) {
    Write-Host '[3] Memasang Node.js LTS...'
    $idx = Invoke-RestMethod 'https://nodejs.org/dist/index.json'
    $ver = ($idx | Where-Object { $_.lts } | Select-Object -First 1).version
    $nf = Join-Path $tmp 'node.zip'
    Unduh "https://nodejs.org/dist/$ver/node-$ver-win-x64.zip" $nf
    $x = Join-Path $tmp 'node_x'
    if (Test-Path $x) { Remove-Item $x -Recurse -Force }
    Expand-Archive -Path $nf -DestinationPath $x -Force
    if (Test-Path $node) { Remove-Item $node -Recurse -Force }
    Move-Item (Get-ChildItem $x | Select-Object -First 1).FullName $node
} else { Write-Host '[3] Node.js sudah ada.' }

Remove-Item $tmp -Recurse -Force -ErrorAction SilentlyContinue
Write-Host 'Semua alat sudah siap.'
