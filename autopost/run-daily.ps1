<#
Unattended daily run for dejbansec.ir, started by the Windows Task Scheduler job "dejbansec-daily-articles"
(daily at 05:30 UTC = 09:00 Tehran; Iran has no DST, and the trigger is in UTC so US DST changes don't move it).

  powershell -NoProfile -ExecutionPolicy Bypass -File autopost\run-daily.ps1 [-DryRun]

- Runs Claude Code headless (claude -p) with autopost\ROUTINE.md as the prompt, Opus with Sonnet as fallback,
  permission mode dontAsk + autopost\agent-settings.json: anything not pre-approved is refused, never prompted.
- At most 2 posts per Tehran day: the quota passed to the run is 2 minus what log.jsonl already shows for today,
  and publish.py refuses past the cap as well. A lock file stops two runs overlapping.
- After the run, this script (not the agent) commits log.jsonl and the day's drafts and pushes to GitHub.
- -DryRun: full research/writing/cover/login check, but publish.py stops before uploading; drafts are moved into the
  run folder, and git push is only tested with --dry-run.
- Each run writes autopost\runs\<Tehran date>_<HHmm>\ (wrapper.log, prompt.md, claude.jsonl, result.txt) and appends
  one line to autopost\runs\history.log.
#>
param([switch]$DryRun)

$ErrorActionPreference = 'Stop'
$Repo = Split-Path -Parent $PSScriptRoot
$PyDir = Join-Path $env:LOCALAPPDATA 'Python\pythoncore-3.14-64'
$env:PATH = "$PyDir;$env:PATH"   # the WindowsApps 'python' alias earlier in PATH is unreliable
$env:PYTHONIOENCODING = 'utf-8'
$Claude = Join-Path $env:USERPROFILE '.local\bin\claude.exe'
$Utf8 = New-Object System.Text.UTF8Encoding $false
$TimeoutMinutes = 100
$DailyCap = 2

$Tehran = [DateTime]::UtcNow.AddHours(3.5)
$TDate = $Tehran.ToString('yyyy-MM-dd')
$Runs = Join-Path $Repo 'autopost\runs'
$RunDir = Join-Path $Runs ($Tehran.ToString('yyyy-MM-dd_HHmm') + $(if ($DryRun) { '-dryrun' } else { '' }))
New-Item -ItemType Directory -Force $RunDir | Out-Null
$WLog = Join-Path $RunDir 'wrapper.log'

function Log($msg) {
    $line = '{0} [Tehran {1}] {2}' -f [DateTime]::UtcNow.ToString('yyyy-MM-ddTHH:mm:ssZ'), [DateTime]::UtcNow.AddHours(3.5).ToString('HH:mm'), $msg
    [IO.File]::AppendAllText($WLog, $line + "`r`n", $Utf8)
}
function Add-RunHistory($status) {
    [IO.File]::AppendAllText((Join-Path $Runs 'history.log'), ('{0}  {1}  {2}  {3}' -f [DateTime]::UtcNow.ToString('yyyy-MM-ddTHH:mm:ssZ'), $TDate, $status, (Split-Path -Leaf $RunDir)) + "`r`n", $Utf8)
}
function Native([string]$exe, [string[]]$argv) {
    # run a console program, capture stdout+stderr as UTF-8 text, return @(exitcode, output)
    $psi = New-Object System.Diagnostics.ProcessStartInfo $exe
    $psi.Arguments = ($argv | ForEach-Object { if ($_ -match '[\s"]') { '"' + ($_ -replace '"', '\"') + '"' } else { $_ } }) -join ' '
    $psi.WorkingDirectory = $Repo; $psi.UseShellExecute = $false
    $psi.RedirectStandardOutput = $true; $psi.RedirectStandardError = $true
    $psi.StandardOutputEncoding = $Utf8; $psi.StandardErrorEncoding = $Utf8
    $p = [Diagnostics.Process]::Start($psi)
    $err = $p.StandardError.ReadToEndAsync(); $out = $p.StandardOutput.ReadToEnd(); $p.WaitForExit()
    return @($p.ExitCode, ($out + $err.Result).Trim())
}

# one run at a time
$lockPath = Join-Path $Runs '.lock'
try { $lock = [IO.File]::Open($lockPath, 'OpenOrCreate', 'ReadWrite', 'None') }
catch { Log 'another run holds the lock; exiting'; Add-RunHistory 'SKIPPED-LOCKED'; exit 0 }

try {
    Log ("start mode={0} user={1} claude={2}" -f $(if ($DryRun) { 'dry-run' } else { 'publish' }), $env:USERNAME, (Native $Claude @('--version'))[1])

    $logFile = Join-Path $Repo 'autopost\log.jsonl'
    $before = @([IO.File]::ReadAllLines($logFile, $Utf8)).Count
    $done = [int](Native 'python' @('-c', "import sys; sys.path.insert(0,'autopost'); import publish; print(publish.published_today())"))[1]
    $quota = if ($DryRun) { $DailyCap } else { $DailyCap - $done }
    Log "published today (Tehran $TDate): $done; quota for this run: $quota"
    if ($quota -le 0) { Log 'daily cap already reached; nothing to do'; Add-RunHistory 'SKIPPED-DONE'; exit 0 }

    $prompt = [IO.File]::ReadAllText((Join-Path $Repo 'autopost\ROUTINE.md'), $Utf8)
    $prompt = $prompt.Replace('{{TEHRAN_DATE}}', $TDate).Replace('{{QUOTA}}', "$quota").Replace('{{MODE}}', $(if ($DryRun) { 'dry-run (do NOT publish; stop after the --dry-run checks)' } else { 'publish' }))
    $promptFile = Join-Path $RunDir 'prompt.md'
    [IO.File]::WriteAllText($promptFile, $prompt, $Utf8)

    $jsonl = Join-Path $RunDir 'claude.jsonl'
    $argv = @('-p', '--model', 'opus', '--fallback-model', 'sonnet',
        '--permission-mode', 'dontAsk', '--permission-prompts', 'none',
        '--settings', (Join-Path $Repo 'autopost\agent-settings.json'),
        '--tools', 'Bash,Read,Write,Edit,Glob,Grep,WebSearch,WebFetch',
        '--strict-mcp-config', '--no-chrome',
        '--output-format', 'stream-json', '--verbose',
        '--name', "dejbansec-daily-$TDate")
    $started = Get-Date
    Log "claude $($argv -join ' ')"
    $p = Start-Process -FilePath $Claude -ArgumentList $argv -WorkingDirectory $Repo -NoNewWindow -PassThru `
        -RedirectStandardInput $promptFile -RedirectStandardOutput $jsonl -RedirectStandardError (Join-Path $RunDir 'claude.stderr.txt')
    $null = $p.Handle   # Windows PowerShell only reports ExitCode if the handle was opened early
    if (-not $p.WaitForExit($TimeoutMinutes * 60 * 1000)) {
        Log "timeout after $TimeoutMinutes min; killing the run"
        & taskkill.exe /T /F /PID $p.Id | Out-Null
    }
    $p.WaitForExit()
    Log ("claude finished, exit {0}, {1:N1} min" -f $p.ExitCode, ((Get-Date) - $started).TotalMinutes)

    # final report from the stream
    $result = $null
    foreach ($l in [IO.File]::ReadAllLines($jsonl, $Utf8)) {
        if ($l -like '*"type":"result"*') { try { $result = $l | ConvertFrom-Json } catch {} }
    }
    $report = if ($result) { "$($result.result)" } else { '(no result message in the stream)' }
    [IO.File]::WriteAllText((Join-Path $RunDir 'result.txt'), $report, $Utf8)
    if ($result) { Log ("result: is_error={0} turns={1} denials={2}" -f $result.is_error, $result.num_turns, @($result.permission_denials).Count) }
    if ($result -and @($result.permission_denials).Count) {
        foreach ($d in $result.permission_denials) { Log ("  denied: {0} {1}" -f $d.tool_name, ($d.tool_input | ConvertTo-Json -Compress -Depth 3)) }
    }
    $resultLine = ($report -split "`n" | Where-Object { $_ -match '^\s*RESULT:' } | Select-Object -Last 1)
    Log "agent says: $resultLine"

    if ($DryRun) {
        # keep the test articles out of drafts/ so tomorrow's real run starts clean
        $moved = Join-Path $RunDir 'drafts'; New-Item -ItemType Directory -Force $moved | Out-Null
        Get-ChildItem (Join-Path $Repo 'autopost\drafts') -File | Where-Object { $_.LastWriteTime -ge $started -and $_.Name -ne '.gitkeep' } |
            ForEach-Object { Move-Item $_.FullName $moved -Force; Log "moved test file $($_.Name) to run folder" }
        $after = @([IO.File]::ReadAllLines($logFile, $Utf8)).Count
        if ($after -ne $before) { Log "ERROR: log.jsonl changed during a dry run ($before -> $after lines)" }
        $r = Native 'git' @('push', '--dry-run', 'origin', 'main'); Log ("git push --dry-run: exit {0} {1}" -f $r[0], $r[1])
        $ok = ($resultLine -match 'DRY-RUN OK') -and ($after -eq $before) -and ($r[0] -eq 0)
        Add-RunHistory $(if ($ok) { 'DRYRUN-OK' } else { 'DRYRUN-FAILED' })
        exit $(if ($ok) { 0 } else { 1 })
    }

    # commit and push what was published
    $lines = @([IO.File]::ReadAllLines($logFile, $Utf8))
    $new = @($lines | Select-Object -Skip $before | Where-Object { $_.Trim() } | ForEach-Object { $_ | ConvertFrom-Json })
    if (-not $new.Count) { Log 'nothing was published'; Add-RunHistory 'FAILED-NOTHING-PUBLISHED'; exit 1 }
    foreach ($e in $new) {
        $slug = ($e.link.TrimEnd('/') -split '/')[-1]
        $v = Native 'python' @('autopost/site.py', 'verify', $slug); Log "verify: $($v[1])"
    }
    $add = @('add', 'autopost/log.jsonl') + @(Get-ChildItem (Join-Path $Repo 'autopost\drafts') -File |
        Where-Object { $_.Name -like "$TDate-*.json" -or $_.Name -like "$TDate-*.html" } | ForEach-Object { 'autopost/drafts/' + $_.Name })
    $r = Native 'git' $add; Log ("git add: exit {0} {1}" -f $r[0], $r[1])
    $msgFile = Join-Path $RunDir 'commit-msg.txt'
    [IO.File]::WriteAllText($msgFile, ("autopost: $TDate — " + (($new | ForEach-Object { $_.title }) -join ' / ') + "`n`nCo-Authored-By: Claude <noreply@anthropic.com>`n"), $Utf8)
    $r = Native 'git' @('commit', '-F', $msgFile); Log ("git commit: exit {0} {1}" -f $r[0], $r[1])
    $pushed = $false
    foreach ($wait in 0, 2, 4, 8, 16) {
        Start-Sleep -Seconds $wait
        $r = Native 'git' @('push', 'origin', 'main'); Log ("git push: exit {0} {1}" -f $r[0], $r[1])
        if ($r[0] -eq 0) { $pushed = $true; break }
    }
    Add-RunHistory ("PUBLISHED-{0}{1}" -f $new.Count, $(if ($pushed) { '' } else { '-PUSH-FAILED' }))
    exit $(if ($pushed) { 0 } else { 1 })
}
catch {
    Log "wrapper error: $($_.Exception.Message)"
    Add-RunHistory 'FAILED-WRAPPER'
    exit 1
}
finally {
    Log 'end'
    $lock.Dispose()
}
