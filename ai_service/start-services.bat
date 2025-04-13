@echo off
echo === Khoi dong cac service AI ===
echo.

echo Dang xay dung va khoi dong container...
docker-compose up -d --build

echo.
echo === Thong tin container ===
docker-compose ps

echo.
echo Cac API endpoint:
echo - Scraper API: http://localhost:5001
echo - Rewrite API: http://localhost:5002
echo - Keyword Rewrite API: http://localhost:5003
echo.
echo De dung cac service, chay: docker-compose down
echo.
pause 