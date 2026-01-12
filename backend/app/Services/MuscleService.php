<?php

namespace App\Services;
use App\Models\Muscle;
use Illuminate\Database\Eloquent\Collection;

class MuscleService
{
    /**
     * @warning Не использовать, пока будет здесь как пережитток прошлого)
     * @param array $filter
     * @return array
     */
    public function getFiltered(array $filter = []): array
    {
        $query = Muscle::query();

        if (isset($filter['level.gte'])) {
            $query->where('level', '>=', $filter['level.gte']);
        }
        if (isset($filter['level.lte'])) {
            $query->where('level', '<=', $filter['level.lte']);
        }
        if (isset($filter['parent_id'])) {
            $query->where('parent_id', $filter['parent_id']);
        }
        if (isset($filter['name'])) {
            $query->where('name', 'like', '%' . $filter['name'] . '%');
        }

        $musclesArray = $query->get()->toArray();

        $grouped = [];
        $allIds = []; // Собираем все ID

        foreach ($musclesArray as $element) {
            $parent = $element['parent_id'] ?? 0;
            $grouped[$parent][] = $element;
        }

        $build = function(?int $parentId = 0) use (&$build, $grouped) {
            $branch = [];
            if (!isset($grouped[$parentId])) return $branch;

            foreach ($grouped[$parentId] as $element) {
                $element['children'] = $build($element['id']);
                $branch[] = $element;
            }

            return $branch;
        };

        // Находим все корневые элементы (parent_id отсутствует в массиве ID)
        $roots = [];
        foreach ($grouped as $parentId => $children) {
            // Если parentId = 0 (null) или parentId отсутствует в массиве ID - это корень
            if ($parentId === 0 || !in_array($parentId, $allIds)) {
                $roots = array_merge($roots, $build($parentId));
            }
        }

        return $roots;
    }

    public function getTree(?int $levelGte = 1, ?int $levelLte = 4): Collection
    {
        if ($levelGte < 1) throw new \InvalidArgumentException('level gte must be greater than 0');
        if ($levelLte < 1) throw new \InvalidArgumentException('level lte must be greater than 0');
        if ($levelLte > 4) throw new \InvalidArgumentException('level lte must be <= 4');
        if ($levelGte > 4) throw new \InvalidArgumentException('level gte must be <= 4');
        if ($levelGte > $levelLte) throw new \InvalidArgumentException('level gte must be less than or equal to level lte');

        $query = Muscle::query();
        if (isset($levelGte)) {
            $query->where('level', $levelGte);
        }

        if (isset($levelLte)) {
            $childrenLevelCount = $levelLte-$levelGte;

            $currentRelation = 'children';
            for ($i = 0; $i < $childrenLevelCount; $i++) {
                $query->with($currentRelation);
                $currentRelation .= '.children';
            }
        }

        return $query->get();
    }

    public function find(int $id): Muscle
    {
        return Muscle::findOrFail($id);
    }
}
