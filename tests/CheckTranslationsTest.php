<?php

declare(strict_types=1);

namespace AlexPavliukov\Devtools\Tests;

use PHPUnit\Framework\TestCase;

final class CheckTranslationsTest extends TestCase
{
    private string $fixtureRoot;

    protected function setUp(): void
    {
        $this->fixtureRoot = sys_get_temp_dir().'/devtools-fixture-'.bin2hex(random_bytes(6));

        mkdir($this->fixtureRoot, 0777, true);
    }

    protected function tearDown(): void
    {
        shell_exec('rm -rf '.escapeshellarg($this->fixtureRoot));
    }

    public function test_all_mode_reports_missing_keys_per_locale(): void
    {
        $this->writeFile('lang/en.json', '{"Save": "Save"}');
        $this->writeFile('lang/ru.json', '{}');
        $this->writeFile('app/Page.php', "<?php __('Save'); __('Delete');");

        [$exitCode, $stdout, $stderr] = $this->runScript(['--all']);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Delete', $stdout);
        $this->assertStringContainsString('ru', $stdout);
        $this->assertStringContainsString('2 missing', $stdout);
        $this->assertStringContainsString('Untranslated strings found', $stderr);
    }

    public function test_passes_when_every_key_is_translated_in_every_locale(): void
    {
        $this->writeFile('lang/en.json', '{"Save": "Save"}');
        $this->writeFile('lang/ru.json', '{"Save": "Сохранить"}');
        $this->writeFile('app/Page.php', "<?php __('Save');");

        [$exitCode, $stdout] = $this->runScript(['--all']);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('covered in every locale', $stdout);
    }

    public function test_exits_cleanly_when_host_has_no_json_locales(): void
    {
        $this->writeFile('app/Page.php', "<?php __('Save');");

        [$exitCode, $stdout] = $this->runScript(['--all']);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('No lang/*.json files found', $stdout);
    }

    public function test_ratchet_mode_checks_only_files_changed_vs_main(): void
    {
        $this->writeFile('lang/en.json', '{}');
        $this->writeFile('app/Old.php', "<?php __('Old string');");
        $this->git('init -b main');
        $this->git('add -A');
        $this->git('commit -m base');
        $this->git('checkout -b feature');
        $this->writeFile('app/New.php', "<?php __('New string');");

        [$exitCode, $stdout] = $this->runScript();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('New string', $stdout);
        $this->assertStringNotContainsString('Old string', $stdout);
    }

    public function test_extracts_edge_cases_and_ignores_dotted_keys(): void
    {
        $this->writeFile('lang/en.json', '{}');
        $this->writeFile(
            'app/Edge.php',
            "<?php __('It\\'s fine'); trans('auth.failed'); trans_choice('One apple', 1);",
        );
        $this->writeFile('resources/views/page.blade.php', "@lang('Hello')");

        [$exitCode, $stdout] = $this->runScript(['--all']);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString("It's fine", $stdout);
        $this->assertStringContainsString('One apple', $stdout);
        $this->assertStringContainsString('Hello', $stdout);
        $this->assertStringNotContainsString('auth.failed', $stdout);
    }

    /**
     * @param  list<string>  $arguments
     * @return array{0: int, 1: string, 2: string}
     */
    private function runScript(array $arguments = []): array
    {
        $command = array_merge(
            [PHP_BINARY, dirname(__DIR__).'/bin/check-translations'],
            $arguments,
        );

        $process = proc_open(
            $command,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $this->fixtureRoot,
        );

        $this->assertIsResource($process);

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $stdout, $stderr];
    }

    private function writeFile(string $relativePath, string $contents): void
    {
        $absolutePath = $this->fixtureRoot.'/'.$relativePath;
        $directory = dirname($absolutePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($absolutePath, $contents);
    }

    private function git(string $arguments): void
    {
        shell_exec(sprintf(
            'git -C %s -c user.email=dev@example.com -c user.name=Dev -c commit.gpgsign=false %s 2>&1',
            escapeshellarg($this->fixtureRoot),
            $arguments,
        ));
    }
}
