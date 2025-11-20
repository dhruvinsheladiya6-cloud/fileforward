@echo off
echo Initializing Git repository...
git init

echo Adding all files...
git add .

echo Committing files...
git commit -m "Initial commit - Laravel Fileforward application"

echo Setting branch to main...
git branch -M main

echo Adding remote origin...
git remote add origin https://github.com/dhruvinsheladiya6-cloud/fileforward.git

echo Pushing to GitHub...
git push -u origin main

echo.
echo Done! Your code has been pushed to GitHub.
pause
