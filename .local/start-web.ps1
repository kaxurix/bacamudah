$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$runtimeRoot = Join-Path $PSScriptRoot 'runtime'
$phpBinary = 'C:\php84\php.exe'
$mysqlRoot = 'C:\xampp\mysql'
$mysqlBinary = Join-Path $mysqlRoot 'bin\mysqld.exe'
$dataRoot = Join-Path $runtimeRoot 'mysql'
New-Item -ItemType Directory -Path $runtimeRoot,(Join-Path $runtimeRoot 'sessions'),(Join-Path $runtimeRoot 'tmp') -Force | Out-Null

function Test-LocalPort([int]$Port) {
    $connection = New-Object System.Net.Sockets.TcpClient
    try { $connection.Connect('127.0.0.1', $Port); return $true }
    catch { return $false }
    finally { $connection.Dispose() }
}

if (!(Test-Path -LiteralPath (Join-Path $dataRoot 'mysql'))) {
    & (Join-Path $mysqlRoot 'bin\mysql_install_db.exe') "--datadir=$dataRoot" --port=3306
    if ($LASTEXITCODE -ne 0) { throw 'Inisialisasi database gagal.' }
}
if (!(Test-LocalPort 3306)) {
    $mysqlConfig = Join-Path $runtimeRoot 'mysql-local.ini'
    $forwardData = $dataRoot.Replace('\','/')
    $forwardRuntime = $runtimeRoot.Replace('\','/')
    $config = @"
[mysqld]
basedir=C:/xampp/mysql
datadir="$forwardData"
tmpdir="$forwardRuntime/tmp"
port=3306
bind-address=127.0.0.1
skip-name-resolve
pid-file="$forwardRuntime/mysql.pid"
log-error="$forwardRuntime/mysql-error.log"
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci
"@
    [IO.File]::WriteAllText($mysqlConfig, $config, [Text.UTF8Encoding]::new($false))
    $mysqlProcess = Start-Process -FilePath $mysqlBinary -ArgumentList "--defaults-file=`"$mysqlConfig`"" -WorkingDirectory $projectRoot -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $runtimeRoot 'mysql-output.log') -RedirectStandardError (Join-Path $runtimeRoot 'mysql-stderr.log')
    $mysqlProcess.Id | Set-Content -LiteralPath (Join-Path $runtimeRoot 'mysql-process.id')
    for ($attempt = 0; $attempt -lt 40 -and !(Test-LocalPort 3306); $attempt++) { Start-Sleep -Milliseconds 250 }
    if (!(Test-LocalPort 3306)) { throw 'MySQL belum siap. Lihat .local/runtime/mysql-error.log.' }
}
& $phpBinary (Join-Path $PSScriptRoot 'import-database.php')
if ($LASTEXITCODE -ne 0) { throw 'Impor database gagal.' }
& $phpBinary (Join-Path $projectRoot 'scripts/migrate.php')
if ($LASTEXITCODE -ne 0) { throw 'Migrasi database gagal.' }

if (!(Test-LocalPort 8000)) {
    $sessions = Join-Path $runtimeRoot 'sessions'
    $router = Join-Path $PSScriptRoot 'router.php'
    $phpArguments = "-d session.save_path=`"$sessions`" -S 127.0.0.1:8000 -t `"$projectRoot`" `"$router`""
    $webProcess = Start-Process -FilePath $phpBinary -ArgumentList $phpArguments -WorkingDirectory $projectRoot -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $runtimeRoot 'web-output.log') -RedirectStandardError (Join-Path $runtimeRoot 'web-error.log')
    $webProcess.Id | Set-Content -LiteralPath (Join-Path $runtimeRoot 'web-process.id')
    for ($attempt = 0; $attempt -lt 20 -and !(Test-LocalPort 8000); $attempt++) { Start-Sleep -Milliseconds 250 }
    if (!(Test-LocalPort 8000)) { throw 'Server PHP belum siap. Lihat .local/runtime/web-error.log.' }
}
Write-Output 'Baca Mudah: http://127.0.0.1:8000/'
