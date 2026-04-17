<?php
namespace Rinadev\SitemapCustom\Plugin;

use Magento\Sitemap\Model\Sitemap;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class SitemapPlugin
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct(
        Filesystem $filesystem
    ) {
        $this->filesystem = $filesystem;
    }

    public function aroundGenerateXml(Sitemap $subject, callable $proceed)
    {
        $result = $proceed();

        try {
            // Build file path manually from sitemap model data
            $sitemapPath = $subject->getSitemapPath();
            $sitemapFilename = $subject->getSitemapFilename();

            // Validate both values exist
            if (empty($sitemapPath) || empty($sitemapFilename)) {
                return $result;
            }

            // Get pub/ directory path
            $pubDirectory = $this->filesystem->getDirectoryWrite(
                DirectoryList::PUB
            );

            // Clean path
            $filePath = rtrim($sitemapPath, '/') . '/' . $sitemapFilename;
            $filePath = ltrim($filePath, '/');

            // Check file exists
            if (!$pubDirectory->isExist($filePath)) {
                return $result;
            }

            // Read content
            $content = $pubDirectory->readFile($filePath);

            if (empty($content)) {
                return $result;
            }

            // Remove unwanted tags
            $content = preg_replace('/<lastmod>[^<]*<\/lastmod>\s*/', '', $content);
            $content = preg_replace('/<changefreq>[^<]*<\/changefreq>\s*/', '', $content);
            $content = preg_replace('/<priority>[^<]*<\/priority>\s*/', '', $content);

            // Write cleaned content back
            $pubDirectory->writeFile($filePath, $content);

        } catch (\Exception $e) {
            // Log error but don't break sitemap generation
            return $result;
        }

        return $result;
    }
}