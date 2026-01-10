<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\Muscle;
use Illuminate\Database\Seeder;

class ExerciseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('data/exercises.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("Файл {$jsonPath} не найден!");
            return;
        }

        $json = file_get_contents($jsonPath);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error("Ошибка парсинга JSON: " . json_last_error_msg());
            return;
        }

        $createdCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($data as $exerciseData) {
            try {
                // Проверяем, существует ли уже упражнение с таким именем
                $existingExercise = Exercise::where('name', $exerciseData['name'])->first();

                if ($existingExercise) {
                    $this->command->warn("Упражнение '{$exerciseData['name']}' уже существует, пропускаем...");
                    $skippedCount++;
                    continue;
                }

                // Создаем упражнение
                $exercise = Exercise::create([
                    'name' => $exerciseData['name'],
                    'description' => $exerciseData['description'] ?? null,
                ]);

                // Привязываем мышцы
                if (isset($exerciseData['muscles']) && is_array($exerciseData['muscles'])) {
                    $musclesToAttach = [];

                    foreach ($exerciseData['muscles'] as $muscleData) {
                        $code = $muscleData['code'] ?? null;
                        $isPrimary = $muscleData['is_primary'] ?? false;

                        if (!$code) {
                            $this->command->warn("Пропущена мышца без кода в упражнении '{$exerciseData['name']}'");
                            continue;
                        }

                        // Находим мышцу по коду
                        $muscle = Muscle::where('code', $code)->first();

                        if (!$muscle) {
                            $errors[] = "Мышца с кодом '{$code}' не найдена для упражнения '{$exerciseData['name']}'";
                            continue;
                        }

                        $musclesToAttach[$muscle->id] = [
                            'is_primary' => $isPrimary,
                        ];
                    }

                    // Привязываем все мышцы к упражнению
                    if (!empty($musclesToAttach)) {
                        $exercise->muscles()->attach($musclesToAttach);
                    }
                }

                $createdCount++;
                $this->command->info("Создано упражнение: {$exerciseData['name']}");

            } catch (\Exception $e) {
                $errors[] = "Ошибка при создании упражнения '{$exerciseData['name']}': " . $e->getMessage();
                $this->command->error("Ошибка при создании упражнения '{$exerciseData['name']}': " . $e->getMessage());
            }
        }

        // Выводим итоговую информацию
        $this->command->info("\n=== Итоги загрузки упражнений ===");
        $this->command->info("Создано упражнений: {$createdCount}");
        $this->command->info("Пропущено упражнений: {$skippedCount}");

        if (!empty($errors)) {
            $this->command->warn("\nОшибки и предупреждения:");
            foreach ($errors as $error) {
                $this->command->warn("  - {$error}");
            }
        } else {
            $this->command->info("Ошибок не обнаружено!");
        }

        $this->command->info("\nУпражнения успешно загружены из JSON файла!");
    }
}


