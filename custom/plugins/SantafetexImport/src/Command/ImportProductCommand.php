<?php declare(strict_types=1);

namespace SantafetexImport\Command;

use SantafetexImport\Service\ProductCsvService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'santafetex:import',
    description: 'Import products from Excel/JSON files and generate CSV'
)]
class ImportProductCommand extends Command
{
    private ProductCsvService $csvService;

    public function __construct(ProductCsvService $csvService)
    {
        parent::__construct();
        $this->csvService = $csvService;
    }

    protected function configure(): void
    {
        $this
            ->setHelp(
                'This command reads Excel (.xls, .xlsx) or JSON files from input folder ' .
                'and generates product import CSV files.' . PHP_EOL . PHP_EOL .
                'Usage:' . PHP_EOL .
                '  bin/console santafetex:import --vendor=ross' . PHP_EOL .
                '  bin/console santafetex:import --vendor=harko' . PHP_EOL .
                '  bin/console santafetex:import --vendor=newwave' . PHP_EOL .
                '  bin/console santafetex:import (auto-detect from filename)' . PHP_EOL . PHP_EOL .
                'Auto-Detection:' . PHP_EOL .
                '  File with "ross" in name → Ross vendor' . PHP_EOL .
                '  File with "harko" or "hakro" in name → Harko vendor' . PHP_EOL .
                '  File with "newwave" in name or .json → Newwave vendor' . PHP_EOL . PHP_EOL .
                'Directory Structure:' . PHP_EOL .
                '  Input:  custom/plugins/SantafetexImport/import/input/' . PHP_EOL .
                '  Output: custom/plugins/SantafetexImport/import/output/' . PHP_EOL .
                '  Processed: custom/plugins/SantafetexImport/import/processed/'
            )
            ->addOption(
                'vendor',
                'v',
                InputOption::VALUE_OPTIONAL,
                'Vendor name: ross, harko, or newwave (auto-detected if not specified)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $vendor = $input->getOption('vendor');

        $io->title('🚀 SantaFeTex Product Import');

        // Check input directory
        $inputPath = $this->csvService->getInputPath();
        if (!is_dir($inputPath)) {
            $io->error("Input directory does not exist: {$inputPath}");
            return Command::FAILURE;
        }

        // Get all files
        $allFiles = glob($inputPath . '/*.{xls,xlsx,json}', GLOB_BRACE);

        if (empty($allFiles)) {
            $io->warning('No files found in input directory!');
            $io->text("Place Excel (.xls, .xlsx) or JSON files in: {$inputPath}");
            return Command::SUCCESS;
        }

        // Auto-detect vendor if not specified
        if (!$vendor) {
            $io->section('🔍 Auto-Detecting Vendor from Filename');
            
            $detectedVendors = [];
            foreach ($allFiles as $file) {
                $detectedVendor = $this->detectVendorFromFilename($file);
                if ($detectedVendor) {
                    $detectedVendors[basename($file)] = $detectedVendor;
                    $io->text("📄 " . basename($file) . " → " . strtoupper($detectedVendor));
                }
            }

            if (empty($detectedVendors)) {
                $io->warning('Could not auto-detect vendor from filenames!');
                $io->newLine();
                
                $vendor = $io->choice(
                    'Please select vendor manually:',
                    ['ross', 'harko', 'newwave'],
                    'harko'
                );
            } elseif (count(array_unique($detectedVendors)) > 1) {
                $io->warning('Multiple vendors detected! Please process one vendor at a time.');
                $io->newLine();
                
                $vendor = $io->choice(
                    'Select which vendor to process:',
                    array_unique($detectedVendors)
                );
            } else {
                $vendor = reset($detectedVendors);
                $io->success("✅ Auto-detected vendor: " . strtoupper($vendor));
            }
        }

        // Validate vendor
        if (!in_array($vendor, ['ross', 'harko', 'newwave'], true)) {
            $io->error('Invalid vendor specified!');
            $io->text('Please use one of: ross, harko, newwave');
            return Command::FAILURE;
        }

        $io->section('Configuration');
        $io->definitionList(
            ['Vendor' => strtoupper($vendor)],
            ['Input Path' => $inputPath],
            ['Output Path' => $this->csvService->getOutputPath()]
        );

        // Filter files by vendor
        $files = [];
        foreach ($allFiles as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $detectedVendor = $this->detectVendorFromFilename($file);
            
            if ($detectedVendor === $vendor || 
                ($vendor === 'newwave' && $extension === 'json') ||
                ($vendor !== 'newwave' && in_array($extension, ['xls', 'xlsx']))) {
                $files[] = $file;
            }
        }

        if (empty($files)) {
            $io->warning('No matching files found for vendor: ' . strtoupper($vendor));
            $io->text([
                "Expected file types: " . (($vendor === 'newwave') ? 'JSON (.json)' : 'Excel (.xls, .xlsx)'),
                "Tip: Include vendor name in filename (e.g., 'HAKRO_products.xlsx', 'ross_data.xls')"
            ]);
            return Command::SUCCESS;
        }

        $io->section('📁 Found Files');
        $io->listing(array_map('basename', $files));

        if (!$io->confirm('Do you want to process these files?', true)) {
            $io->note('Import cancelled by user');
            return Command::SUCCESS;
        }

        $io->section('🔄 Processing Files...');
        $io->newLine();

        try {
            $results = $this->csvService->processFiles($vendor);

            if (isset($results['error'])) {
                $io->error($results['error']);
                return Command::FAILURE;
            }

            $totalSuccess = 0;
            $totalFailed = 0;

            foreach ($results as $result) {
                $io->newLine();
                
                if ($result['success']) {
                    $totalSuccess++;
                    
                    $io->success("✅ Processed: {$result['file']}");
                    
                    if (isset($result['csv_path'])) {
                        $io->text("📄 CSV Generated: " . basename($result['csv_path']));
                    }

                    if (isset($result['stats']) && !empty($result['stats'])) {
                        $statsTable = [];
                        
                        if (isset($result['stats']['total_products'])) {
                            $statsTable[] = ['Total Products', $result['stats']['total_products']];
                        }
                        if (isset($result['stats']['new_brands']) && $result['stats']['new_brands'] > 0) {
                            $statsTable[] = ['New Brands', $result['stats']['new_brands']];
                        }
                        if (isset($result['stats']['new_categories']) && $result['stats']['new_categories'] > 0) {
                            $statsTable[] = ['New Categories', $result['stats']['new_categories']];
                        }
                        if (isset($result['stats']['new_colors']) && $result['stats']['new_colors'] > 0) {
                            $statsTable[] = ['New Colors', $result['stats']['new_colors']];
                        }
                        if (isset($result['stats']['new_sizes']) && $result['stats']['new_sizes'] > 0) {
                            $statsTable[] = ['New Sizes', $result['stats']['new_sizes']];
                        }

                        if (!empty($statsTable)) {
                            $io->table(['Metric', 'Count'], $statsTable);
                        }
                    }
                } else {
                    $totalFailed++;
                    $io->error("❌ Failed: {$result['file']}");
                    if (isset($result['error'])) {
                        $io->text("Error: {$result['error']}");
                    }
                }
            }

            $io->newLine();
            $io->section('📊 Import Summary');
            
            $summaryTable = [
                ['Total Files Processed', count($results)],
                ['Successful', $totalSuccess],
                ['Failed', $totalFailed]
            ];
            
            $io->table(['Metric', 'Count'], $summaryTable);

            if ($totalSuccess > 0) {
                $io->success('🎉 Import completed successfully!');
                $io->newLine();
                $io->text([
                    'Next Steps:',
                    '1. Check generated CSV files in: ' . $this->csvService->getOutputPath(),
                    '2. Import CSV files into Shopware using Import/Export profile',
                    '3. Processed files moved to: custom/plugins/SantafetexImport/import/processed/'
                ]);
            } else {
                $io->warning('No files were successfully processed');
            }

            return ($totalFailed > 0) ? Command::FAILURE : Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Import failed with exception!');
            $io->text([
                'Error Message: ' . $e->getMessage(),
                'File: ' . $e->getFile(),
                'Line: ' . $e->getLine()
            ]);
            
            if ($output->isVerbose()) {
                $io->section('Stack Trace');
                $io->text($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    /**
     * Auto-detect vendor from filename
     */
    private function detectVendorFromFilename(string $filePath): ?string
    {
        $fileName = strtolower(basename($filePath));
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        // Check for JSON (Newwave)
        if ($extension === 'json') {
            return 'newwave';
        }
        
        // Check filename for vendor keywords
        if (strpos($fileName, 'ross') !== false) {
            return 'ross';
        }
        
        if (strpos($fileName, 'harko') !== false || strpos($fileName, 'hakro') !== false) {
            return 'harko';
        }
        
        if (strpos($fileName, 'newwave') !== false || strpos($fileName, 'new-wave') !== false) {
            return 'newwave';
        }
        
        return null;
    }
}
