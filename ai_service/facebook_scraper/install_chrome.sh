#!/bin/bash

# This script installs the latest Chrome version and compatible ChromeDriver

echo "Installing the latest Chrome version..."

# Update repository and install latest Chrome
apt-get update
apt-get install -y wget curl jq unzip

# Install latest Chrome
wget -q https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb -O /tmp/chrome.deb
apt-get install -y /tmp/chrome.deb
rm /tmp/chrome.deb

# Check installed version
CHROME_VERSION=$(google-chrome --version | awk '{print $3}')
CHROME_MAJOR_VERSION=$(echo $CHROME_VERSION | cut -d. -f1)
echo "Installed Chrome version: $CHROME_VERSION (Major: $CHROME_MAJOR_VERSION)"

# Get compatible ChromeDriver using Chrome for Testing API
echo "Finding compatible ChromeDriver from Chrome for Testing API..."
curl -s https://googlechromelabs.github.io/chrome-for-testing/last-known-good-versions-with-downloads.json > /tmp/chrome_versions.json

# Try to get ChromeDriver URL from stable or canary channels
CHROMEDRIVER_URL=$(jq -r '.channels.Stable.downloads.chromedriver[] | select(.platform=="linux64") | .url' /tmp/chrome_versions.json)
if [ -z "$CHROMEDRIVER_URL" ]; then
  echo "ChromeDriver URL not found in Stable channel, trying Canary..."
  CHROMEDRIVER_URL=$(jq -r '.channels.Canary.downloads.chromedriver[] | select(.platform=="linux64") | .url' /tmp/chrome_versions.json)
fi

if [ ! -z "$CHROMEDRIVER_URL" ]; then
  echo "Downloading ChromeDriver from: $CHROMEDRIVER_URL"
  mkdir -p /tmp/chromedriver
  wget -q -O /tmp/chromedriver.zip "$CHROMEDRIVER_URL"
  unzip -q /tmp/chromedriver.zip -d /tmp/chromedriver
  
  # Find and install ChromeDriver
  CHROMEDRIVER_PATH=$(find /tmp/chromedriver -name chromedriver -type f)
  if [ ! -z "$CHROMEDRIVER_PATH" ]; then
    cp "$CHROMEDRIVER_PATH" /usr/local/bin/chromedriver
    chmod +x /usr/local/bin/chromedriver
    echo "Successfully installed ChromeDriver at /usr/local/bin/chromedriver"
    chromedriver --version
  else
    echo "ChromeDriver not found in extracted files!"
  fi
  
  # Cleanup
  rm -rf /tmp/chromedriver /tmp/chromedriver.zip
else
  echo "Could not find ChromeDriver download URL. You may need to install it manually."
fi

rm -f /tmp/chrome_versions.json

echo "Chrome and ChromeDriver installation completed!" 