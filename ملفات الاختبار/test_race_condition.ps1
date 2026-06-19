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
Write-Host "Launching 2 Concurrent Requests for 1 Available Stock..." -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

for ($i = 1; $i -le 2; $i++) {
    Start-Job -ScriptBlock $requestScript -ArgumentList $url | Out-Null
}

Write-Host "Processing, waiting for both jobs to finish..." -ForegroundColor Yellow
Get-Job | Wait-Job | Out-Null

Start-Sleep -Seconds 1

Get-Job | ForEach-Object {
    $result = $_ | Receive-Job
    if ($result) {
        if ($result -match "SUCCESS") {
            Write-Host $result -ForegroundColor Green
        } else {
            Write-Host $result -ForegroundColor Red
        }
    }
}

Get-Job | Remove-Job -Force