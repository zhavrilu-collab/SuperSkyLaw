# Objavljuje točno HEAD (origin/main). Ne pakira radni direktorij i ne sije demo bazu.
$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..')

$dirty = git status --porcelain
if ($dirty) {
    Write-Error "Stablo nije čisto. Prvo commit i push. Objava je stala."
}

git fetch origin
$head = (git rev-parse HEAD).Trim()
$origin = (git rev-parse origin/main).Trim()
if ($head -ne $origin) {
    Write-Error "HEAD nije origin/main. Prvo push. Objava je stala."
}

$sshKey = Join-Path $env:USERPROFILE '.ssh\supersky_vps'
$sshTarget = 'ubuntu@148.163.187.14'
$sshArgs = @('-p', '2222', '-i', $sshKey, '-o', 'IdentitiesOnly=yes', $sshTarget)
$scpArgs = @('-P', '2222', '-i', $sshKey, '-o', 'IdentitiesOnly=yes')

$deployed = (ssh @sshArgs "cat /var/www/legal-saas/.deploy-sha 2>/dev/null || true").Trim()
if ($deployed) {
    git merge-base --is-ancestor $deployed $head
    if ($LASTEXITCODE -ne 0) {
        Write-Error "Commit $head nije iznad zadnje objave $deployed. Objava je stala."
    }
}

$newArchive = Join-Path $env:TEMP 'legal-deploy-new.tgz'
$oldArchive = Join-Path $env:TEMP 'legal-deploy-old.tgz'
git archive --format=tar.gz -o $newArchive HEAD
if ($deployed) {
    git archive --format=tar.gz -o $oldArchive $deployed
}

scp @scpArgs $newArchive "${sshTarget}:/tmp/legal-deploy-new.tgz"
scp @scpArgs (Join-Path $PSScriptRoot 'deploy-law-remote.sh') "${sshTarget}:/tmp/deploy-law-remote.sh"
if ($deployed) {
    scp @scpArgs $oldArchive "${sshTarget}:/tmp/legal-deploy-old.tgz"
    ssh @sshArgs "sed -i 's/\r$//' /tmp/deploy-law-remote.sh && bash /tmp/deploy-law-remote.sh /tmp/legal-deploy-new.tgz $head /tmp/legal-deploy-old.tgz"
} else {
    ssh @sshArgs "sed -i 's/\r$//' /tmp/deploy-law-remote.sh && bash /tmp/deploy-law-remote.sh /tmp/legal-deploy-new.tgz $head"
}
