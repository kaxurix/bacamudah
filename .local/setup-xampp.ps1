param([string]$XamppRoot = 'C:\xampp')
$ErrorActionPreference = 'Stop'
$sourceRoot = [IO.Path]::GetFullPath((Split-Path -Parent $PSScriptRoot))
$xamppPath = [IO.Path]::GetFullPath($XamppRoot).TrimEnd('\')
$targetRoot = [IO.Path]::GetFullPath((Join-Path $xamppPath 'htdocs\bacamudah'))
$backupRoot = Join-Path $PSScriptRoot 'xampp-migration'
$mysqlClient = Join-Path $xamppPath 'mysql\bin\mysql.exe'
$mysqlAdmin = Join-Path $xamppPath 'mysql\bin\mysqladmin.exe'
$dumpBinary = Join-Path $xamppPath 'mysql\bin\mysqldump.exe'
$phpBinary = Join-Path $xamppPath 'php\php.exe'
$apacheBinary = Join-Path $xamppPath 'apache\bin\httpd.exe'
$mysqlBinary = Join-Path $xamppPath 'mysql\bin\mysqld.exe'
$expectedPreviewData = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot 'runtime\mysql')).TrimEnd('\')
$expectedXamppData = [IO.Path]::GetFullPath((Join-Path $xamppPath 'mysql\data')).TrimEnd('\')
if ($targetRoot -eq $sourceRoot -or !$targetRoot.StartsWith($xamppPath + '\htdocs\', [StringComparison]::OrdinalIgnoreCase)) { throw 'Folder tujuan tidak valid.' }
if (Test-Path -LiteralPath $targetRoot) { throw 'Folder tujuan sudah ada. Skrip berhenti agar tidak menimpa proyek lain.' }
if (Test-Path -LiteralPath (Join-Path $expectedXamppData 'bacamudah')) { throw 'Database bacamudah sudah ada di XAMPP. Skrip berhenti agar data tujuan tidak tertimpa.' }
foreach ($binary in @($mysqlClient,$mysqlAdmin,$dumpBinary,$phpBinary,$apacheBinary,$mysqlBinary)) {
    if (!(Test-Path -LiteralPath $binary)) { throw "Program tidak ditemukan: $binary" }
}
function Get-DatabaseValue([string]$Sql) {
    $result = & $mysqlClient --no-defaults --host=127.0.0.1 --port=3306 --user=root --raw --batch --skip-column-names -e $Sql
    if ($LASTEXITCODE -ne 0) { throw 'Pemeriksaan database gagal.' }
    return ($result -join "`n").Trim()
}
function Wait-Port([int]$Port) {
    for ($attempt = 0; $attempt -lt 60; $attempt++) {
        $connection = [Net.Sockets.TcpClient]::new()
        try { $connection.Connect('127.0.0.1', $Port); return }
        catch { Start-Sleep -Milliseconds 250 }
        finally { $connection.Dispose() }
    }
    throw "Layanan pada port $Port belum siap."
}
$currentData = [IO.Path]::GetFullPath((Get-DatabaseValue 'SELECT @@datadir')).TrimEnd('\')
if ($currentData -ne $expectedPreviewData) { throw 'Database aktif bukan database pratinjau proyek ini. Tidak ada layanan yang dihentikan.' }
& $apacheBinary -t
if ($LASTEXITCODE -ne 0) { throw 'Konfigurasi Apache belum valid.' }

# Salin sebelum mengubah layanan. Folder sumber tetap ada sebagai cadangan.
New-Item -ItemType Directory -Path $targetRoot | Out-Null
$copyLog = Join-Path $backupRoot 'copy.log'
& robocopy.exe $sourceRoot $targetRoot /E /XJ /R:1 /W:1 /XD (Join-Path $PSScriptRoot 'runtime') $backupRoot /LOG:$copyLog /NFL /NDL /NJH /NJS
if ($LASTEXITCODE -gt 7) { throw "Penyalinan gagal. Lihat $copyLog. Layanan asal belum diubah." }
foreach ($relative in @('index.php','config\database.php','assets\css\style.css','assets\js\reader.js','uploads')) {
    if (!(Test-Path -LiteralPath (Join-Path $targetRoot $relative))) { throw "Hasil salinan tidak lengkap: $relative" }
}

# Hentikan hanya server PHP milik launcher ini, jika masih berjalan.
$pidFile = Join-Path $PSScriptRoot 'runtime\web-process.id'
if (Test-Path -LiteralPath $pidFile) {
    $webProcessId = [int](Get-Content -LiteralPath $pidFile)
    $webProcess = Get-CimInstance Win32_Process -Filter "ProcessId=$webProcessId"
    if ($webProcess -and $webProcess.Name -eq 'php.exe' -and $webProcess.CommandLine.Contains($sourceRoot)) { Stop-Process -Id $webProcessId }
}
$before = & $phpBinary (Join-Path $backupRoot 'inventory.php')
if ($LASTEXITCODE -ne 0) { throw 'Pemeriksaan data sumber gagal.' }
$before | Set-Content -LiteralPath (Join-Path $backupRoot 'before.json') -Encoding UTF8
$dumpFile = Join-Path $backupRoot 'bacamudah-current.sql'
& $dumpBinary --no-defaults --host=127.0.0.1 --port=3306 --user=root --single-transaction --routines --triggers --hex-blob --default-character-set=utf8mb4 --databases bacamudah "--result-file=$dumpFile"
if ($LASTEXITCODE -ne 0) { throw 'Ekspor gagal; database pratinjau belum dihentikan.' }
& $mysqlAdmin --no-defaults --host=127.0.0.1 --port=3306 --user=root shutdown
if ($LASTEXITCODE -ne 0) { throw 'Database pratinjau tidak dapat dihentikan dengan normal.' }
Start-Sleep -Milliseconds 700
$mysqlConfig = Join-Path $xamppPath 'mysql\bin\my.ini'
Start-Process -FilePath $mysqlBinary -ArgumentList "--defaults-file=`"$mysqlConfig`" --standalone" -WorkingDirectory $xamppPath -WindowStyle Hidden | Out-Null
Wait-Port 3306
$activeData = [IO.Path]::GetFullPath((Get-DatabaseValue 'SELECT @@datadir')).TrimEnd('\')
if ($activeData -ne $expectedXamppData) { throw 'Layanan yang aktif bukan MySQL standar XAMPP. Impor dibatalkan.' }
if ((Get-DatabaseValue "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='bacamudah'") -ne '0') { throw 'Database tujuan berisi tabel. Impor dibatalkan.' }
$import = Start-Process -FilePath $mysqlClient -ArgumentList '--no-defaults --host=127.0.0.1 --port=3306 --user=root --default-character-set=utf8mb4' -RedirectStandardInput $dumpFile -RedirectStandardError (Join-Path $backupRoot 'import-error.log') -WindowStyle Hidden -Wait -PassThru
if ($import.ExitCode -ne 0) { throw 'Impor gagal. Cadangan SQL dan database sumber masih tersedia.' }
$after = & $phpBinary (Join-Path $backupRoot 'inventory.php')
if ($LASTEXITCODE -ne 0) { throw 'Pemeriksaan data tujuan gagal.' }
$after | Set-Content -LiteralPath (Join-Path $backupRoot 'after.json') -Encoding UTF8
if (($before -join "`n") -ne ($after -join "`n")) { throw 'Data tujuan tidak sama dengan sumber. Periksa before.json dan after.json.' }
& $phpBinary (Join-Path $targetRoot 'scripts\migrate.php')
if ($LASTEXITCODE -ne 0) { throw 'Migrasi aplikasi gagal.' }
if (!(Get-Process httpd -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath $apacheBinary -WorkingDirectory (Join-Path $xamppPath 'apache\bin') -WindowStyle Hidden | Out-Null
}
Wait-Port 80
$response = Invoke-WebRequest -UseBasicParsing 'http://localhost/bacamudah/login.php'
if ($response.StatusCode -ne 200 -or $response.Content -notmatch 'Masuk ke Baca Mudah') { throw 'Halaman login Apache belum dapat dibuka.' }
Write-Output "Selesai: $targetRoot"
Write-Output 'Buka http://localhost/bacamudah/'
Write-Output 'Folder sumber dan cadangan database dipertahankan.'
