Get-Job | Remove-Job -Force
Clear-Host

$url = "http://127.0.0.1:8000/buy"

$elapsedTime = Measure-Command {
    
    Write-Host "==========================================================" -ForegroundColor Cyan
    Write-Host "Sending 10 Fast Requests to test Asynchronous Queues..." -ForegroundColor Cyan
    Write-Host "==========================================================" -ForegroundColor Cyan

    for ($i = 1; $i -le 10; $i++) {
        $web = New-Object System.Net.WebClient
        $web.Headers.Add("Content-Type", "application/x-www-form-urlencoded")
        $web.Headers.Add("Accept", "application/json")
        try {
            $response = $web.UploadString($url, "POST", "product_id=1&quantity=1")
            Write-Host "[SERVER RESPONSE $i]: $response" -ForegroundColor Green
        } catch {
            Write-Host "[SERVER REJECTED $i]: Out of stock or rate limited." -ForegroundColor Red
        }
    }
}

Write-Host "==========================================================" -ForegroundColor Yellow
Write-Host "Total script execution time: $($elapsedTime.TotalSeconds) seconds" -ForegroundColor Yellow
Write-Host "==========================================================" -ForegroundColor Yellow
Write-Host "Check your Terminal running 'php artisan queue:work' to see background job processing!" -ForegroundColor Cyan