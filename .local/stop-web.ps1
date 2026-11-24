$ErrorActionPreference = 'Stop'
$runtimeRoot = Join-Path $PSScriptRoot 'runtime'
$projectRoot = Split-Path -Parent $PSScriptRoot
foreach ($entry in @(@{Name='web'; Executable='php.exe'}, @{Name='mysql'; Executable='mysqld.exe'})) {
    $idFile = Join-Path $runtimeRoot ($entry.Name + '-process.id')
    if (!(Test-Path -LiteralPath $idFile)) { continue }
    $processId = [int](Get-Content -LiteralPath $idFile)
    $process = Get-CimInstance Win32_Process -Filter "ProcessId=$processId" -ErrorAction SilentlyContinue
    if ($process -and $process.Name -eq $entry.Executable -and $process.CommandLine.Contains($projectRoot)) {
        if ($entry.Name -eq 'mysql') {
            & 'C:\xampp\mysql\bin\mysqladmin.exe' --no-defaults --host=127.0.0.1 --port=3306 --user=root shutdown
            if ($LASTEXITCODE -ne 0) { throw 'MySQL tidak dapat dihentikan secara normal.' }
        } else {
            Stop-Process -Id $processId
        }
        Write-Output ($entry.Name + ' dihentikan.')
    }
}
