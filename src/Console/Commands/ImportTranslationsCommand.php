<?php

namespace Novadaemon\LaravelCsvTranslations\Console\Commands;

use Illuminate\Console\Command;

class ImportTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-translations {source : Path to directory or csv file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import app translations from csv file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $source = $this->argument('source');

        if (is_dir($source)) {
            $files = array_map(function ($file) use ($source) {
                return $source.'/'.$file;
            }, scandir($source));
        } elseif (is_file($source)) {
            $files = [$source];
        } else {
            $this->error('The source must be a path to a directory or to csv file.');

            return 1;
        }

        foreach ($files as $file) {
            try {
                if (! str_ends_with($file, '.csv')) {
                    continue;
                }

                $filename = basename($file, '.csv');

                $trans_filename = str_replace('translations - ', '', $filename);

                $trans = $this->parseCsv($file);

                $this->createTransFiles($trans, $trans_filename);

                $this->info(sprintf('The file %s was successfully imported', $file));
            } catch (\Exception $e) {
                $this->error(sprintf('An error occurred importing the file %s', $file));
                $this->error($e->getMessage());
            }
        }

        return 0;
    }

    /**
     * Parse csv file and return an array
     *
     * @param  string  $file The path to the csv file
     */
    private function parseCsv($file): array
    {
        $langs = $trans = [];
        $group = $subgroup = null;

        if (($handle = fopen($file, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if ($data[0] == 'transKey') {
                    for ($i = 1; $i < count($data); $i++) {
                        $langs[] = $data[$i];
                        $trans[$data[$i]] = [];
                    }
                } else {
                    if (str_starts_with($data[0], 'group:')) {
                        $subgroup = null;
                        foreach ($langs as $lang) {
                            $group = str_replace('group:', '', $data[0]);
                            $trans[$lang][$group] = [];
                        }
                    } elseif (str_starts_with($data[0], 'subgroup:')) {
                        foreach ($langs as $lang) {
                            $subgroup = str_replace('subgroup:', '', $data[0]);
                            $trans[$lang][$group][$subgroup] = [];
                        }
                    } else {
                        foreach ($langs as $t => $lang) {
                            $value = $data[$t + 1];
                            if ($subgroup != null) {
                                $trans[$lang][$group][$subgroup][$data[0]] = $value;
                            } else {
                                $trans[$lang][$group][$data[0]] = $value;
                            }
                        }
                    }
                }
            }
            fclose($handle);

            return $trans;
        }
    }

    /**
     * Create translation files in the lang directories
     *
     * @param  array  $trans Translations
     * @param  string  $trans_filename Name of translation file
     */
    private function createTransFiles(array $trans, string $trans_filename): void
    {
        foreach ($trans as $lang => $content) {
            $dest_dir = lang_path($lang);
            if (! is_dir($dest_dir)) {
                mkdir($dest_dir, 0777, true);
            }
            $path = $dest_dir.'/'.$trans_filename.'.php';
            $file = fopen($path, 'wb');
            $content = var_export($content, true);
            $content = str_replace([
                'array (',
                ')',
            ], [
                '[',
                ']',
            ], $content);
            fwrite(
                $file,
                '<?php '
                    .PHP_EOL.'return '.$content.';'
            );
            fclose($file);
        }
    }
}
