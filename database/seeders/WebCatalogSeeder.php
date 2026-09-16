<?php

namespace Database\Seeders;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

/**
 * Contenido inicial de la web pública, tomado del antiguo sitio estático.
 *
 * Todo entra publicado para que la web no quede vacía, pero con precio 0:
 * la web muestra "Consultar" hasta que el administrador cargue los precios
 * reales desde el ERP. Idempotente: no pisa lo que ya se haya editado.
 */
class WebCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedServices();
        $this->seedSupplements();
    }

    private function seedServices(): void
    {
        $catalog = [
            'Faciales' => [
                'description' => 'Protocolos de limpieza, hidratación y renovación adaptados a cada tipo de piel, con evaluación previa.',
                'services' => [
                    ['Limpieza facial profunda', 60, 'Higiene, extracción y calmado de la piel. Base de cualquier protocolo facial.'],
                    ['Hidratación facial', 50, 'Recupera la barrera cutánea en pieles secas o deshidratadas.'],
                    ['Peeling y renovación', 45, 'Renovación celular para textura irregular, marcas y manchas.'],
                    ['Tratamiento antiacné', 60, 'Control de brotes y regulación de la producción de sebo.'],
                    ['Radiofrecuencia facial', 50, 'Firmeza y estímulo de colágeno en rostro y cuello.'],
                    ['Masaje facial relajante', 40, 'Drenaje y descanso para rostro con signos de tensión.'],
                ],
            ],
            'Corporales' => [
                'description' => 'Reducción, moldeo y drenaje, además de acompañamiento post operatorio y post parto.',
                'services' => [
                    ['Drenaje linfático', 60, 'Estimula la circulación y reduce la retención de líquidos.'],
                    ['Masaje reductor', 60, 'Trabajo localizado sobre zonas de grasa acumulada.'],
                    ['Tratamiento anticelulítico', 60, 'Mejora la apariencia de la piel en muslos y glúteos.'],
                    ['Post operatorio', 50, 'Acompañamiento en la recuperación tras cirugía estética, con técnica suave.'],
                    ['Post parto', 60, 'Recuperación corporal progresiva después del embarazo.'],
                    ['Maderoterapia', 50, 'Moldeo corporal con instrumentos de madera y drenaje manual.'],
                ],
            ],
            'Podología' => [
                'description' => 'Atención podológica para todas las personas, sin distinción de género, con material esterilizado.',
                'services' => [
                    ['Quiropodia completa', 45, 'Corte, limado y tratamiento de durezas y callosidades.'],
                    ['Uña encarnada', 40, 'Alivio y tratamiento de la onicocriptosis.'],
                    ['Onicomicosis', 40, 'Manejo de hongos en la uña con seguimiento.'],
                    ['Pie diabético', 50, 'Cuidado preventivo y revisión periódica del pie de riesgo.'],
                ],
            ],
        ];

        foreach ($catalog as $categoryName => $data) {
            $category = ServiceCategory::firstOrCreate(
                ['name' => $categoryName],
                ['description' => $data['description'], 'active' => true]
            );

            foreach ($data['services'] as [$name, $minutes, $description]) {
                Service::firstOrCreate(
                    ['name' => $name],
                    [
                        'category_id' => $category->id,
                        'price' => 0,
                        'duration_minutes' => $minutes,
                        'description' => $description,
                        'active' => true,
                        'is_published' => true,
                    ]
                );
            }
        }
    }

    private function seedSupplements(): void
    {
        // Nombre anterior de la categoría: se renombra para que la URL sea /productos/suplementos.
        if (InventoryCategory::where('name', 'Suplementos')->doesntExist()) {
            InventoryCategory::where('name', 'Suplementos Nutricost')->update(['name' => 'Suplementos']);
        }

        $catalog = [
            'Suplementos' => [
                'description' => 'Suplementos Nutricost que acompañan los tratamientos de piel y bienestar.',
                'products' => [
                    ['Omega 3', 'Ácidos grasos esenciales para piel y salud cardiovascular.'],
                    ['Inositol', 'Apoyo al equilibrio hormonal y metabólico.'],
                    ['Ácido alfa lipoico', 'Antioxidante que acompaña tratamientos de piel.'],
                    ['Potasio + Magnesio', 'Función muscular y balance de electrolitos.'],
                    ['Enzimas digestivas', 'Apoyo a la digestión y absorción de nutrientes.'],
                ],
            ],
            'Vitaminas' => [
                'description' => 'Vitaminas para complementar tu rutina de cuidado desde adentro.',
                'products' => [
                    ['Vitamina C', 'Antioxidante que apoya la formación de colágeno.'],
                    ['Vitamina D3', 'Contribuye a la salud ósea y al sistema inmune.'],
                    ['Complejo B', 'Apoyo al metabolismo energético.'],
                    ['Biotina', 'Asociada al cuidado de cabello, piel y uñas.'],
                ],
            ],
        ];

        foreach ($catalog as $categoryName => $data) {
            $category = InventoryCategory::firstOrCreate(
                ['name' => $categoryName],
                ['description' => $data['description'], 'active' => true]
            );

            $this->seedProducts($category, $data['products']);
        }
    }

    /** @param array<int,array{0:string,1:string}> $products */
    private function seedProducts(InventoryCategory $category, array $products): void
    {
        foreach ($products as [$name, $description]) {
            // Stock 0: las existencias reales se cargan con un movimiento de compra.
            InventoryItem::firstOrCreate(
                ['name' => $name],
                [
                    'description' => $description,
                    'category_id' => $category->id,
                    'unit' => 'unidad',
                    'stock' => 0,
                    'min_stock' => 0,
                    'cost' => 0,
                    'sale_price' => null,
                    'is_sellable' => true,
                    'active' => true,
                    'is_published' => true,
                ]
            );
        }
    }
}
