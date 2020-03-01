<?php

namespace Presta\SitemapBundle\Tests\Integration\Tests;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class CliTest extends SitemapTestCase
{
    private const PUBLIC_DIR = __DIR__ . '/../public';

    protected function setUp(): void
    {
        $files = array_merge(
            [$this->index()],
            $this->sections()
        );

        foreach (array_filter(array_map('realpath', $files)) as $file) {
            if (!@unlink($file)) {
                throw new \RuntimeException('Cannot delete file ' . $file);
            }
        }
    }

    private function index(): string
    {
        return self::PUBLIC_DIR . '/sitemap.xml';
    }

    private function section(string $name): string
    {
        return self::PUBLIC_DIR . '/sitemap.' . $name . '.xml';
    }

    private function sections(): array
    {
        return glob(self::section('*'));
    }

    public function testDumpSitemapUsingCLI()
    {
        $index = $this->index();
        self::assertFileNotExists($index, 'Sitemap index file does not exists before dump');

        $static = $this->section('static');
        self::assertFileNotExists($static, 'Sitemap "static" section file does not exists before dump');

        $blog = $this->section('blog');
        self::assertFileNotExists($blog, 'Sitemap "blog" section file does not exists before dump');

        $archives = $this->section('archives');
        $archives0 = $this->section('archives_0');
        self::assertFileNotExists($archives, 'Sitemap "archive" section file does not exists before dump');
        self::assertFileNotExists($archives0, 'Sitemap "archive_0" section file does not exists before dump');

        $commandTester = new CommandTester(
            (new Application(self::createKernel()))->find('presta:sitemaps:dump')
        );
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();

        self::assertSame(0, $commandTester->getStatusCode(), 'Sitemap dump command succeed');
        self::assertStringContainsString('sitemap.static.xml', $output, '"sitemap.static.xml" was dumped');
        self::assertStringContainsString('sitemap.static.xml', $output, '"sitemap.blog.xml" was dumped');
        self::assertStringContainsString('sitemap.archives.xml', $output, '"sitemap.archives.xml" was dumped');
        self::assertStringContainsString('sitemap.archives_0.xml', $output, '"sitemap.archives_0.xml" was dumped');

        // get sitemap index content via filesystem
        self::assertFileExists($index, 'Sitemap index file exists after dump');
        self::assertIsReadable($index, 'Sitemap index section file is readable');
        self::assertIndex(file_get_contents($index));

        // get sitemap "static" section content via filesystem
        self::assertFileExists($static, 'Sitemap "static" section file exists after dump');
        self::assertIsReadable($static, 'Sitemap "static" section file is readable');
        self::assertStaticSection(file_get_contents($static));

        // get sitemap "blog" section content via filesystem
        self::assertFileExists($blog, 'Sitemap "blog" section file exists after dump');
        self::assertIsReadable($blog, 'Sitemap "blog" section file is readable');
        self::assertBlogSection(file_get_contents($blog));

        // get sitemap "archives" section content via filesystem
        self::assertFileExists($archives, 'Sitemap "archives" section file exists after dump');
        self::assertIsReadable($archives, 'Sitemap "archives" section file is readable');
        self::assertFileExists($archives0, 'Sitemap "archives_0" section file exists after dump');
        self::assertIsReadable($archives0, 'Sitemap "archives_0" section file is readable');
        self::assertArchivesSection(file_get_contents($archives));
        self::assertArchivesSection(file_get_contents($archives0));
    }
}