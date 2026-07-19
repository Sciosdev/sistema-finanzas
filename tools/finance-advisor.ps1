[CmdletBinding()]
param(
    [ValidateSet('setup', 'snapshot', 'config-path')]
    [string] $Action = 'snapshot',

    [string] $BaseUrl = 'https://finanzas.xaanal.com'
)

$ErrorActionPreference = 'Stop'
$configDirectory = Join-Path $env:LOCALAPPDATA 'FinanzasAdvisor'
$configPath = Join-Path $configDirectory 'credential.json'

function Test-AdvisorBaseUrl {
    param([string] $Value)

    $parsed = $null
    return [Uri]::TryCreate($Value, [UriKind]::Absolute, [ref] $parsed) `
        -and $parsed.Scheme -eq 'https' `
        -and -not [string]::IsNullOrWhiteSpace($parsed.Host)
}

function Protect-AdvisorConfig {
    param(
        [string] $Directory,
        [string] $Path
    )

    try {
        $identity = [Security.Principal.WindowsIdentity]::GetCurrent().Name
        & icacls.exe $Directory /inheritance:r /grant:r "${identity}:(OI)(CI)(F)" | Out-Null
        & icacls.exe $Path /inheritance:r /grant:r "${identity}:(F)" | Out-Null
    }
    catch {
        Write-Warning 'No se pudo reforzar el ACL; el token sigue cifrado con DPAPI para tu usuario de Windows.'
    }
}

if ($Action -eq 'config-path') {
    Write-Output $configPath
    exit 0
}

if ($Action -eq 'setup') {
    $BaseUrl = $BaseUrl.TrimEnd('/')

    if (-not (Test-AdvisorBaseUrl -Value $BaseUrl)) {
        throw 'BaseUrl debe ser una URL HTTPS válida.'
    }

    $secureToken = Read-Host 'Pega FINANCE_ADVISOR_API_TOKEN' -AsSecureString
    $plainToken = [System.Net.NetworkCredential]::new('', $secureToken).Password

    try {
        if ($plainToken.Length -lt 32) {
            throw 'El token debe tener al menos 32 caracteres.'
        }

        $payload = [ordered]@{
            base_url = $BaseUrl
            token_cipher = ConvertFrom-SecureString -SecureString $secureToken
            created_at = (Get-Date).ToUniversalTime().ToString('o')
        }

        New-Item -ItemType Directory -Path $configDirectory -Force | Out-Null
        $payload | ConvertTo-Json | Set-Content -LiteralPath $configPath -Encoding UTF8
        Protect-AdvisorConfig -Directory $configDirectory -Path $configPath
    }
    finally {
        $plainToken = $null
        $secureToken = $null
        Remove-Variable plainToken, secureToken -ErrorAction SilentlyContinue
    }

    Write-Output 'Configuración local guardada y cifrada con Windows DPAPI.'
    Write-Output "Archivo: $configPath"
    Write-Output 'Ya puedes ejecutar: tools\finance-advisor.ps1 -Action snapshot'
    exit 0
}

if (-not (Test-Path -LiteralPath $configPath -PathType Leaf)) {
    throw 'No existe configuración local. Ejecuta primero con -Action setup.'
}

$config = Get-Content -Raw -LiteralPath $configPath | ConvertFrom-Json
if (-not (Test-AdvisorBaseUrl -Value ([string] $config.base_url))) {
    throw 'La URL guardada no es válida.'
}

$secureToken = ConvertTo-SecureString -String ([string] $config.token_cipher)
$plainToken = [System.Net.NetworkCredential]::new('', $secureToken).Password
$uri = ([string] $config.base_url).TrimEnd('/') + '/api/finance/advisor/snapshot'

try {
    $response = Invoke-RestMethod `
        -Method Get `
        -Uri $uri `
        -Headers @{
            Authorization = 'Bearer ' + $plainToken
            Accept = 'application/json'
        } `
        -TimeoutSec 90

    $response | ConvertTo-Json -Depth 40
}
catch {
    $statusCode = $null
    if ($_.Exception.Response -and $_.Exception.Response.StatusCode) {
        $statusCode = [int] $_.Exception.Response.StatusCode
    }

    if ($statusCode) {
        throw "La API del asesor respondió HTTP $statusCode."
    }

    throw "No se pudo consultar la API del asesor: $($_.Exception.Message)"
}
finally {
    $plainToken = $null
    $secureToken = $null
    Remove-Variable plainToken, secureToken -ErrorAction SilentlyContinue
}
