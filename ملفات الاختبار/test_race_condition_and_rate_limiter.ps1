# ====================================================================
# HIGH CONCURRENCY TEST SCRIPT (FIXED ERROR HANDLING)
# ====================================================================

Get-Job | Remove-Job -Force
Clear-Host

$url = "http://127.0.0.1:8000/buy"

$requestScript = {
    param($u)
    $web = New-Object System.Net.WebClient
    $web.Headers.Add("Content-Type", "application/x-www-form-urlencoded")
    $web.Headers.Add("Accept", "application/json")
    try {
        $response = $web.UploadString($u, "POST", "product_id=1&quantity=1")
        return "[SUCCESS] " + $response
    } catch {
        # Safe handling if the server rejects under heavy parallel pressure
        if ($_.Exception.Response -ne $null) {
            $stream = $_.Exception.Response.GetResponseStream()
            $reader = New-Object System.IO.StreamReader($stream)
            $errorResponse = $reader.ReadToEnd()
            return "[REJECTED] " + $errorResponse
        } else {
            return "[REJECTED] {""message"":""Out of stock / Request Blocked""}"
        }
    }
}

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "Launching 20 Concurrent Requests to simulate Flash Sale..." -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

for ($i = 1; $i -le 20; $i++) {
    Start-Job -ScriptBlock $requestScript -ArgumentList $url | Out-Null
}

Write-Host "Processing requests, please wait..." -ForegroundColor Yellow
Get-Job | Out-Null

Write-Host "`n==========================================================" -ForegroundColor Green
Write-Host "TEST RESULTS RECEIVED FROM LARAVEL CONVERSIONS:" -ForegroundColor Green
Write-Host "==========================================================" -ForegroundColor Green

Get-Job | Receive-Job | ForEach-Object {
    if ($_ -match "SUCCESS") {
        Write-Host $_ -ForegroundColor Green
    } else {
        Write-Host $_ -ForegroundColor Red
    }
}

Get-Job | Remove-Job -Force