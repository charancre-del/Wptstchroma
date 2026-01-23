# Launch Chroma Director Portal
Write-Host "Starting Chroma Director Portal..." -ForegroundColor Cyan

# Check if node_modules exists
if (-not (Test-Path "node_modules")) {
    Write-Host "First run detected. Installing dependencies..." -ForegroundColor Yellow
    npm install
}

# Start the dev server
Write-Host "Launching Server at http://localhost:3000" -ForegroundColor Green
npm run dev
