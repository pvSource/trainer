<?php

namespace Database\Seeders;

use App\Models\Muscle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MuscleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('data/muscles.json');

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

        // Обработка корневых элементов
        foreach ($data as $item) {
            $this->createMuscle($item, null);
        }

        $this->command->info('Мышцы успешно загружены из JSON файла!');
    }

    /**
     * Рекурсивное создание мышцы и её дочерних элементов
     *
     * @param array $data Данные мышцы из JSON
     * @param int|null $parentId ID родительской мышцы
     * @return Muscle
     */
    private function createMuscle(array $data, ?int $parentId = null): Muscle
    {
        // Создание мышцы
        $muscle = Muscle::create([
            'parent_id' => $parentId,
            'name' => $data['name'],
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
        ]);

        // Рекурсивная обработка дочерних элементов
        if (isset($data['children']) && is_array($data['children'])) {
            foreach ($data['children'] as $child) {
                $this->createMuscle($child, $muscle->id);
            }
        }

        return $muscle;
    }
}
