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
        return "[ALLOWED] " + $response
    } catch {
        if ($_.Exception.Response -ne $null) {
            $stream = $_.Exception.Response.GetResponseStream()
            $reader = New-Object System.IO.StreamReader($stream)
            $errorResponse = $reader.ReadToEnd()
            
            if ($_.Exception.Response.StatusCode -eq "TooManyRequests" -or $_.Exception.Response.StatusCode -eq 429) {
                return "[BLOCKED - 429] Too Many Requests! Rate Limiter Triggered."
            }
            return "[SERVER REJECTION] " + $errorResponse
        } else {
            return "[FAILED] Connection Error"
        }
    }
}

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "Launching 26 Concurrent Requests to Break Rate Limiter (Limit: 24)..." -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

for ($i = 1; $i -le 26; $i++) {
    Start-Job -ScriptBlock $requestScript -ArgumentList $url | Out-Null
}

Write-Host "Firing requests simultaneously, holding thread for results..." -ForegroundColor Yellow
Get-Job | Wait-Job | Out-Null

Get-Job | ForEach-Object {
    $result = $_ | Receive-Job
    if ($result) {
        if ($result -match "BLOCKED") {
            Write-Host $result -ForegroundColor Red
        } elseif ($result -match "ALLOWED") {
            Write-Host $result -ForegroundColor Green
        } else {
            Write-Host $result -ForegroundColor Yellow
        }
    }
}

Get-Job | Remove-Job -Force