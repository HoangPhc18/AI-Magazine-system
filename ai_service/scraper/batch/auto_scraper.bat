@echo off
:: Auto Scraper for AI Magazine System
:: Auto collects news, sends to backend, and cleans up old files
echo =====================================================
echo AI Magazine News Scraper - Starting data collection
echo Started at: %date% %time%
echo =====================================================
echo.
cd /d "F:\magazine-ai-system\ai_service\scraper"
echo.
echo 1. Running the scraper...
python main.py --auto-send --retention-days 7 --batch-size 5 --verbose
echo.
echo 2. Scraping completed!
echo =====================================================
echo Finished at: %date% %time%
echo =====================================================
echo.
echo %date% %time% - Auto scraper completed successfully >> auto_scraper_log.txt
echo.
pause
