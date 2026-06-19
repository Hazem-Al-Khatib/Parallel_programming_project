Clear-Host

Write-Host "==========================================================" -ForegroundColor Yellow
Write-Host " 🚀 Launching 20 Concurrent Requests WITHOUT Distributed Locks " -ForegroundColor Yellow
Write-Host "==========================================================" -ForegroundColor Yellow
Write-Host "Firing requests simultaneously...`n" -ForegroundColor Cyan

$URL = "http://127.0.0.1:8000/purchase-without-lock"

for ($i = 1; $i -le 20; $i++) {
    Start-Job -ScriptBlock {
        param($id, $url)
        try {
            $Body = @{ product_id = 1; quantity = 1 }
            $Response = Invoke-WebRequest -Uri $url -Method Post -Body $Body -TimeoutSec 5 -UseBasicParsing
            return "Request #$id -> STATUS: $($Response.StatusCode) | Body: $($Response.Content)"
        }
        catch {
            if ($_.Exception.Response) {
                $Reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
                return "Request #$id -> ERROR STATUS: $($_.Exception.Response.StatusCode) | Message: $($Reader.ReadToEnd())"
            }
            return "Request #$id -> CRITICAL: $($_.Exception.Message)"
        }
    } -ArgumentList $i, $URL | Out-Null
}

Get-Job | Wait-Job -Timeout 5 | Out-Null

$JobResults = Get-Job | Receive-Job

foreach ($Result in $JobResults) {
    if ($Result -like "*STATUS: 200*") {
        Write-Host $Result -ForegroundColor Green
    } else {
        Write-Host $Result -ForegroundColor Red
    }
}

Get-Job | Remove-Job