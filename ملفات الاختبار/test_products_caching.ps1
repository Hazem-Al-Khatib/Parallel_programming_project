# Clear the terminal screen for a clean, professional output
Clear-Host

# Base URL for your Laravel product route
$BaseURL = "http://127.0.0.1:8000/products"

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " Testing Product Listing Caching (Cache-Aside Pattern)     " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host ""

# ----------------------------------------------------------------
# FIRST ATTEMPT: Force Cache Miss (Fetch from Database)
# ----------------------------------------------------------------
Write-Host "[1] Launching FIRST request (Forcing Cache MISS)..." -ForegroundColor Yellow

# Appending ?clear_cache=true tells our ProductController to drop the existing Redis RAM key first
$URL_First = "$BaseURL`?clear_cache=true"

$Stopwatch1 = [System.Diagnostics.Stopwatch]::StartNew()
$Response1 = Invoke-RestMethod -Uri $URL_First -Method Get
$Stopwatch1.Stop()

# Display the actual products fetched
Write-Host "--- DISPLAYING PRODUCTS RECEIVED ---" -ForegroundColor Gray
foreach ($product in $Response1.data) {
    Write-Host "• ID: $($product.id) | Name: $($product.name) | Price: $($product.price)$" -ForegroundColor White
}
Write-Host "------------------------------------" -ForegroundColor Gray
Write-Host "--> Source: $($Response1.source)" -ForegroundColor Red
Write-Host "--> Execution Time: $($Stopwatch1.Elapsed.TotalMilliseconds) ms" -ForegroundColor Magenta
Write-Host ""

# Wait 2 seconds to visually separate the two tests in the console
Start-Sleep -Seconds 2

# ----------------------------------------------------------------
# SECOND ATTEMPT: Leverage Cache Hit (Fetch from Redis RAM)
# ----------------------------------------------------------------
Write-Host "[2] Launching SECOND request (Expecting Cache HIT)..." -ForegroundColor Yellow

# Standard URL without parameters so it uses the cached data built by the first request
$URL_Second = $BaseURL

$Stopwatch2 = [System.Diagnostics.Stopwatch]::StartNew()
$Response2 = Invoke-RestMethod -Uri $URL_Second -Method Get
$Stopwatch2.Stop()

# Display the actual products fetched (should be identical data)
Write-Host "--- DISPLAYING PRODUCTS RECEIVED ---" -ForegroundColor Gray
foreach ($product in $Response2.data) {
    Write-Host "• ID: $($product.id) | Name: $($product.name) | Price: $($product.price)$" -ForegroundColor White
}
Write-Host "------------------------------------" -ForegroundColor Gray
Write-Host "--> Source: $($Response2.source)" -ForegroundColor Green
Write-Host "--> Execution Time: $($Stopwatch2.Elapsed.TotalMilliseconds) ms" -ForegroundColor Magenta
Write-Host ""

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " Notice the massive drop in Execution Time!                " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan