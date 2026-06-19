Get-Job | Remove-Job -Force
Clear-Host

$servers = @("http://127.0.0.1:8000/buy", "http://127.0.0.1:8001/buy")

$requestScript = {
    param($u, $requestId)
    $web = New-Object System.Net.WebClient
    $web.Headers.Add("Content-Type", "application/x-www-form-urlencoded")
    $web.Headers.Add("Accept", "application/json")
    try {
        $response = $web.UploadString($u, "POST", "product_id=1&quantity=1")
        return "[SERVER " + $u.Split(':')[-1].Split('/')[0] + "] Request #" + $requestId + " -> SUCCESS"
    } catch {
        return "[SERVER " + $u.Split(':')[-1].Split('/')[0] + "] Request #" + $requestId + " -> REJECTED (Out of stock/Throttle)"
    }
}

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "Simulating Load Balancer distributing 20 requests..." -ForegroundColor Cyan
Write-Host "Pool: [Port 8000] and [Port 8001]" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

for ($i = 1; $i -le 20; $i++) {
    $targetServer = $servers[$i % 2]
    Start-Job -ScriptBlock $requestScript -ArgumentList $targetServer, $i | Out-Null
}

Write-Host "Distributing load across cluster, please wait..." -ForegroundColor Yellow
Get-Job | Wait-Job | Out-Null

Write-Host "`n==========================================================" -ForegroundColor Green
Write-Host "CLUSTER DISTRIBUTION RESULTS:" -ForegroundColor Green
Write-Host "==========================================================" -ForegroundColor Green

Get-Job | Receive-Job | ForEach-Object {
    if ($_ -match "SUCCESS") {
        Write-Host $_ -ForegroundColor Green
    } else {
        Write-Host $_ -ForegroundColor Red
    }
}

Get-Job | Remove-Job -Force