<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class OptimizeSeoImages extends Command
{
    protected $signature = 'seo:optimize-images {--force : Re-optimize all images even if already optimized}';
    protected $description = 'Generate the social sharing card and optimize all article featured images for Core Web Vitals';

    public function handle(): int
    {
        $this->info('========================================================================');
        $this->info('          CRICBAIL SEO: IMAGE OPTIMIZATION & SHARE CARD GENERATOR       ');
        $this->info('========================================================================');

        $this->generateSocialShareCard();
        $this->optimizeArticleImages();

        $this->newLine();
        $this->info('Image optimization process complete!');
        return 0;
    }

    /**
     * Generate 1200x630 Open Graph & Twitter Card social share banner.
     */
    protected function generateSocialShareCard(): void
    {
        $targetPath = public_path('assets/images/cricket/cricbail_share_card.jpg');
        File::ensureDirectoryExists(dirname($targetPath));

        if (File::exists($targetPath) && !$this->option('force')) {
            $this->line('[INFO] Social share card already exists: ' . basename($targetPath));
            return;
        }

        $this->line('[TASK] Creating 1200x630 social share card...');

        $width = 1200;
        $height = 630;
        $img = imagecreatetruecolor($width, $height);

        // Dark cricket turf gradient background
        $bgTop = [9, 38, 30];     // #09261e
        $bgBottom = [17, 63, 50]; // #113f32

        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $r = (int)($bgTop[0] + ($bgBottom[0] - $bgTop[0]) * $ratio);
            $g = (int)($bgTop[1] + ($bgBottom[1] - $bgTop[1]) * $ratio);
            $b = (int)($bgTop[2] + ($bgBottom[2] - $bgTop[2]) * $ratio);
            $lineColor = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, $width, $y, $lineColor);
        }

        // Gold accent border
        $gold = imagecolorallocate($img, 245, 158, 11); // #f59e0b
        $white = imagecolorallocate($img, 255, 255, 255);
        $offWhite = imagecolorallocate($img, 226, 232, 240);
        $subText = imagecolorallocate($img, 148, 163, 184);

        // Outer border
        imagesetthickness($img, 6);
        imagerectangle($img, 20, 20, $width - 20, $height - 20, $gold);
        imagesetthickness($img, 1);

        // Inner subtle frame
        $frameColor = imagecolorallocatealpha($img, 255, 255, 255, 110);
        imagerectangle($img, 40, 40, $width - 40, $height - 40, $frameColor);

        // Header Pill Badge
        imagefilledrectangle($img, 80, 80, 360, 125, $gold);
        imagestring($img, 5, 105, 95, 'CRICKET JOURNALISM DESK', imagecolorallocate($img, 10, 30, 24));

        // Brand Title
        imagestring($img, 5, 80, 180, 'CRICBAIL.COM', $white);
        imagestring($img, 5, 80, 230, 'PREMIER CRICKET NEWS, IN-DEPTH ANALYSIS & TRIVIA', $gold);

        // Divider
        imageline($img, 80, 280, 1120, 280, $gold);

        // Value propositions
        imagestring($img, 5, 80, 320, '* Breaking Bilateral & ICC Tournament Match Recaps', $offWhite);
        imagestring($img, 5, 80, 370, '* Franchise Strategies, Auction Dynamics & IPL Insights', $offWhite);
        imagestring($img, 5, 80, 420, '* Technical Bowling & Batting Tactical Breakdowns', $offWhite);
        imagestring($img, 5, 80, 470, '* Interactive 120-Second Timed Cricket Quizzes', $offWhite);

        // Footer disclaimer
        imagestring($img, 4, 80, 560, 'Independent Sports Reporting & Analysis | Server-Rendered Blade Architecture', $subText);

        imagejpeg($img, $targetPath, 88);
        imagedestroy($img);

        $this->info('[SUCCESS] Created social share card at: ' . $targetPath . ' (' . round(filesize($targetPath)/1024, 1) . ' KB)');
    }

    /**
     * Resize and compress heavy JPEG/PNG images in public/assets/images/cricket/articles/.
     */
    protected function optimizeArticleImages(): void
    {
        $dir = public_path('assets/images/cricket/articles');
        if (!File::isDirectory($dir)) {
            $this->warn('Article images directory not found: ' . $dir);
            return;
        }

        $files = File::files($dir);
        $this->line('[TASK] Inspecting ' . count($files) . ' article images for optimization...');

        $optimizedCount = 0;
        $bytesSaved = 0;

        foreach ($files as $file) {
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
                continue;
            }

            $origSize = $file->getSize();
            // If image is already under 250KB and not forced, skip
            if ($origSize <= 250 * 1024 && !$this->option('force')) {
                continue;
            }

            $filePath = $file->getRealPath();
            $info = getimagesize($filePath);
            if (!$info) {
                continue;
            }

            $width = $info[0];
            $height = $info[1];
            $mime = $info['mime'];

            // Determine if resize is needed (max width 1200px)
            $targetWidth = min($width, 1200);
            $targetHeight = (int)round(($height / $width) * $targetWidth);

            $srcImg = null;
            if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
                $srcImg = imagecreatefromjpeg($filePath);
            } elseif ($mime === 'image/png') {
                $srcImg = imagecreatefrompng($filePath);
            }

            if (!$srcImg) {
                continue;
            }

            // Create resized image
            $dstImg = imagecreatetruecolor($targetWidth, $targetHeight);

            // Handle transparency for PNG
            if ($mime === 'image/png') {
                imagealphablending($dstImg, false);
                imagesavealpha($dstImg, true);
            }

            imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

            // Overwrite original with optimized version
            if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
                imagejpeg($dstImg, $filePath, 82);
            } elseif ($mime === 'image/png') {
                // If it's a huge PNG photograph, convert to high-quality JPEG
                if ($origSize > 300 * 1024) {
                    imagejpeg($dstImg, $filePath, 82);
                } else {
                    imagepng($dstImg, $filePath, 8);
                }
            }

            // Also create WebP version if supported
            $webpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $filePath);
            if (function_exists('imagewebp')) {
                imagewebp($dstImg, $webpPath, 80);
            }

            imagedestroy($dstImg);
            imagedestroy($srcImg);

            clearstatcache(true, $filePath);
            $newSize = filesize($filePath);
            $diff = $origSize - $newSize;

            if ($diff > 0) {
                $bytesSaved += $diff;
                $optimizedCount++;
                $this->line(sprintf(
                    '  Optimized: %-38s %s -> %s (Saved %s)',
                    $file->getFilename(),
                    round($origSize / 1024 / 1024, 2) . 'MB',
                    round($newSize / 1024, 1) . 'KB',
                    round($diff / 1024 / 1024, 2) . 'MB'
                ));
            }
        }

        $this->info(sprintf(
            '[SUCCESS] %d images optimized! Total bandwidth saved: %s MB',
            $optimizedCount,
            round($bytesSaved / 1024 / 1024, 2)
        ));
    }
}
