# check-bootstrap.ps1
# --- Scan de tout le projet pour repérer d'éventuelles inclusions multiples de Bootstrap/CSS/JS/Encore ---

Write-Host "`n=== Scan du projet pour doublons Bootstrap/CSS/JS ===`n"

$root = Split-Path $MyInvocation.MyCommand.Path -Parent
Set-Location (Resolve-Path "$root\..")

# Fichiers à scanner
$files = Get-ChildItem -Recurse -File -Include *.twig,*.html,*.php,*.js,*.css

# Motifs à rechercher
$patterns = @(
    'bootstrap(\.bundle)?(\.min)?\.js',
    'bootstrap(\.min)?\.css',
    'cdn\.jsdelivr\.net/npm/bootstrap',
    'data-bs-toggle',
    'encore_entry_link_tags',
    'encore_entry_script_tags'
)

$result = @()

foreach ($p in $patterns) {
    $matches = $files | Select-String -Pattern $p -Encoding UTF8 -SimpleMatch:$false -CaseSensitive:$false
    if ($matches) {
        $result += $matches | Select-Object Path, LineNumber, Line, Pattern
    }
}

if (-not $result) {
    Write-Host "Aucune occurrence trouvée pour Bootstrap/Encore." -ForegroundColor Green
    exit 0
}

# Regroupe par fichier pour voir les fichiers "bruyants"
$grouped = $result | Group-Object Path | Sort-Object Count -Descending

Write-Host "=== Fichiers contenant des inclusions Bootstrap/Encore ===`n"
foreach ($g in $grouped) {
    Write-Host ("{0}  (occurrences: {1})" -f $g.Name, $g.Count) -ForegroundColor Yellow
}

# Détail des lignes par fichier
Write-Host "`n=== Détail des occurrences ==="
foreach ($g in $grouped) {
    Write-Host "`n--- $($g.Name) ---" -ForegroundColor Cyan
    $g.Group | Sort-Object LineNumber | ForEach-Object {
        # Raccourci de ligne
        $line = $_.Line.Trim()
        if ($line.Length -gt 160) { $line = $line.Substring(0,160) + " ..." }
        "{0,5}: {1}" -f $_.LineNumber, $line
    }
}

# Aide : met en évidence quelques patterns fréquents problématiques
Write-Host "`n=== Conseils ===" -ForegroundColor Magenta
Write-Host "1) Vérifie qu'un seul fichier (souvent templates/base.html.twig) inclut Bootstrap CSS et JS."
Write-Host "2) Évite d'inclure Bootstrap dans des partiels (_navbar.html.twig, _footer.html.twig)."
Write-Host "3) Si tu utilises Encore, garde uniquement {{ encore_entry_link_tags('app') }} et {{ encore_entry_script_tags('app') }} dans base.html.twig."
Write-Host "4) Ne mélange pas CDN et Encore pour une même lib."
