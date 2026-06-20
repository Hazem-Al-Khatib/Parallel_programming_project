# ====================================================================
# TESTING PRODUCT LISTING CACHING (CACHE-ASIDE PATTERN)
# ====================================================================

Clear-Host

$BaseURL = "http://127.0.0.1:8000/products"

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " Testing Product Listing Caching (Cache-Aside Pattern)     " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "[1] Launching FIRST request (Forcing Cache MISS)..." -ForegroundColor Yellow

$URL_First = "$BaseURL`?clear_cache=true"
$Stopwatch1 = [System.Diagnostics.Stopwatch]::StartNew()

try {
    $WebResponse1 = Invoke-WebRequest -Uri $URL_First -Method Get -UseBasicParsing -TimeoutSec 10
    $Stopwatch1.Stop()

    $Response1 = $WebResponse1.Content | ConvertFrom-Json

    Write-Host "--- DISPLAYING PRODUCTS RECEIVED ---" -ForegroundColor Gray
    foreach ($product in $Response1.data) {
        Write-Host "• ID: $($product.id) | Name: $($product.name) | Price: $($product.price)$" -ForegroundColor White
    }
    Write-Host "------------------------------------" -ForegroundColor Gray
    Write-Host "--> Source: $($Response1.source)" -ForegroundColor Red
    Write-Host "--> Client Stopwatch Time: $($Stopwatch1.Elapsed.TotalMilliseconds) ms" -ForegroundColor Magenta

    $ServerTime1 = $WebResponse1.Headers['X-Server-Execution-Time']
    if ($null -eq $ServerTime1) { $ServerTime1 = "N/A" }
    Write-Host "--> Net Server Execution Time (AOP): $ServerTime1" -ForegroundColor Cyan

} catch {
    Write-Host "[ERROR IN FIRST REQUEST]: $_" -ForegroundColor Red
}

Start-Sleep -Seconds 2
Write-Host ""

Write-Host "[2] Launching SECOND request (Expecting Cache HIT)..." -ForegroundColor Yellow

$URL_Second = $BaseURL
$Stopwatch2 = [System.Diagnostics.Stopwatch]::StartNew()

try {
    $WebResponse2 = Invoke-WebRequest -Uri $URL_Second -Method Get -UseBasicParsing -TimeoutSec 10
    $Stopwatch2.Stop()

    $Response2 = $WebResponse2.Content | ConvertFrom-Json

    Write-Host "--- DISPLAYING PRODUCTS RECEIVED ---" -ForegroundColor Gray
    foreach ($product in $Response2.data) {
        Write-Host "• ID: $($product.id) | Name: $($product.name) | Price: $($product.price)$" -ForegroundColor White
    }
    Write-Host "------------------------------------" -ForegroundColor Gray
    Write-Host "--> Source: $($Response2.source)" -ForegroundColor Green
    Write-Host "--> Client Stopwatch Time: $($Stopwatch2.Elapsed.TotalMilliseconds) ms" -ForegroundColor Magenta

    $ServerTime2 = $WebResponse2.Headers['X-Server-Execution-Time']
    if ($null -eq $ServerTime2) { $ServerTime2 = "N/A" }
    Write-Host "--> Net Server Execution Time (AOP): $ServerTime2" -ForegroundColor Cyan

} catch {
    Write-Host "[ERROR IN SECOND REQUEST]: $_" -ForegroundColor Red
}
