<#
Save a secret for the dejbansec scripts on this PC, encrypted with Windows DPAPI (machine scope).

  powershell -ExecutionPolicy Bypass -File tools\save-secret.ps1 wp       # WordPress application password of user "chat"
  powershell -ExecutionPolicy Bypass -File tools\save-secret.ps1 cpanel   # cPanel API token of user "dejbansec"

The value is typed hidden and never shown. It is stored in %USERPROFILE%\.dejbansec\<name>.dpapi as "machine:<base64>".
Machine scope survives a reset of the Windows password (user-scope DPAPI does not); the file stays in this user's
profile, so other accounts cannot read it. A previous file is kept as <name>.dpapi.bak.
#>
param([Parameter(Mandatory = $true)][ValidateSet('wp', 'cpanel')][string]$Name)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Security
$file = @{ wp = 'wp-autoposter.dpapi'; cpanel = 'cpanel-token.dpapi' }[$Name]
$dir = Join-Path $env:USERPROFILE '.dejbansec'
New-Item -ItemType Directory -Force $dir | Out-Null
$path = Join-Path $dir $file

$label = @{ wp = 'WordPress application password for user chat'; cpanel = 'cPanel API token' }[$Name]
$secure = Read-Host -AsSecureString "Paste the $label, then press Enter"
$bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
try { $plain = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr).Trim() }
finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr) }
if ($plain.Length -lt 10) { throw "That looks too short; nothing was saved." }

$bytes = [Security.Cryptography.ProtectedData]::Protect([Text.Encoding]::UTF8.GetBytes($plain), $null, 'LocalMachine')
if (Test-Path $path) { Copy-Item $path "$path.bak" -Force }
Set-Content -Path $path -Value ('machine:' + [Convert]::ToBase64String($bytes)) -NoNewline -Encoding ASCII

# read it back the way the scripts do
$check = [Text.Encoding]::UTF8.GetString([Security.Cryptography.ProtectedData]::Unprotect(
    [Convert]::FromBase64String((Get-Content -Raw $path).Substring(8)), $null, 'LocalMachine'))
if ($check -ne $plain) { throw "Saved, but reading it back failed." }
Write-Host "Saved $label to $path ($($plain.Length) characters)."
