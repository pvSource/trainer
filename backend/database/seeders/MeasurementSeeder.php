<?php


namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\Muscle;
use App\Models\Measurement;
use Illuminate\Database\Seeder;


class MeasurementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = $this->getDataFromJsonFile();
        if (!$data) return;

        $createdCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($data as $rowData) {
            try {
                // Проверяем, существует ли уже замер с таким кодом
                $existingMeasurement = Measurement::where('code', $rowData['code'])->first();

                if ($existingMeasurement) {
                    $this->command->warn("Замер '{$existingMeasurement['code']}' уже существует, пропускаем...");
                    $skippedCount++;
                    continue;
                }

                // Создаем замер
                $measurement = Measurement::create([
                    'name' => $rowData['name'],
                    'code' => $rowData['code'],
                    'unit' => $rowData['unit'],
                    'description' => $rowData['description'] ?? null,
                ]);

                // Привязываем мышцы
                if (isset($rowData['muscles']) && is_array($rowData['muscles'])) {
                    $musclesToAttach = [];
                    foreach ($rowData['muscles'] as $muscleCode) {
                        // Находим мышцу по коду
                        $muscle = Muscle::where('code', $muscleCode)->first();

                        if (!$muscle) {
                            $errors[] = "Мышца с кодом '{$muscleCode}' не найдена";
                            continue;
                        }

                        $musclesToAttach[] = $muscle->id;
                    }

                    $measurement->muscles()->sync($musclesToAttach);
                }

                $createdCount++;
                $this->command->info("Создан замер: {$rowData['name']}");

            } catch (\Exception $e) {
                $errors[] = "Ошибка при создании '{$rowData['name']}': " . $e->getMessage();
                $this->command->error("Ошибка при создании '{$rowData['name']}': " . $e->getMessage());
            }
        }

        // Выводим итоговую информацию
        $this->command->info("\n=== Итоги загрузки ===");
        $this->command->info("Создано: {$createdCount}");
        $this->command->info("Пропущено: {$skippedCount}");

        if (!empty($errors)) {
            $this->command->warn("\nОшибки и предупреждения:");
            foreach ($errors as $error) {
                $this->command->warn("  - {$error}");
            }
        } else {
            $this->command->info("Ошибок не обнаружено!");
        }

        $this->command->info("\nУспешно загружены из JSON файла!");
    }

    private function getDataFromJsonFile(): ?array
    {
        $jsonPath = database_path('data/measurements.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("Файл {$jsonPath} не найден!");
            return null;
        }

        $json = file_get_contents($jsonPath);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error("Ошибка парсинга JSON: " . json_last_error_msg());
            return null;
        }
        return $data;
    }
}

